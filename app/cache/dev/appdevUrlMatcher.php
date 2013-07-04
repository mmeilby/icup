<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * appdevUrlMatcher
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appdevUrlMatcher extends Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($pathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($pathinfo);

        if (0 === strpos($pathinfo, '/bundles/icuppublicsite/css/e81fe90')) {
            // _assetic_e81fe90
            if ($pathinfo === '/bundles/icuppublicsite/css/e81fe90.css') {
                return array (  '_controller' => 'assetic.controller:render',  'name' => 'e81fe90',  'pos' => NULL,  '_format' => 'css',  '_route' => '_assetic_e81fe90',);
            }

            // _assetic_e81fe90_0
            if ($pathinfo === '/bundles/icuppublicsite/css/e81fe90_part_1_basic_1.css') {
                return array (  '_controller' => 'assetic.controller:render',  'name' => 'e81fe90',  'pos' => 0,  '_format' => 'css',  '_route' => '_assetic_e81fe90_0',);
            }

        }

        // _welcome
        if (rtrim($pathinfo, '/') === '') {
            if (substr($pathinfo, -1) !== '/') {
                return $this->redirect($pathinfo.'/', '_welcome');
            }

            return array (  '_controller' => 'ICupPublicSiteBundleDefault:category',  '_route' => '_welcome',);
        }

        if (0 === strpos($pathinfo, '/_')) {
            // _wdt
            if (0 === strpos($pathinfo, '/_wdt') && preg_match('#^/_wdt/(?P<token>[^/]++)$#s', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, array('_route' => '_wdt')), array (  '_controller' => 'web_profiler.controller.profiler:toolbarAction',));
            }

            if (0 === strpos($pathinfo, '/_profiler')) {
                // _profiler_home
                if (rtrim($pathinfo, '/') === '/_profiler') {
                    if (substr($pathinfo, -1) !== '/') {
                        return $this->redirect($pathinfo.'/', '_profiler_home');
                    }

                    return array (  '_controller' => 'web_profiler.controller.profiler:homeAction',  '_route' => '_profiler_home',);
                }

                if (0 === strpos($pathinfo, '/_profiler/search')) {
                    // _profiler_search
                    if ($pathinfo === '/_profiler/search') {
                        return array (  '_controller' => 'web_profiler.controller.profiler:searchAction',  '_route' => '_profiler_search',);
                    }

                    // _profiler_search_bar
                    if ($pathinfo === '/_profiler/search_bar') {
                        return array (  '_controller' => 'web_profiler.controller.profiler:searchBarAction',  '_route' => '_profiler_search_bar',);
                    }

                }

                // _profiler_purge
                if ($pathinfo === '/_profiler/purge') {
                    return array (  '_controller' => 'web_profiler.controller.profiler:purgeAction',  '_route' => '_profiler_purge',);
                }

                if (0 === strpos($pathinfo, '/_profiler/i')) {
                    // _profiler_info
                    if (0 === strpos($pathinfo, '/_profiler/info') && preg_match('#^/_profiler/info/(?P<about>[^/]++)$#s', $pathinfo, $matches)) {
                        return $this->mergeDefaults(array_replace($matches, array('_route' => '_profiler_info')), array (  '_controller' => 'web_profiler.controller.profiler:infoAction',));
                    }

                    // _profiler_import
                    if ($pathinfo === '/_profiler/import') {
                        return array (  '_controller' => 'web_profiler.controller.profiler:importAction',  '_route' => '_profiler_import',);
                    }

                }

                // _profiler_export
                if (0 === strpos($pathinfo, '/_profiler/export') && preg_match('#^/_profiler/export/(?P<token>[^/\\.]++)\\.txt$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => '_profiler_export')), array (  '_controller' => 'web_profiler.controller.profiler:exportAction',));
                }

                // _profiler_phpinfo
                if ($pathinfo === '/_profiler/phpinfo') {
                    return array (  '_controller' => 'web_profiler.controller.profiler:phpinfoAction',  '_route' => '_profiler_phpinfo',);
                }

                // _profiler_search_results
                if (preg_match('#^/_profiler/(?P<token>[^/]++)/search/results$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => '_profiler_search_results')), array (  '_controller' => 'web_profiler.controller.profiler:searchResultsAction',));
                }

                // _profiler
                if (preg_match('#^/_profiler/(?P<token>[^/]++)$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => '_profiler')), array (  '_controller' => 'web_profiler.controller.profiler:panelAction',));
                }

                // _profiler_router
                if (preg_match('#^/_profiler/(?P<token>[^/]++)/router$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => '_profiler_router')), array (  '_controller' => 'web_profiler.controller.router:panelAction',));
                }

                // _profiler_exception
                if (preg_match('#^/_profiler/(?P<token>[^/]++)/exception$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => '_profiler_exception')), array (  '_controller' => 'web_profiler.controller.exception:showAction',));
                }

                // _profiler_exception_css
                if (preg_match('#^/_profiler/(?P<token>[^/]++)/exception\\.css$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => '_profiler_exception_css')), array (  '_controller' => 'web_profiler.controller.exception:cssAction',));
                }

            }

            if (0 === strpos($pathinfo, '/_configurator')) {
                // _configurator_home
                if (rtrim($pathinfo, '/') === '/_configurator') {
                    if (substr($pathinfo, -1) !== '/') {
                        return $this->redirect($pathinfo.'/', '_configurator_home');
                    }

                    return array (  '_controller' => 'Sensio\\Bundle\\DistributionBundle\\Controller\\ConfiguratorController::checkAction',  '_route' => '_configurator_home',);
                }

                // _configurator_step
                if (0 === strpos($pathinfo, '/_configurator/step') && preg_match('#^/_configurator/step/(?P<index>[^/]++)$#s', $pathinfo, $matches)) {
                    return $this->mergeDefaults(array_replace($matches, array('_route' => '_configurator_step')), array (  '_controller' => 'Sensio\\Bundle\\DistributionBundle\\Controller\\ConfiguratorController::stepAction',));
                }

                // _configurator_final
                if ($pathinfo === '/_configurator/final') {
                    return array (  '_controller' => 'Sensio\\Bundle\\DistributionBundle\\Controller\\ConfiguratorController::finalAction',  '_route' => '_configurator_final',);
                }

            }

        }

        // _showcategory
        if (0 === strpos($pathinfo, '/category') && preg_match('#^/category/(?P<categoryid>[^/]++)$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => '_showcategory')), array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\CategoryController::listAction',));
        }

        if (0 === strpos($pathinfo, '/init')) {
            // icup_publicsite_default_index
            if ($pathinfo === '/init') {
                return array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\DefaultController::indexAction',  '_route' => 'icup_publicsite_default_index',);
            }

            // icup_publicsite_default_index2
            if ($pathinfo === '/init2') {
                return array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\DefaultController::index2Action',  '_route' => 'icup_publicsite_default_index2',);
            }

        }

        if (0 === strpos($pathinfo, '/edit')) {
            // _editmatch
            if (preg_match('#^/edit/(?P<playgroundid>[^/]++)/(?P<date>[^/]++)$#s', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, array('_route' => '_editmatch')), array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\EditMatchController::listAction',));
            }

            // _editmatchpost
            if ($pathinfo === '/edit') {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not__editmatchpost;
                }

                return array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\EditMatchController::postAction',  '_route' => '_editmatchpost',);
            }
            not__editmatchpost:

        }

        if (0 === strpos($pathinfo, '/playground')) {
            // _showplayground
            if (preg_match('#^/playground/(?P<playgroundid>[^/]++)/(?P<groupid>[^/]++)$#s', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, array('_route' => '_showplayground')), array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\PlaygroundController::listAction',));
            }

            // _showplayground_full
            if (preg_match('#^/playground/(?P<playgroundid>[^/]++)$#s', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, array('_route' => '_showplayground_full')), array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\PlaygroundController::listAllAction',));
            }

        }

        if (0 === strpos($pathinfo, '/t')) {
            // _showteam
            if (0 === strpos($pathinfo, '/team') && preg_match('#^/team/(?P<teamid>[^/]++)/(?P<groupid>[^/]++)$#s', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, array('_route' => '_showteam')), array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\TeamController::listAction',));
            }

            // _showtournament
            if ($pathinfo === '/tournament') {
                return array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\TournamentController::listAction',  '_route' => '_showtournament',);
            }

        }

        // contact
        if (preg_match('#^/(?P<_locale>en|da|it)/switch$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'contact')), array (  '_controller' => 'ICup\\Bundle\\PublicSiteBundle\\Controller\\DefaultController::switchAction',  '_locale' => 'en',));
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
