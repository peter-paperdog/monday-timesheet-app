<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MondayService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('MONDAY_API_TOKEN');
    }

    /**
     * Makes a request to the Monday.com API with the given GraphQL query.
     *
     * @param string $query The GraphQL query string.
     * @return array The JSON response from the API.
     */
    private function makeApiRequest(string $query, array $variables = []): array|null
    {
        $requestData = [
            'query' => $query,
        ];

        if (!empty($variables)) {
            $requestData['variables'] = $variables;
        }

        try {
            // Set the script execution time limit dynamically
            set_time_limit(300); // Allow up to 5 minutes

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])
                ->timeout(120) // Timeout for the HTTP request (2 minutes)
                ->post('https://api.monday.com/v2', $requestData);

            if ($response->successful()) {
                return $response->json();
            }

            // Log or handle non-successful responses
            logger()->error('API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            // Log the exception for debugging purposes
            logger()->error('API request encountered an error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetches the list of users from the Monday.com API.
     *
     * @return array The array of user objects.
     */
    public function getUsers()
    {
        $query = <<<'GRAPHQL'
        query {
          users {
            id
            name
            email
            location
          }
        }
    GRAPHQL;

        $response = $this->makeApiRequest($query);

        return $response['data']['users'];
    }

    /**
     * Fetches the list of boards from the Monday.com API.
     *
     * @return array The array of board objects.
     */
    public function getBoards()
    {
        $query = <<<'GRAPHQL'
    query {
      boards(limit: 999, workspace_ids: 5096840, order_by: used_at){
            id
            name
            type
      }
    }
    GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['boards'];
    }

    /**
     * Fetches the list of boards from the Monday.com API.
     *
     * @return array The array of board objects.
     */
    public function getAssignments()
    {
        $query = <<<'GRAPHQL'
    query {
        boards (limit:500, workspace_ids: 5096840){
            items_page(limit: 300) {
                items {
                    id
                    column_values {
                        ... on PeopleValue {
                        persons_and_teams{
                            id
                        }
                        }
                        ... on StatusValue {
                            is_done
                        }
                    }
                }
            }
        }
    }
    GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['boards'];
    }

    /**
     * Fetches the items for the board.
     *
     * @param string $boardId The ID of the board.
     * @return array The array of items with time tracking data.
     */
    public function getItems(string $boardId)
    {
        $query = <<<GRAPHQL
    query {
      boards (ids:"$boardId"){
        items_page(limit:500){
            items{
                id
                name
                group{
                    id
                }
                parent_item{
                    id
                }
            }
        }
      }
    }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['boards'][0]['items_page']['items'];
    }

    /**
     * Fetches the groups for the board.
     *
     * @param string | array $itemId The ID(s) of the item(s).
     * @return array The array of TimeTrackingValue columns.
     */
    public function getTimeTrackingColumns(array|string $itemIds)
    {
        $itemIds = is_array($itemIds) ? implode(',', $itemIds) : $itemIds;
        $query = <<<GRAPHQL
        query {
          items (ids: [$itemIds]) {
            id
            name
            column_values{
                ...on TimeTrackingValue{
                    id
                    column{
                        id
                        title
                    }
                }
            }
          }
        }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['items'];
    }

    /**
     * Updates the time tracking data for a specific item on a board.
     *
     * @param string $boardId The ID of the board.
     * @param string $itemId The ID of the item to update.
     * @param string $columnId The ID of the column containing the time tracking data.
     * @param int $startTimestamp The starting timestamp for the time tracking entry.
     * @param int $endTimestamp The ending timestamp for the time tracking entry.
     * @return array The result of the API request to update the time tracking data.
     */
    public function updateTimeTracking($boardId, $itemId, $columnId, $startTimestamp, $endTimestamp)
    {
        $mutation = <<<'GRAPHQL'
        mutation ($boardId: ID!, $itemId: ID!, $columnId: String!, $value: JSON!) {
          change_column_value(
            board_id: $boardId,
            item_id: $itemId,
            column_id: $columnId,
            value: $value
          ) {
            id
          }
        }
    GRAPHQL;

        // Convert times to JSON format
        $value = json_encode([
            "started_at" => $startTimestamp,
            "ended_at" => $endTimestamp
        ]);

        return $this->makeApiRequest($mutation, [
            'boardId' => $boardId,
            'itemId' => $itemId,
            'columnId' => $columnId,
            'value' => $value
        ]);
    }

    /**
     * Fetches the groups for the board.
     *
     * @param string $boardId The ID of the board.
     * @return array The array of groups.
     */
    public function getGroups(string $boardId)
    {
        $query = <<<GRAPHQL
    query {
      boards (ids:"$boardId"){
        groups{
            id
            title
        }
      }
    }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['boards'][0]['groups'];
    }

    /**
     * Fetches the time tracking data for the board.
     *
     * @param string $boardId The ID of the board.
     * @return array The array of items with time tracking data.
     */
    public function getTimeTrackingItems(string $boardId): array
    {
        // Define the GraphQL query
        $query = <<<GRAPHQL
                {
                  boards (ids:"$boardId"){
                    items_page(limit:500){
                        items{
                            id
                            column_values {
                                ... on TimeTrackingValue {
                                    history {
                                        id
                                        started_user_id
                                        started_at
                                        ended_at
                                    }
                                }
                            }
                        }
                    }
                  }
                }
GRAPHQL;

        // Make the API request and return the result
        $timeTrackingData = $this->makeApiRequest($query);

        $items = [];
        if ($timeTrackingData != null) {
            foreach ($timeTrackingData['data']['boards'] as $item) {
                foreach ($item['items_page']['items'] as $_item) {
                    foreach ($_item['column_values'] as $column_value) {
                        if (!empty($column_value)) {
                            foreach ($column_value['history'] as $history) {
                                $history['id'] = intval($history['id']);
                                $history['started_user_id'] = intval($history['started_user_id']);
                                $items[] = array_merge(
                                    ['item_id' => intval($_item['id'])],
                                    $history
                                );
                            }
                        }
                    }
                }
            }
        }
        return $items;
    }


    /**
     * Fetches the items for the board.
     *
     * @param string $boardId The ID of the board.
     * @return array The array of items with time tracking data.
     */
    public function getContactItems(string $boardId)
    {
        $query = <<<GRAPHQL
    query {
      boards (ids:"$boardId"){
        items_page(limit:500){
            items{
                id
                name
                column_values {
                    text
                }
            }
        }
      }
    }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['boards'][0]['items_page']['items'];
    }


    /**
     * Fetches the list of clients, projects from the Monday.com API.
     *
     * @return object The object of clients,projects.
     */
    public function getFolders(): object
    {
        $clients = [];
        $projects = [];

        $page = 1;
        do {
            $query = <<<GRAPHQL
    query {
      folders(workspace_ids: "9147845" limit:100 page:$page){
          id
          name
          parent{
            id
          }
          children{
            id
            name
          }
        }
    }
GRAPHQL;

            $response = $this->makeApiRequest($query);
            $data = $response['data']['folders'];

            $filtered_clients = array_filter($data, function ($item) {
                return is_null($item['parent']);
            });

            foreach ($filtered_clients as $item) {
                $client = new \stdClass();
                $client->id = $item['id'];
                $client->name = $item['name'];
                $clients[] = $client;
            }
            $page++;
        } while (!empty($data));

        // Sort the data by 'name' ascending
        usort($clients, function ($a, $b) {
            return strcmp($a->name, $b->name);
        });

        $data = new \stdClass();
        $data->clients = $clients;
        $data->projects = $projects;
        return $data;
    }
}
