<?php

namespace App\Classes;

use App\Services\MondayService;
use DateTime;

class User
{
    private $id;
    private $name;
    private $email;


    /**
     * Class constructor.
     *
     * @param int $id The user ID.
     * @param string $name The user's name.
     * @param string $email The user's email address.
     * @return void
     */
    public function __construct($id, $name, $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getTimeTrackingItems()
    {
        $mondayService = new MondayService();

        return array_filter($mondayService->getTimeTrackingItems(), function ($item) {
            return $item['started_user_id'] === $this->id;
        });
    }

    public function getTimeTrackingItemsBetween(DateTime $start, DateTime $end)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        // Fetch all time tracking items for the user
        $items = $this->getTimeTrackingItems();

        // Filter the items based on the provided start and end dates
        $filteredItems = array_filter($items, function ($item) use ($start, $end) {
            // Convert the 'started_at' and 'ended_at' to DateTime objects
            $startedAt = new DateTime($item['started_at']);
            $endedAt = new DateTime($item['ended_at']);
            // Check if the item falls within the specified date range
            return $startedAt >= $start && $endedAt <= $end;
        });

        return $filteredItems;
    }
}
