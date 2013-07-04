<?php
namespace ICup\Bundle\PublicSiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TournamentController extends Controller
{
    /**
     * @Route("/tournament", name="_showtournament")
     * @Template("ICupPublicSiteBundle:Default:tournament.html.twig")
     */
    public function listAction()
    {
        $tournamentId = DefaultController::getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournament->getId()));

        $qb = $em->createQuery("select p ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site s, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground p ".
                               "where s.pid=:tournament and ".
                                     "p.pid=s.id ".
                               "order by p.no");
        $qb->setParameter('tournament', $tournamentId);
        $playgrounds = $qb->getResult();

        return array('tournament' => $tournament, 'categories' => $categories, 'playgrounds' => $playgrounds, 'imagepath' => DefaultController::getImagePath($this));
    }
}
