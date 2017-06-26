<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Doctrine\Country controller.
 *
 * @Route("/rest/country")
 */
class RestCountryController extends Controller
{
    /**
     * List all valid countries
     * @Route("/list", name="_rest_list_countries", options={"expose"=true})
     * @Method("GET")
     * @return JsonResponse
     */
    public function indexAction()
    {
        $countries = array();
        foreach ($this->get('util')->getCountries() as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort($countries);
        return new JsonResponse($countries);
    }
}

