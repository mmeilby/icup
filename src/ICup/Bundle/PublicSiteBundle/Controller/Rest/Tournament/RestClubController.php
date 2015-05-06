<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RestClubController extends Controller
{
    /**
     * Get the club identified by club id
     * @Route("/rest/admin/club/get/{clubid}", name="_rest_get_club", options={"expose"=true})
     */
    public function restGetClubAction($clubid)
    {
        // Validate that user is logged in...
        $this->get('util')->getCurrentUser();
        
        $club = $this->get('entity')->getClubById($clubid);
        $country = $this->get('translator')->trans($club->getCountry(), array(), 'lang');
        return new Response(json_encode(
            array('id' => $club->getId(),
                  'name' => $club->getName(),
                  'country' => $country,
                  'country_code' => $club->getCountry())
                ));
    }
    
    /**
     * List the clubs available for a country matching the pattern given
     * Arguments:
     *   pattern: stringpattern with % for wildcard
     *   countrycode: countrycode like DNK, DEU, ITA
     * @Route("/rest/club/list/{countrycode}/{pattern}", name="_rest_list_clubs", options={"expose"=true})
     */
    public function restListClubsAction($pattern, $countrycode)
    {
        // Validate that user is logged in...
        $this->get('util')->getCurrentUser();

        $clubs = $this->get('logic')->listClubsByPattern($pattern, $countrycode);
        $result = array();
        foreach ($clubs as $club) {
            $country = $this->get('translator')->trans($club->getCountry(), array(), 'lang');
            $result[] = array(
                'id' => $club->getId(),
                'name' => $club->getname(),
                'country' => $country);
            if (count($result) > 3) {
                break;
            }
        }
        return new Response(json_encode($result));
    }
}
