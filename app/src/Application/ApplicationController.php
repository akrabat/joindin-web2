<?php
namespace Application;

use Event\EventDb;
use Event\EventApi;
use User\UserDb;
use User\UserApi;

class ApplicationController extends BaseController
{
    protected function defineRoutes(\Slim\App $app)
    {
        $app->get('/', array($this, 'index'));
        $app->get('/apps', array($this, 'apps'))->setName('apps');
        $app->get('/about', array($this, 'about'))->setName('about');
        $app->get('/not-allowed', array($this, 'notAllowed'))->setName('not-allowed');
    }

    public function index($request, $response)
    {
        $page = ((int)$request->getParam('page') === 0)
            ? 1
            : $request->getPara('page');

        $perPage = 6;
        $start = ($page -1) * $perPage;

        $eventApi = $this->getEventApi();
        $hotEvents = $eventApi->getEvents($perPage, $start, 'hot');
        $cfpEvents = $eventApi->getEvents(10, 0, 'cfp', true);

        $this->render(
            $response,
            'Application/index.html.twig',
            array(
                'events' => $hotEvents,
                'cfp_events' => $cfpEvents,
                'page' => $page,
            )
        );
    }

    public function apps($request, $response)
    {
        $this->render($response, 'Application/apps.html.twig');
    }

    /**
     * Render the about page
     */
    public function about($request, $response)
    {
        $this->render($response, 'Application/about.html.twig');
    }


    /**
     * Render the notAllowed page
     */
    public function notAllowed($request, $response)
    {
        $this->render($response, 'Application/not-allowed.html.twig');
    }

    /**
     * @return CacheService
     */
    private function getCache()
    {
        $keyPrefix = $this->cfg['redisKeyPrefix'];
        return new CacheService($keyPrefix);
    }

    /**
     * @return EventApi
     */
    private function getEventApi()
    {
        $eventDb = new EventDb($this->getCache());
        return new EventApi($this->cfg, $this->accessToken, $eventDb, $this->getUserApi());
    }

    /**
     * @return UserApi
     */
    private function getUserApi()
    {
        $userDb = new UserDb($this->getCache());
        return new UserApi($this->cfg, $this->accessToken, $userDb);
    }
}
