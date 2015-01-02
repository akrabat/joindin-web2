<?php

namespace User\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

class ProfileLinks extends Twig_Extension
{
    protected $userApi;
    
    public function __construct($config)
    {
        $accessToken = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null;
        $userApi = new \User\UserApi(
            $config,
            $accessToken,
            new \User\UserDb(new \Application\CacheService($config['redisKeyPrefix']))
        );

        $this->userApi = $userApi;
    }

    public function getName()
    {
        return 'userprofilelinks';
    }

    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'profileLink',
                [$this, 'profileLink'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        );
    }

    /**
     * Wrap $displayName in a link to the user's profile
     *
     * @param  Twig_Environment $environment
     * @param  string $displayName
     * @param  string $uri
     * @return string
     */
    public function profileLink($environment, $displayName, $uri = null)
    {
        $displayName = twig_escape_filter($environment, $displayName);
        if (!$uri) {
            return $displayName;
        }

        $username = $this->userApi->getUsername($uri);
        if ($username) {
            $url = $environment->getExtension('slim')->urlFor('user-profile', ['username' => $username]);
            return "<a href='$url'>$displayName</a>";
        }
        return $displayName;
    }
}
