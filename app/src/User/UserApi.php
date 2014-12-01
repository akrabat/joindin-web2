<?php
namespace User;

use Application\BaseApi;

class UserApi extends BaseApi
{

    public function __construct($config, $accessToken, UserDb $userDb)
    {
        parent::__construct($config, $accessToken);
        $this->userDb = $userDb;
    }

    /**
     * Retrieve user information from the API
     *
     * @param  string $url User's URI
     * @return mixed       stdClass of user data or false
     */
    public function getUser($url)
    {
        $result = $this->apiGet($url, array('verbose'=>'yes'));

        if ($result) {
            $data = json_decode($result);
            if ($data) {
                if (isset($data->users) && isset($data->users[0])) {
                    $user = new UserEntity($data->users[0]);

                    return $user;
                }

            }
        }
        return false;
    }

    /**
     * Retrieve a user
     *
     * @param  string $username User's username
     * @return mixed            stdClass of user data or false
     */
    public function getUserByUsername($username)
    {
        $url = $this->baseApiUrl . '/v2.1/users';
        $result = $this->apiGet($url, ['username' => $username, 'verbose'=>'yes']);

        if ($result) {
            $data = json_decode($result);
            if ($data) {
                if (isset($data->users)) {
                    foreach ($data->users as $userData) {
                        if (strtolower($userData->username) == strtolower($username)) {
                            $user = new UserEntity($userData);
                            return $user;
                        }
                    }
                }
            }
        }
        return false;
    }
}
