<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RestClubController extends Controller
{
    /**
     * Get the club identified by club id
     * @Route("/rest/admin/club/get/{clubid}", name="_rest_get_club", options={"expose"=true}))
     */
    public function restGetClubsAction($clubid)
    {
        // Validate that user is logged in...
        $this->get('util')->getCurrentUser();
        
        $club = $this->get('entity')->getClubById($clubid);
        return new Response(json_encode(
            array('id' => $club->getId(),
                  'name' => $club->getname(),
                  'country' => $club->getCountry())
                ));
    }
    
    /**
     * List the clubs available for a country matching the pattern given
     * Arguments:
     *   country: countrycode
     *   pattern: stringpattern with % for wildcard
     * @Route("/rest/club/list", name="_rest_list_clubs")
     */
    public function restListClubsAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        // Validate that user is logged in...
        $utilService->getCurrentUser();
        $request = $this->getRequest();
        $pattern = $request->get('pattern', '%');
        $countryCode = $request->get('country', '');
        $clubs = $this->get('logic')->listClubsByPattern($pattern, $countryCode);
        $result = array();
        foreach ($clubs as $club) {
            $country = $this->get('translator')->trans($club->getCountry(), array(), 'lang');
            $result[] = array('id' => $club->getId(), 'name' => $club->getname(), 'country' => $country);
            if (count($result) > 3) break;
        }
        return new Response(json_encode($result));
    }
    
}
