<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditClubController extends Controller
{
    /**
     * List the clubs available for a tournament
     * @Route("/edit/club/list/{tournamentid}", name="_edit_club_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listclubs.html.twig")
     */
    public function listClubsAction($tournamentid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        
        $qb = $em->createQuery("select c ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category cat, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where cat.pid=:tournament and ".
                                     "g.pid=cat.id and ".
                                     "g.classification=0 and ".
                                     "o.pid=g.id and ".
                                     "o.cid=t.id and ".
                                     "t.pid=c.id ".
                               "order by c.country asc, c.name asc");
        $qb->setParameter('tournament', $tournamentid);
        $clubs = $qb->getResult();

        $teamList = array();
        foreach ($clubs as $club) {
            $country = $club->getCountry();
            $teamList[$country][$club->getId()] = $club;
        }

        $teamcount = count($teamList, COUNT_RECURSIVE)/3;
        $teamColumns = array();
        $ccount = 0;
        $column = 0;
        foreach ($teamList as $country => $clubs) {
            $teamColumns[$column][] = array($country => $clubs);
            $ccount += count($clubs) + 1;
            if ($ccount > $teamcount && $column < 2) {
                $column++;
                $ccount = 0;
            }
        }
        return array('tournament' => $tournament, 'teams' => $teamColumns);
    }
}
