<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Cache;
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
     * @param  string  $query  The GraphQL query string.
     * @return array The JSON response from the API.
     */
    private function makeApiRequest(string $query, array $variables = []): array|null
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
        ])
            ->timeout(120) // Set timeout to 60 seconds
            ->post('https://api.monday.com/v2', [
                'query' => $query,
                'variables' => $variables
            ]);

        return $response->json();
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
      boards(limit: 999){
            id
            name
      }
    }
    GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['boards'];
    }

    /**
     * Fetches the time tracking data for all boards.
     *
     * @param  string  $boardId  The ID of the board.
     * @return array The array of items with time tracking data.
     */
    public function getTimeTrackingItems(): array
    {
        // Check if the data is already in the cache
        /*$timeTrackingData = Cache::rememberForever('timeTrackingData', function () {
            // Define the GraphQL query
            $query = <<<GRAPHQL
            {
              boards (limit:300){
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
            return $this->makeApiRequest($query);
        });*/

        // Define the GraphQL query
        $query = <<<GRAPHQL
                {
                  boards (limit:300){
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


    public function getItems($ids = []): array
    {
        if (empty($ids)) {
            return [];
        }
        $ids_string = implode(',', $ids);

        $query = <<<GRAPHQL
        query{
            items(ids: [$ids_string]) {
                 id
                 name
                 group{
                    id
                    title
                 }
                 board{
                    id
                    name
                 }
                 parent_item{
                    id
                 }
                 subitems{
                    id
                 }
              }
            }
        GRAPHQL;

        $response = $this->makeApiRequest($query);

        $items = [];

        if (!isset($response['data']['items'])) {
            var_dump($response);
            exit;
        }
        foreach ($response['data']['items'] as $item) {
            $items[$item['id']] = $item;
        }

        return $items;
    }
}
