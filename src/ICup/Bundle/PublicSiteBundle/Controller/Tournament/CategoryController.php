<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CategoryController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/ctgr/{categoryid}/prm", name="_showcategory")
     * @Template("ICupPublicSiteBundle:Tournament:category.html.twig")
     */
    public function listAction($tournament, $categoryid)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryid);

        $qb = $em->createQuery("select g ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where g.pid=:category and g.classification = 0 ".
                               "order by g.name asc");
        $qb->setParameter('category', $category->getId());
        $groups = $qb->getResult();
        
        $groupList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($this, $group->getId());
            $groupList[$group->getName()] = array('group' => $group, 'teams' => $teamsList);
        }
        return array('tournament' => $tournament, 'category' => $category, 'grouplist' => $groupList);
    }
    
    /**
     * @Route("/tmnt/{tournament}/ctgr/{categoryid}/clss", name="_showcategory_classification")
     * @Template("ICupPublicSiteBundle:Tournament:category_class.html.twig")
     */
    public function listClassAction($tournament, $categoryid)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryid);

        $qb = $em->createQuery("select g ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where g.pid=:category and g.classification > 0 and g.classification < 8 ".
                               "order by g.classification asc, g.name asc");
        $qb->setParameter('category', $category->getId());
        $groups = $qb->getResult();
        
        $groupList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($this, $group->getId());
            $groupList[$group->getId()] = array('group' => $group, 'teams' => $teamsList);
        }
        return array('tournament' => $tournament, 'category' => $category, 'grouplist' => $groupList);
    }
    
    /**
     * @Route("/tmnt/{tournament}/ctgr/{categoryid}/fnls", name="_showcategory_finals")
     * @Template("ICupPublicSiteBundle:Tournament:category_finals.html.twig")
     */
    public function listFinalsAction($tournament, $categoryid)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryid);

        $qb = $em->createQuery("select g ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where g.pid=:category and g.classification > 7 ".
                               "order by g.classification desc, g.name asc");
        $qb->setParameter('category', $category->getId());
        $groups = $qb->getResult();
        
        $champions = array('SA'=>null,'SB'=>null,'SC'=>null,'SD'=>null,'FA'=>null,'FB'=>null,'FC'=>null,'FD'=>null);
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($this, $group->getId());
            foreach ($teamsList as $team) {
                $keys = array();
                switch ($group->getClassification()) {
                    case 10:
                        /* finals */
                        $keys = array('FA', 'FB');
                        break;
                    case 9:
                        /* 3/4 position */
                        $keys = array('FC', 'FD');
                        break;
                    case 8:
                        /* semifinals */
                        $keys = array('SA', 'SB', 'SC', 'SD');
                        break;
                }
                foreach ($keys as $key) {
                    if ($champions[$key] == null) {
                        $champions[$key] = $team;
                        break;
                    }
                }
            }
        }
        return array('tournament' => $tournament, 'category' => $category, 'champions' => $champions);
    }
}
