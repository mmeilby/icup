<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Host;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;

class HostTournamentController extends Controller
{
    /**
     * List the tournaments available for current editor
     * @Route("/host/list/tournaments", name="_host_list_tournaments")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listtournaments.html.twig")
     */
    public function listAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser($this);
            // Validate current user - is it an editor?
            $utilService->validateHostUser($this, $user);
            // Get the host from current user
            $hostid = $user->getPid();
            $host = $utilService->getHostById($this, $hostid);
            // Find list of tournaments for this host
            $tournaments = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                                ->findBy(array('pid' => $hostid), array('name' => 'asc'));
            return array('tournaments' => $tournaments, 'host' => $host);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }

    /**
     * List the clubs enrolled for a tournament
     * @Route("/host/list/clubs/{tournamentid}", name="_host_list_clubs")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listclubs.html.twig")
     */
    public function listClubsAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser($this);
            // Validate current user - is it an editor?
            $utilService->validateHostUser($this, $user);
            // Get the host from current user
            $hostid = $user->getPid();
            $host = $utilService->getHostById($this, $hostid);
            
            $tmnt = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                                ->find($tournamentid);
            if ($tmnt == null) {
                throw new ValidationException("badtournament.html.twig");
            }
            
            if ($tmnt->getPid() != $hostid) {
                throw new ValidationException("noteditoradmin.html.twig");
            }
            
            $qb = $em->createQuery("select clb as club, count(e) as enrolled ".
                                   "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                        "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club clb ".
                                   "where c.pid=:tournament and e.pid=c.id and e.cid=t.id and t.pid=clb.id ".
                                   "group by clb.id order by clb.country, clb.name");
            $qb->setParameter('tournament', $tmnt->getId());
            $clubs = $qb->getResult();

            $teamcount = 0;
            $teamList = array();
            foreach ($clubs as $clb) {
                $club = $clb['club'];
                $country = $club->getCountry();
                $teamList[$country][$club->getId()] = $clb;
                $teamcount++;
            }

            $teamcount /= 2;
            $teamColumns = array();
            $ccount = 0;
            $column = 0;
            foreach ($teamList as $country => $clubs) {
                $teamColumns[$column][] = array($country => $clubs);
                $ccount += count($clubs);
                if ($ccount > $teamcount && $column < 1) {
                    $column++;
                    $ccount = 0;
                }
            }
            return array('host' => $host, 'tournament' => $tmnt, 'teams' => $teamColumns);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
    
    /**
     * List the clubs by groups assigned in the category
     * @Route("/host/list/grps/{categoryid}", name="_host_list_groups")
     * @Template("ICupPublicSiteBundle:Host:listgroups.html.twig")
     */
    public function listByCategoryAction($categoryid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser($this);
            // Validate current user - is it an editor?
            $utilService->validateHostUser($this, $user);
            // Get the host from current user
            $hostid = $user->getPid();
            $host = $utilService->getHostById($this, $hostid);

            /* @var $category Category */
            $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                                ->find($categoryid);
            if ($category == null) {
                throw new ValidationException("badcategory.html.twig");
            }

            $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                                ->find($category->getPid());
            if ($tournament == null) {
                throw new ValidationException("badtournament.html.twig");
            }

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
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
    
}
