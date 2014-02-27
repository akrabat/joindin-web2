<?php
namespace Joindin\View\Functions;

use Slim;

/**
 * A group of Twig functions for use in view templates
 *
 * @param  Twig_Environment $env
 * @param  Slim             $app
 * @return void
 */
function initialize(\Twig_Environment $env, Slim $app)
{
    $env->addFunction(new \Twig_SimpleFunction('urlFor', function ($routeName, $params=array()) use ($app) {
        $url = $app->urlFor($routeName, $params);
        return $url;
    }));
    
    $env->addFunction(new \Twig_SimpleFunction('hash', function ($value) {
        return md5($value);
    }));

    $env->addFunction(
        new \Twig_SimpleFunction('urlForTalk', function ($eventSlug, $talkSlug, $params = array()) use ($app) {
            return $app->urlFor('talk', array('eventSlug' => $eventSlug, 'talkSlug' => $talkSlug));
        })
    );

    $env->addFunction(new \Twig_SimpleFunction('talkHosts', function ($hosts) use ($app) {
        if (empty($hosts)) {
            return '';
        }
        foreach ($hosts as $host) {
            $url = $app->urlFor('user-profile', array('slug' => $host->getSlug()));
            $items[] = '<a href="' . $url . '">' . $host->getFullname() .'</a>';
        }

        $html = '<p class="hosts">Your host';
        $html .= count($items) == 1 ? '' : 's';
        $html .= ': ';
        $html .= implode(', ', $items);
        $html .= "</p>\n";
        return $html;
    }, array(
            'is_safe' => array('html')
    )));
}
