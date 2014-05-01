<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Test;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Match as MatchForm;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditMatchController extends Controller
{
    /**
     * Add or update the match information
     * @Route("/edit/tmnt/{tournament}/new/match/{categoryId}", name="_newmatch")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:editmatch.html.twig")
     */
    public function newAction($tournament, $categoryId) {
        $this->get('util')->setTournamentKey($tournament);
        $tournamentId = $this->get('util')->getTournamentId();
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryId);

        $form = $this->makeform($tournamentId, $category);
        return array('form' => $form->createView(), 'tournament' => $tournament, 'category' => $category);
    }

    /**
     * Add or update the match information
     * @Route("/edit/tmnt/{tournament}/new/match/{categoryId}", name="_newmatchpost")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("POST")
     * @Template("ICupPublicSiteBundle:Edit:editmatch.html.twig")
     */
    public function newPostAction($tournament, $categoryId) {
        $this->get('util')->setTournamentKey($tournament);
        $tournamentId = $this->get('util')->getTournamentId();
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryId);

        $form = $this->makeform($tournamentId, $category);
        $request = $this->getRequest();
        $form->bind($request);
        if ($form->isValid()) {
            $formData = $form->getData();

            $matchrec = new Match();
            $matchrec->setDate($formData->getDate());
            $matchrec->setMatchno($formData->getMatchno());
            $matchrec->setPid($formData->getPid());
            $matchrec->setPlayground($formData->getPlayground());
            $matchrec->setTime($formData->getTime());
            $em->persist($matchrec);
            $em->flush();

            $resultreq = new MatchRelation();
            $resultreq->setPid($matchrec->getId());
            $resultreq->setCid($formData->getTeamA());
            $resultreq->setAwayteam(false);
            $resultreq->setScorevalid(false);
            $resultreq->setScore(0);
            $resultreq->setPoints(0);
            $em->persist($resultreq);

            $resultreq = new MatchRelation();
            $resultreq->setPid($matchrec->getId());
            $resultreq->setCid($formData->getTeamB());
            $resultreq->setAwayteam(true);
            $resultreq->setScorevalid(false);
            $resultreq->setScore(0);
            $resultreq->setPoints(0);
            $em->persist($resultreq);

            $em->flush();
        }
        return array('form' => $form->createView(), 'tournament' => $tournament, 'category' => $category);
    }
    
    private function makeform($tournamentId, $category) {
        $em = $this->getDoctrine()->getManager();
        $groups = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')
                            ->findBy(array('pid' => $category->getId()));
        $groupnames = array();
        foreach ($groups as $group) {
            $groupnames[$group->getId()] = $group->getName();
        }
        
        $qb = $em->createQuery("select p ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site s, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground p ".
                               "where s.pid=:tournament and ".
                                     "p.pid=s.id ".
                               "order by p.no");
        $qb->setParameter('tournament', $tournamentId);
        $playgrounds = $qb->getResult();
        $playgroundnames = array();
        foreach ($playgrounds as $playground) {
            $playgroundnames[$playground->getId()] = $playground->getName();
        }
        
        $qb = $em->createQuery("select t ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where g.pid=:category and ".
                                     "o.pid=g.id and ".
                                     "t.id=o.cid ".
                               "order by t.id");
        $qb->setParameter('category', $category->getId());
        $teams = $qb->getResult();
        $teamnames = array();
        foreach ($teams as $team) {
            $name = $team->getName();
            if ($team->getDivision() != '') {
                $name.= ' "'.$team->getDivision().'"';
            }
            $teamnames[$team->getId()] = $name;
        }

        $formData = new MatchForm();
        $formDef = $this->createFormBuilder($formData);
        $formDef->add('pid', 'choice', array('label' => 'Gruppe', 'required' => false, 'choices' => $groupnames, 'empty_value' => 'Vælg...'));
        $formDef->add('matchno', 'text', array('label' => 'Match', 'required' => false));
        $formDef->add('date', 'text', array('label' => 'Dato', 'required' => false));
        $formDef->add('time', 'text', array('label' => 'Tid', 'required' => false));
        $formDef->add('playground', 'choice', array('label' => 'Bane', 'required' => false, 'choices' => $playgroundnames, 'empty_value' => 'Vælg...'));
        $formDef->add('teamA', 'choice', array('label' => 'Hjemmehold', 'required' => false, 'choices' => $teamnames, 'empty_value' => 'Vælg...'));
        $formDef->add('teamB', 'choice', array('label' => 'Udehold', 'required' => false, 'choices' => $teamnames, 'empty_value' => 'Vælg...'));
        return $formDef->getForm();
    }
}
