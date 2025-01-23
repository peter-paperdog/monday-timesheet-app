<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Cache;

class TimeSheetService
{
    private $ttl;
    private $date;
    private $mondayService;

    private $boards = [];
    private $boardItems = [];
    private $boardSubitems = [];
    private $history = [];

    public function __construct(MondayService $mondayService, DateTime $date = null, int $ttl = 10000)
    {
        $this->mondayService = $mondayService;
        $this->date = $date ?? new DateTime();
        $this->ttl = $ttl;

        $this->initializeWeekBounds();
        $this->loadBoards();
        $this->processBoards();
    }

    public function getBoards()
    {
        return $this->boards;
    }

    public function getBoardItems()
    {
        return $this->boardItems;
    }

    private function initializeWeekBounds()
    {
        /*$this->startOfWeek = clone $this->date;
        $this->startOfWeek->modify('Monday this week');

        $this->endOfWeek = clone $this->date;
        $this->endOfWeek->modify('Sunday this week');*/

        $this->startOfWeek = new DateTime('2024-12-09');
        $this->endOfWeek = new DateTime('2025-01-10');
    }

    private function loadBoards()
    {
        /*$boardsMonday = Cache::remember('boards_' . $this->startOfWeek->format('YW'), $this->ttl, function () {
            return $this->mondayService->getBoards();
        });*/

        $boardsMonday = $this->mondayService->getBoards();

        foreach ($boardsMonday as $board) {
            $boardName = str_replace('Subitems of ', '', $board['name']);
            if (!in_array($boardName, $this->boards)) {
                $this->boards[$board['id']] = $boardName;
            }
        }
    }

    private function processBoards()
    {
        foreach ($this->boards as $boardId => $boardName) {
            /*$boardData = Cache::remember('board_' . $this->startOfWeek->format('YW') . '_' . $boardId, $this->ttl, function () use ($boardId) {
                return $this->mondayService->getTimeTrackingDataForBoard($boardId);
            });*/
            $boardData = $this->mondayService->getTimeTrackingDataForBoard($boardId);

            foreach ($boardData['items_page']['items'] as $boardItem) {
                $this->processBoardItem($boardId, $boardItem);
            }

        }
    }

    private function processBoardItem($boardId, $boardItem)
    {
        $boardItemId = intval($boardItem['id']);

        foreach ($boardItem['column_values'] as $columnValue) {
            if (!empty($columnValue) && isset($columnValue['history'])) {
                $this->processHistory($boardId, $boardItemId, $columnValue['history']);
            }
        }

        $this->boardItems[$boardItemId] = [
            'name' => $boardItem['name'],
            'group' => $boardItem['group']['title'],
        ];

        $this->processSubitems($boardId, $boardItemId, $boardItem['subitems']);
    }

    private function processSubitems($boardId, $boardItemId, $subitems)
    {
        foreach ($subitems as $subitem) {
            if (!empty($subitem)) {
                $subitemId = $subitem['id'];
                $this->boardSubitems[$subitemId] = [
                    'board_id' => $boardId,
                    'board_item_id' => $boardItemId,
                    'name' => $subitem['name']
                ];

                foreach ($subitem['column_values'] as $columnValue) {
                    if (!empty($columnValue) && isset($columnValue['history'])) {
                        $this->processHistory($boardId, $boardItemId, $columnValue['history'], $subitemId);
                    }
                }
            }
        }
    }

    private function processHistory($boardId, $boardItemId, $history, $subitemId = null)
    {
        foreach ($history as $historyColumn) {
            $startedAt = new DateTime($historyColumn['started_at']);
            $endedAt = new DateTime($historyColumn['ended_at']);

            if ($startedAt >= $this->startOfWeek && $endedAt <= $this->endOfWeek) {
                $interval = $startedAt->diff($endedAt);
                $historyColumn['totalSeconds'] = $interval->h * 3600 + $interval->i * 60 + $interval->s;
                $historyColumn['started_user_id'] = intval($historyColumn['started_user_id']);

                $record = [
                    'board_id' => $boardId,
                    'board_item_id' => $boardItemId,
                ];

                if ($subitemId) {
                    $record['subitem_id'] = $subitemId;
                }

                $this->history[] = array_merge($record, $historyColumn);
            }
        }
    }

    public function getHistory()
    {
        return $this->history;
    }
}
