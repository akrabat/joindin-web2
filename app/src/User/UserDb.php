<?php
namespace User;

use Application\BaseDb;

class UserDb extends BaseDb
{
    protected $keyName = 'users';

    public function save($user)
    {
        $data = array(
            'uri'  => $user->getUri(),
            'username' => $user->getUsername(),
            'verbose_uri'  => $user->getVerboseUri()
        );

        $savedUser = $this->load('uri', $user->getUri());
        if ($savedUser) {
            // user is already known - update this record
            $data = array_merge($savedUser, $data);
        }

        $this->cache->save($this->keyName, $data, 'username', $user->getUsername());
        $this->cache->save($this->keyName, $data, 'uri', $user->getUri());
    }

    public function getUriFor($username)
    {
        $data = $this->cache->load('users', 'username', $username);
        return $data['uri'];
    }
}
