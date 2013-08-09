<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WinnersController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/wn", name="_tournament_winners")
     * @Template("ICupPublicSiteBundle:Tournament:winners.html.twig")
     */
    public function listAction($tournament)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $championList = array();

        $qb = $em->createQuery("select c.id as catid,c.name as category,c.gender,c.classification as class,g.id,g.classification ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where c.pid=:tournament and g.pid=c.id and g.classification >= :finals ".
                               "order by c.gender asc, c.classification asc, g.classification desc");
        $qb->setParameter('tournament', $tournament->getId());
        $qb->setParameter('finals', 9);
        $groups = $qb->getResult();
        
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($this, $group['id']);
            $championList[$group['catid']][] = array('group' => $group, 'teams' => $teamsList);
        }
        return array('tournament' => $tournament, 'championlist' => $championList);
    }
}
