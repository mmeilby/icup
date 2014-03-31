<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SelectController extends Controller
{
    /**
     * @Route("/tmnt/sel", name="_tournament_select")
     * @Template("ICupPublicSiteBundle:Tournament:select.html.twig")
     */
    public function selectAction()
    {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $tournaments = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->findAll();
        $tournamentList = array();
        foreach ($tournaments as $tournament) {
            $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'enrolled' => 0);
        }

        return array('tournaments' => $tournamentList);
    }
}
