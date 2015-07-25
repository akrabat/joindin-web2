<?php
namespace Application;

use Slim\App;
use Twig_Error_Runtime;

abstract class BaseController
{
    /** @var Slim */
    protected $application = null;

    protected $accessToken;
    protected $cfg;

    public function __construct(App $app)
    {
        $this->application = $app;
        $this->defineRoutes($app);
        $this->cfg = $this->getConfig();

        $this->accessToken = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null;
    }

    private function getConfig()
    {
        $config = $this->application->settings['custom'];
        return $config;
    }

    protected function render($response, $template, $data = array(), $status = null)
    {
        try {
            if ($status) {
                $response = $response->withStatus($status);
            }
            return $this->application->view->render($response, $template, $data);
        } catch (Twig_Error_Runtime $e) {
            $response = $response->withStatus(500);
            $this->application->view->render(
                $response,
                'Error/app_load_error.html.twig',
                array(
                    'message' => sprintf(
                        'An exception has been thrown during the rendering of a template ("%s").',
                        $e->getMessage()
                    ),
                    -1,
                    null,
                    $e
                )
            );
        }
    }

    abstract protected function defineRoutes(App $app);
}
