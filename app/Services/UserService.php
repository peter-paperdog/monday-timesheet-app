<?php

namespace App\Services;

use App\Classes\User;
use Illuminate\Support\Facades\Cache;

class UserService
{
    private $mondayService;
    private $users = [];

    public function __construct(MondayService $mondayService)
    {
        $this->mondayService = $mondayService;
        $this->loadUsers();
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        return $this->users;
    }

    // name email
    public function getUserBy($property, $value)
    {
        foreach ($this->users as $userId => $user) {
            if (isset($user[$property]) && $user[$property] == $value) {
                return new User($userId, $user['name'], $user['email']);
            }
        }
        return null; // Return null if no user is found
    }


    private function loadUsers()
    {
        $usersMonday = Cache::remember('users', 20, function () {
            return $this->mondayService->getUsers();
        });

        foreach ($usersMonday as $user) {
            $this->users[$user['id']] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ];
        }
    }
}
