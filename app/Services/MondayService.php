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

            die($response->body());
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
        $page = 1;
        $boards = [];
        do {
            $query = <<<GRAPHQL
                query {
                  boards(workspace_ids: 9147845, limit: 100, page: $page){
                        id
                        name
                        type
                  }
                }
GRAPHQL;
            $response = $this->makeApiRequest($query);
            $boards = array_merge($boards, $response['data']['boards']);
            $page++;
        } while (!empty($response['data']['boards']));
        return $boards;
    }

    /**
     * @return array The array of board objects.
     */
    public function getBoardsCreatedWithNewProjectButtonPress()
    {
        $query = <<<'GRAPHQL'
    query {
      boards(limit: 3, workspace_ids: 9147845, order_by: created_at){
            id
            name
            board_folder_id
      }
    }
    GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['boards'];
    }

    /**
     * Update folder name
     * @param $folder_id
     * @param $folder_name
     * @return array|null
     */
    public function updateFolder($folder_id, $folder_name)
    {
        $query = <<<GRAPHQL
            mutation {
                update_folder (folder_id: $folder_id, name: "$folder_name") {
                id
                }
            }
GRAPHQL;
        // Define the variables to pass into the query
        return $this->makeApiRequest($query);
    }

    /**
     * Fetches the list of boards from the Monday.com API.
     *
     * @return array The array of board objects.
     */
    public function getBoardsFromNewStructure()
    {
        $query = <<<'GRAPHQL'
    query {
      boards(limit: 999, workspace_ids: 9147845, order_by: used_at){
            id
            name
            type
            board_folder_id
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
     * Fetches the list of boards from the Monday.com API.
     *
     * @return array The array of board objects.
     */
    public function getAssignmentsFromNewStructure()
    {
        $query = <<<'GRAPHQL'
    query {
        boards (limit:500, workspace_ids: 9147845){
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
    public function getItems(string $boardId): array
    {
        $allItems = [];
        $cursor = null;

        do {
            $cursorPart = $cursor ? "cursor: \"$cursor\"" : '';

            $query = <<<GRAPHQL
        query {
          boards(ids: "$boardId") {
            items_page(limit: 500, $cursorPart) {
              cursor
              items {
                id
                name
                group {
                  id
                }
                parent_item {
                  id
                }
              }
            }
          }
        }
        GRAPHQL;

            $response = $this->makeApiRequest($query);
            $page = $response['data']['boards'][0]['items_page'];

            $items = $page['items'] ?? [];
            $allItems = array_merge($allItems, $items);
            $cursor = $page['cursor'] ?? null;

        } while ($cursor); // Amíg van még lap, folytatjuk

        return $allItems;
    }

    /**
     * Fetches the items for the board.
     *
     * @param string $boardId The ID of the board.
     * @return \stdClass The array of items with time tracking data.
     */
    public function getInvoiceItems(string $boardId): \stdClass
    {
        $allItems = [];
        $cursor = null;
        $return = new \stdClass();

        do {
            $cursorPart = $cursor ? "cursor: \"$cursor\"" : '';

            $query = <<<GRAPHQL
            query {
              boards(ids: "$boardId") {
                columns{
                    id
                    title
                }
                name
                items_page(limit: 500, $cursorPart) {
                  cursor
                  items {
                    id
                    name
                    group {
                      title
                    }
                    column_values {
                        id
                        text
                        value
                    }
                  }
                }
              }
            }
            GRAPHQL;

            $response = $this->makeApiRequest($query);
            $return->name = $response['data']['boards'][0]['name'];
            $columnsMeta = $response['data']['boards'][0]['columns'] ?? [];
            $page = $response['data']['boards'][0]['items_page'];

            $items = $page['items'] ?? [];
            $allItems = array_merge($allItems, $items);
            $cursor = $page['cursor'] ?? null;

        } while ($cursor); // Amíg van még lap, folytatjuk

        $columnsById = collect($columnsMeta)->keyBy('id');
        $grouped = [];
        foreach ($allItems as $item) {
            $groupTitle = $item['group']['title'] ?? 'Ismeretlen csoport';

            $columnValues = collect($item['column_values'] ?? [])->mapWithKeys(function ($col) use ($columnsById) {
                $title = $columnsById[$col['id']]['title'] ?? $col['id'];
                return [$title => $col['text']];
            });

            $type = $columnValues['Type'] ?? '';
            $status = $columnValues['Status'] ?? '';

            if ($status !== 'To Be Invoiced') {
                continue;
            }

            $columnValues = $columnValues->toArray();
            $columnValues['id'] = $item['id'];
            $columnValues['name'] = $item['name'];
            $columnValues['parent_id'] = $item['parent_item']['id'] ?? null;

            if (empty($columnValues['Time Spent'])) {
                $columnValues['Time Spent'] = '0:0:0';
            }

            if (isset($columnValues['Time Spent'])) {
                list($hours, $minutes, $seconds) = explode(':', $columnValues['Time Spent']);
                $hoursDecimal = (int)$hours + ((int)$minutes / 60) + ((int)$seconds / 3600);
                $hoursDecimal = round($hoursDecimal, 2); // opcionálisan kerekítjük
                $columnValues['Cost'] = $hoursDecimal * 45;
            }

            $grouped[$groupTitle][] = $columnValues;
        }

        $return->data = $grouped;

        return $return;
    }

    /**
     * Fetches the items for the board.
     *
     * @param string $boardId The ID of the board.
     * @return array The array of items with time tracking data.
     */
    public function getInvoiceContacts(): array
    {
        $allItems = [];
        $cursor = null;

        do {
            $cursorPart = $cursor ? "cursor: \"$cursor\"" : '';

            $query = <<<GRAPHQL
            query {
              boards(ids: 8451006561) {
                columns{
                    id
                    title
                }
                items_page(limit: 500, $cursorPart) {
                  cursor
                  items {
                    id
                    name
                    group {
                        title
                    }
                    column_values {
                        id
                        text
                        value
                    }
                  }
                }
              }
            }
            GRAPHQL;

            $response = $this->makeApiRequest($query);
            $columnsMeta = $response['data']['boards'][0]['columns'] ?? [];
            $page = $response['data']['boards'][0]['items_page'];

            $items = $page['items'] ?? [];
            $allItems = array_merge($allItems, $items);
            $cursor = $page['cursor'] ?? null;

        } while ($cursor); // Amíg van még lap, folytatjuk

        $columnsById = collect($columnsMeta)->keyBy('id');
        $grouped = [];
        foreach ($allItems as $item) {

            $columnValues = collect($item['column_values'] ?? [])->mapWithKeys(function ($col) use ($columnsById) {
                $title = $columnsById[$col['id']]['title'] ?? $col['id'];
                return [$title => $col['text']];
            });


            $columnValues['id'] = $item['id'];
            $columnValues['name'] = $item['name'];

            $grouped[] = $columnValues;
        }

        return $grouped;
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
    public function getGroups($boardId)
    {
        $query = <<<GRAPHQL
    query {
          boards (ids:[$boardId] limit: 100 page: 1){
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
     * Fetches the tasks for the project.
     */
    public function getTasks($boardId)
    {
        $tasks = [];
        $cursor = null;

        do {
            $cursorPart = $cursor ? "cursor: \"$cursor\"" : '';
            $query = <<<GRAPHQL
                query {
                    boards(ids: $boardId){
                        items_page(limit: 100, $cursorPart){
                            items{
                                id
                                name
                                group{
                                    id
                                }
                            }
                            cursor
                        }
                    }
                }
GRAPHQL;

            $response = $this->makeApiRequest($query);

            $page = $response['data']['boards'][0]['items_page'];

            $items = $page['items'] ?? [];
            $cursor = $page['cursor'] ?? null;

            $tasks = array_merge($tasks, $items);
        } while ($cursor);
        return $tasks;
    }

    /**
     * @param $itemId
     * @return int
     */
    public function getProjectIdForItem($itemId)
    {

        $query = <<<GRAPHQL
                query {
                    items(ids: $itemId){
                        board{
                            board_folder_id
                        }
                    }
                }
GRAPHQL;

        $response = $this->makeApiRequest($query);
        return intval($response['data']['items'][0]['board']['board_folder_id']);
    }

    /**
     * Fetches the groups for the board.
     *
     * @param string $boardId The ID of the board.
     * @return array The array of groups.
     */
    public function getExpenses(string $boardId)
    {
        $query = <<<GRAPHQL
    query {
      folders (ids:"$boardId"){
              children{
                groups{
                    id
                    title
                }
              }
      }
    }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);
        return $response['data']['folders'][0]['children'][1]['groups'];
    }


    /**
     * Fetches the time tracking data for a specific item.
     *
     * @param string $itemId The ID of the item.
     * @return array The array of items with time tracking data.
     */
    public function getTimeTrackingItemsForItem($itemId)
    {
        $query = <<<GRAPHQL
    {
        items(ids: [$itemId]) {
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
    GRAPHQL;

        $response = $this->makeApiRequest($query);
        $item = $response['data']['items'][0] ?? null;

        if (!$item) {
            return [];
        }

        foreach ($item['column_values'] as $columnValue) {
            if (!empty($columnValue) && isset($columnValue['history'])) {
                foreach ($columnValue['history'] as $history) {
                    $results[] = [
                        'item_id' => intval($item['id']),
                        'id' => intval($history['id']),
                        'started_user_id' => intval($history['started_user_id']),
                        'started_at' => $history['started_at'],
                        'ended_at' => $history['ended_at'],
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Fetches the time tracking data for the board.
     *
     * @param string $boardId The ID of the board.
     * @return array The array of items with time tracking data.
     */
    public function getTimeTrackingItems(string $boardId): array
    {
        $allItems = [];
        $cursor = null;

        do {
            $cursorPart = $cursor ? "cursor: \"$cursor\"" : '';

            $query = <<<GRAPHQL
        {
            boards(ids: "$boardId") {
                items_page(limit: 500, $cursorPart) {
                    cursor
                    items {
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

            $response = $this->makeApiRequest($query);
            $page = $response['data']['boards'][0]['items_page'];

            foreach ($page['items'] as $_item) {
                foreach ($_item['column_values'] as $column_value) {
                    if (!empty($column_value) && isset($column_value['history'])) {
                        foreach ($column_value['history'] as $history) {
                            $allItems[] = array_merge(
                                [
                                    'item_id' => intval($_item['id']),
                                    'id' => intval($history['id']),
                                    'started_user_id' => intval($history['started_user_id']),
                                    'started_at' => $history['started_at'],
                                    'ended_at' => $history['ended_at'],
                                ]
                            );
                        }
                    }
                }
            }

            $cursor = $page['cursor'] ?? null;
        } while ($cursor);

        return $allItems;
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

            //CLIENTS
            $filtered_clients = array_filter($data, function ($item) {
                return is_null($item['parent']);
            });

            foreach ($filtered_clients as $item) {
                //template folders should be ignored
                if ($item['name'] === "1_Reference" || $item['name'] === "2_Admin") {
                    continue;
                }
                $client = new \stdClass();
                $client->id = intval($item['id']);
                $client->name = $item['name'];
                $clients[$item['id']] = $client;
            }

            //PROJECTS
            $filtered_projects = array_filter($data, function ($item) {
                return !is_null($item['parent']) && !empty($item['children']);
            });

            foreach ($filtered_projects as $item) {
                $project = new \stdClass();
                $project->id = intval($item['id']);
                $project->name = $item['name'];
                $project->client_id = intval($item['parent']['id']);
                $project->time_board_id = null;
                $project->expenses_board_id = null;

                foreach ($item['children'] as $child) {
                    $childName = strtolower($child['name']);

                    if (str_starts_with($childName, '1_time')) {
                        $project->time_board_id = intval($child['id']);
                    }

                    if (str_starts_with($childName, '2_expenses')) {
                        $project->expenses_board_id = intval($child['id']);
                    }
                }

                $projects[$item['id']] = $project;
            }
            $page++;
        } while (!empty($data));

        $data = new \stdClass();
        $data->clients = $clients;
        $data->projects = $projects;
        return $data;
    }

    /**
     * Fetches the folders name
     *
     * @param string $folderId The ID of the Folder.
     * @return string
     */
    public function getFoldername(string $folderId)
    {
        $query = <<<GRAPHQL
    query {
      folders (ids:"$folderId"){
            name
      }
    }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['folders'][0]['name'];
    }

    /**
     * @param string $boardId The ID of the Board.
     * @return string
     */
    public function getWebhooksForBoard(string $boardId)
    {
        $query = <<<GRAPHQL
        query {
          webhooks(board_id: $boardId){
            id
            event
          }
        }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return $response['data']['webhooks'];
    }

    public function getFolderParentId(string $folderId)
    {
        $query = <<<GRAPHQL
    query {
      folders (ids:"$folderId"){
        parent {
          id
        }
      }
    }
GRAPHQL;

        // Define the variables to pass into the query
        $response = $this->makeApiRequest($query);

        return is_null($response['data']['folders'][0]['parent']) ? null : $response['data']['folders'][0]['parent']['id'];
    }

    /**
     * @param $item_id
     * @param $projectNr
     * @return array|null
     */
    public function setProjectNumber($item_id, $projectNr)
    {
        $query = <<<GRAPHQL
        mutation {
            change_column_value (board_id: 9370542454, item_id: $item_id, column_id: "numeric_mkrwataq", value: "$projectNr") {
             id
            }
        }
GRAPHQL;
        return $this->makeApiRequest($query);
    }


    /**
     * @param $item_id
     * @param $projectNr
     * @return array|null
     */
    public function setBoardName($board_id, $new_name)
    {
        $query = <<<GRAPHQL
        mutation {
            update_board (
                board_id: $board_id,
                board_attribute: name,
                new_value: "$new_name"
            )
        }
GRAPHQL;
        return $this->makeApiRequest($query);
    }

    /**
     * Fetches the details of a specific board
     *
     * @return array The array of the board objects.
     */
    public function getProjectBoard()
    {
        $allItems = [];
        $cursor = null;

        do {
            $cursorPart = $cursor ? "cursor: \"$cursor\"" : '';

            $query = <<<GRAPHQL
        query {
            boards(ids: "9370542454") {
                    items_page(limit:500 cursor: null){
                        items{
                            id
                            name
                        }
                        cursor
                  }
            }
        }
GRAPHQL;

            $response = $this->makeApiRequest($query);
            $page = $response['data']['boards'][0]['items_page'];

            $items = $page['items'] ?? [];
            $allItems = array_merge($allItems, $items);
            $cursor = $page['cursor'] ?? null;

        } while ($cursor);

        return $allItems;
    }

    /**
     * Fetches the last Items project name
     *
     * @return array The array of the board objects.
     */
    public function getProjectBoardLastItemProjectName()
    {
        $projects = $this->getProjectBoard();
        $last_item = end($projects);
        $last_item_id = $last_item["id"];

        $query = <<<GRAPHQL
                query {
                    items(ids: "$last_item_id") {
                        name
                        column_values (ids: "numeric_mkrwataq"){
                            value
                        }
                    }
                }
GRAPHQL;

        $response = $this->makeApiRequest($query);
        $project_number = $response['data']['items'][0]['column_values'][0]['value'];
        $project_number = json_decode($project_number);

        $project_name = $response['data']['items'][0]['name'];

        return "PD" . date('y') . "_" . str_pad($project_number, 4, "0", STR_PAD_LEFT) . " " . $project_name;
    }


    /**
     * Update status of the items
     *
     * @return void.
     */
    public function updateTaskStatus(string $status, array $items): void
    {
        $statusMap = [
            'To Be Invoiced' => 3,
            'Invoiced' => 4,
        ];

        if (!isset($statusMap[$status])) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }

        $statusId = $statusMap[$status];

        foreach ($items as $index => $item) {
            if (!isset($item['item_id'], $item['board_id'])) {
                throw new \InvalidArgumentException("Missing argument");
            }

            $query = <<<GRAPHQL
                mutation {
                  change_simple_column_value (item_id:"{$item['item_id']}", board_id: "{$item['board_id']}", column_id:"status", value: "{$statusId}") {
                    id
                  }
                }
            GRAPHQL;

            // Define the variables to pass into the query
            try {
                $this->makeApiRequest($query);
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
    }
}
