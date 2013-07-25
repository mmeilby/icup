<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CategoryController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/ctgr/{categoryid}", name="_showcategory")
     * @Template("ICupPublicSiteBundle:Tournament:category.html.twig")
     */
    public function listAction($tournament, $categoryid)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $groupList = array();
        $championList = array();

        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryid);

        $qb = $em->createQuery("select g ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where g.pid=:category ".
                               "order by g.classification desc, g.name asc");
        $qb->setParameter('category', $category->getId());
        $groups = $qb->getResult();
        
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($this, $group->getId());
            if ($group->getClassification() == 0) {
                $groupList[$group->getName()] = array('group' => $group, 'teams' => $teamsList);
            }
            else {
                $championList[$group->getClassification()] = array('group' => $group, 'teams' => $teamsList);
            }
        }
        return array('tournament' => $tournament, 'category' => $category, 'grouplist' => $groupList, 'championlist' => $championList);
    }
}
