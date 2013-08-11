<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TournamentController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/ctgr", name="_tournament_categories")
     * @Template("ICupPublicSiteBundle:Tournament:categories.html.twig")
     */
    public function listCategoriesAction($tournament)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournament->getId()), array('classification' => 'asc', 'gender' => 'asc'));
        $classMap = array();
        $categoryMap = array();
        foreach ($categories as $category) {
            $classMap[$category->getClassification()] = $category->getClassification();
            $cls = $category->getGender() . $category->getClassification();
            $categoryMap[$cls][] = $category;
        }
        return array('tournament' => $tournament, 'classifications' => $classMap, 'categories' => $categoryMap);
    }

    /**
     * @Route("/tmnt/{tournament}/pgrnd", name="_tournament_playgrounds")
     * @Template("ICupPublicSiteBundle:Tournament:playgrounds.html.twig")
     */
    public function listPlaygroundsAction($tournament)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        
        $qb = $em->createQuery("select p.id,p.name,s.name as site ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site s, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground p ".
                               "where s.pid=:tournament and ".
                                     "p.pid=s.id ".
                               "order by p.no");
        $qb->setParameter('tournament', $tournamentId);
        $playgrounds = $qb->getResult();

        $playgroundList = array();
        foreach ($playgrounds as $playground) {
            $site = $playground['site'];
            $playgroundList[$site][$playground['id']] = $playground['name'];
        }
        return array('tournament' => $tournament, 'playgrounds' => $playgroundList);
    }

    /**
     * @Route("/tmnt/{tournament}/clb", name="_tournament_clubs")
     * @Template("ICupPublicSiteBundle:Tournament:clubs.html.twig")
     */
    public function listClubsAction($tournament)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
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
        $qb->setParameter('tournament', $tournamentId);
        $clubs = $qb->getResult();

        $teamList = array();
        foreach ($clubs as $club) {
            $country = $club->getCountry();
            $name = $club->getName();
            $teamList[$country][$club->getId()] = $name;
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
    
    /**
     * @Route("/tmnt/{tournament}/tms/{clubId}", name="_tournament_teams")
     * @Template("ICupPublicSiteBundle:Tournament:teams.html.twig")
     */
    public function listTeamsAction($tournament, $clubId)
    {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_icup'));
        }

        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournament->getId()), array('classification' => 'asc', 'gender' => 'asc'));
        $categoryList = array();
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = $category;
        }

        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                            ->find($clubId);
        if ($club == null) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        
        $qb = $em->createQuery("select t.id, t.name, t.division, c.id as catid, c.name as category, c.classification, c.gender, g.id as groupid, g.name as grp ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c ".
                               "where t.pid=:club and ".
                                     "o.cid=t.id and ".
                                     "o.pid=g.id and ".
                                     "g.classification=0 and ".
                                     "g.pid=c.id ".
                               "order by c.gender asc, c.classification asc, t.division asc");
        $qb->setParameter('club', $club->getId());
        $teams = $qb->getResult();

        $teamList = array();
        foreach ($teams as $team) {
            $name = $team['name'];
            if ($team['division'] != '') {
                $name.= ' "'.$team['division'].'"';
            }
            $team['name'] = $name;
            $teamList[$team['catid']][$team['id']] = $team;
        }

        return array('tournament' => $tournament, 'club' => $club, 'teams' => $teamList, 'categories' => $categoryList);
    }
}
