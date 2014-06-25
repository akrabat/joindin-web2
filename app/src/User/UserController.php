<?php
namespace User;

use Application\BaseController;
use Application\CacheService;
use Talk\TalkDb;
use Talk\TalkApi;
use Event\EventDb;
use Event\EventApi;

class UserController extends BaseController
{
    /**
     * Routes implemented by this class
     *
     * @param \Slim $app Slim application instance
     *
     * @return void
     */
    protected function defineRoutes(\Slim\Slim $app)
    {
        $app->get('/user/logout', array($this, 'logout'))->name('user-logout');
        $app->map('/user/login', array($this, 'login'))->via('GET', 'POST')->name('user-login');
        $app->map('/user/register', array($this, 'register'))->via('GET', 'POST')->name('user-register');
        $app->map('/user/profile/:username', array($this, 'profile'))->via('GET', 'POST')->name('user-profile');
    }

    /**
     * Login page
     *
     * @return void
     */
    public function login()
    {
        $config = $this->application->config('oauth');
        $request = $this->application->request();

        $error = false;
        if ($request->isPost()) {
            // handle submission of login form
        
            // make a call to the api with granttype=password
            $username = $request->post('username');
            $password = $request->post('password');
            $redirect = $request->post('redirect');
            $clientId = $config['client_id'];

            $authApi = new AuthApi($this->cfg, $this->accessToken);
            $result = $authApi->login($username, $password, $clientId);

            if (false === $result) {
                $error = true;
            } else {
                session_regenerate_id(true);
                $_SESSION['access_token'] = $result->access_token;
                $this->accessToken = $_SESSION['access_token'];

                // now get users details
                $keyPrefix = $this->cfg['redis']['keyPrefix'];
                $cache = new CacheService($keyPrefix);
                $userApi = new UserApi($this->cfg, $this->accessToken, new UserDb($cache));
                $user = $userApi->getUser($result->user_uri);
                if ($user) {
                    $_SESSION['user'] = $user;
                    if (empty($redirect) || strpos($redirect, '/user/login') === 0) {
                        $this->application->redirect('/');
                    } else {
                        $this->application->redirect($redirect);
                    }
                } else {
                    unset($_SESSION['access_token']);
                }
            }
        }

        $this->render('User/login.html.twig', array('error' => $error));
    }

    /**
     * Registration page
     *
     * @return void
     */
    public function register()
    {
        // TODO: Implement!
        // This  method exists so that the named route can be defined
        // for the login page text.
        
        // For now, we just redirect to the legacy site
        header("Location: https://joind.in/user/register");
        exit;
    }

    /**
     * Log out
     *
     * @return void
     */
    public function logout()
    {
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
        if (isset($_SESSION['access_token'])) {
            unset($_SESSION['access_token']);
        }
        session_regenerate_id(true);
        $this->application->redirect('/');
    }

    /**
     * User profile page
     *
     * @param  string $username User's username
     * @return void
     */
    public function profile($username)
    {
        $keyPrefix = $this->cfg['redis']['keyPrefix'];
        $cache = new CacheService($keyPrefix);

        $userDb = new UserDb($cache);
        $userApi = new UserApi($this->cfg, $this->accessToken, $userDb);

        $userUri = $userDb->getUriFor($username);
        if ($userUri) {
            $user = $userApi->getUser($userUri);
        } else {
            $user = $userApi->getUserByUsername($username);
            if (!$user) {
                throw new \Slim_Exception_Pass('Page not found', 404);
            }
            $userDb->save($user);
        }

        $talkDb = new TalkDb($cache);
        $talkApi = new TalkApi($this->cfg, $this->accessToken, $talkDb);
        $talkCollection = $talkApi->getCollection($user->getTalksUri());

        $talks = false;
        $eventStubNames = array();
        if (isset($talkCollection['talks'])) {
            $talks = $talkCollection['talks'];

            // need the full web2 url for each talk - we can get this via the db
            $eventDb = new EventDb($cache);
            $eventApi = new EventApi($this->cfg, $this->accessToken, $eventDb);
            foreach ($talks as $talk) {
                $event = $eventApi->getEvent($talk->getEventUri());
                $eventDb->save($event);
                $events[$talk->getEventUri()] = $event;
            }
        }

        echo $this->render(
            'User/profile.html.twig',
            array(
                'user'   => $user,
                'talks'  => $talks,
                'events' => $events,
            )
        );
    }
}
