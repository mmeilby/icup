<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OverviewController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/vw", name="_tournament_overview")
     * @Template("ICupPublicSiteBundle:Tournament:overview.html.twig")
     */
    public function overviewAction($tournament)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $newsStream = array(
            array(
                'date' => time(),
                'text' => 'TEKNOELETTRONICA TERAMO disqualified due to use of players without license.',
                'path' => $this->generateUrl('_tournament_overview', array('tournament' => $tournament->getKey()))
            ),
            array(
                'date' => time(),
                'text' => 'Dimitri Populos, SPE STROVOLOU, male U18 received red card for improper act to game officials.',
                'path' => $this->generateUrl('_tournament_overview', array('tournament' => $tournament->getKey()))
            )
        );
        $teaserList = array(
            array(
                'titletext' => 'Gruppe resultater',
                'text' => 'Se sammensætningen af puljerne i en gruppe eller følg et hold fra gruppen.',
                'path' => $this->generateUrl('_tournament_categories', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'Bane oversigt',
                'text' => 'Se resultater fra banerne eller få overblik over kampplanen.',
                'path' => $this->generateUrl('_tournament_playgrounds', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'Deltagende hold',
                'text' => 'Find hold der deltager i turneringen og se deres resultater.',
                'path' => $this->generateUrl('_tournament_clubs', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'Vindere',
                'text' => 'Se alle vinderne fra denne turnering.',
                'path' => $this->generateUrl('_tournament_clubs', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'Hold statistik',
                'text' => 'Hvilket hold klarede sig bedst? Se hvem og mange andre spændende informationer om turneringen.',
                'path' => $this->generateUrl('_tournament_clubs', array('tournament' => $tournament->getKey()))
            )
        );
        return array('tournament' => $tournament, 'newsstream' => $newsStream, 'teaserlist' => $teaserList);
    }
}
