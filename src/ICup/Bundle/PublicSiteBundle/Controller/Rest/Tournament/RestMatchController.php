<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;

class RestMatchController extends Controller
{
    /**
     * Get the playground identified by playground id
     * @Route("/rest/match/get/{tournamentid}/{matchno}", name="_rest_get_match", options={"expose"=true})
     */
    public function restGetMatchAction($tournamentid, $matchno)
    {
        /* @var $match Match */
        $match = $this->get('match')->getMatchByNo($tournamentid, $matchno);
        $hometeamid = $this->get('match')->getMatchHomeTeam($match->getId());
        /* @var $hometeam Team */
        $hometeam = $this->get('entity')->getTeamById($hometeamid);
        $awayteamid = $this->get('match')->getMatchAwayTeam($match->getId());
        /* @var $awayteam Team */
        $awayteam = $this->get('entity')->getTeamById($awayteamid);
        return new Response(json_encode(
            array(
                'home' => array(
                    'id' => $hometeam->getId(),
                    'name' => $hometeam->getName()
                ),
                'away' => array(
                    'id' => $awayteam->getId(),
                    'name' => $awayteam->getName()
                )
            )
        ));
    }
}
