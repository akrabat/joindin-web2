<?php
namespace Joindin\Model\Db;

use  \Joindin\Service\Cache as CacheService;

class User
{
    protected $keyName = 'users';
    protected $cache;

    public function __construct($keyPrefix)
    {
        $this->cache = new CacheService($keyPrefix);
    }

    public function load($uri)
    {
        $data = $this->cache->load('users', 'uri', $uri);
        return $data;
    }

    public function saveSlugToDatabase($user)
    {
        $data = array(
            'uri'  => $user->getUri(),
            'username' => $user->getUsername(),
            'slug' => $user->getSlug(),
            'verbose_uri'  => $user->getVerboseUri()
        );

        $savedUser = $this->load($user->getUri());
        if ($savedUser) {
            // user is already known - update this record
            $data = array_merge($savedUser, $data);
        }

        $this->cache->save('users', $data, 'slug', $user->getSlug());
        return $this->cache->save('users', $data, 'uri', $user->getUri());
    }

    public function getUriFor($slug)
    {
        $data = $this->cache->load('users', 'slug', $slug);
        return $data['uri'];
    }
}
