<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TournamentImportController extends Controller
{
    /**
     * Copy objects from another tournament
     * @Route("/edit/tournament/import/{hostid}", name="_edit_import_tournament")
     * @Template("ICupPublicSiteBundle:Edit:importtournament.html.twig")
     */
    public function importAction($hostid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();
        
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $host = $this->get('entity')->getHostById($hostid);
        $utilService->validateEditorAdminUser($user, $host);

        $tournament = new Tournament();
        $tournament->setHost($host);
        $form = $this->makeImportForm($tournament);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $formData = $form->getData();
            $tid = $formData['tournament'];
            if ($tid > 0) {
                $source_tournament = $this->get('entity')->getTournamentById($tid);
                if ($source_tournament->getHost()->getId() != $tournament->getHost()->getId()) {
                    throw new ValidationException("NOTTHESAMETOURNAMENT",
                        "Not allowed to import from different host, source=" . $source_tournament->getHost()->getId() . ", target=" . $tournament->getHost()->getId());
                }
                $this->importTournament($source_tournament, $tournament);
                return $this->redirect($returnUrl);
            }
            else {
                
            }
        }
        return array('form' => $form->createView());
    }
    
    private function makeImportForm(Tournament $tournament) {
        $tournaments = $tournament->getHost()->getTournaments();
        $tournamentList = array();
        foreach ($tournaments as $tmnt) {
            if ($tmnt->getId() != $tournament->getId()) {
                $tournamentList[$tmnt->getId()] = $tmnt->getName();
            }
        }
        $formDef = $this->createFormBuilder(array('tournament' => ''));
        $formDef->add('tournament', 'choice',
              array('label' => 'FORM.TOURNAMENTIMPORT.TOURNAMENT.PROMPT',
                    'help' => 'FORM.TOURNAMENTIMPORT.TOURNAMENT.HELP',
                    'choices' => $tournamentList,
                    'empty_value' => 'FORM.TOURNAMENTIMPORT.DEFAULT',
                    'required' => false,
                    'disabled' => false,
                    'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TOURNAMENTIMPORT.CANCEL',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TOURNAMENTIMPORT.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function importTournament(Tournament $source_tournament, Tournament $tournament) {
        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();
        try {
            $tournament->setKey(uniqid());
            $tournament->setEdition($source_tournament->getEdition());
            $tournament->setName($source_tournament->getName().' '.$this->get('translator')->trans('FORM.TOURNAMENTIMPORT.COPY', array(), 'admin'));
            $tournament->setDescription($source_tournament->getDescription());
            $tournament->getOption()->setDrr($source_tournament->getOption()->isDrr());
            $tournament->getOption()->setStrategy($source_tournament->getOption()->getStrategy());
            $tournament->getOption()->setWpoints($source_tournament->getOption()->getWpoints());
            $tournament->getOption()->setTpoints($source_tournament->getOption()->getTpoints());
            $tournament->getOption()->setLpoints($source_tournament->getOption()->getLpoints());
            $tournament->getOption()->setDscore($source_tournament->getOption()->getDscore());
            $em->persist($tournament);
            $em->flush();
            $this->importSites($source_tournament, $tournament);
            $em->commit();
        }
        catch (Exception $e) {
            $em->rollBack();
            throw $e;
        }
    }

    private function importSites(Tournament $source_tournament, Tournament $tournament) {
        $em = $this->getDoctrine()->getManager();
        $tsconversion = $this->importTimeslots($source_tournament, $tournament);
        $cconversion = $this->importCategories($source_tournament, $tournament);
        /* @var $site Site */
        foreach ($source_tournament->getSites() as $site) {
            $new_site = new Site();
            $new_site->setTournament($tournament);
            $new_site->setName($site->getName());
            $em->persist($new_site);
            $em->flush();
            foreach ($site->getPlaygrounds() as $playground) {
                $new_playground = new Playground();
                $new_playground->setSite($new_site);
                $new_playground->setName($playground->getName());
                $new_playground->setNo($playground->getNo());
                $new_playground->setLocation($playground->getLocation());
                $em->persist($new_playground);
                $em->flush();
                $this->importPAttrs($playground, $new_playground, $tsconversion, $cconversion);
            }
        }
    }
    
    private function importTimeslots(Tournament $source_tournament, Tournament $tournament) {
        $em = $this->getDoctrine()->getManager();
        $tsconversion = array();
        foreach ($source_tournament->getTimeslots() as $timeslot) {
            $new_timeslot = new Timeslot();
            $new_timeslot->setTournament($tournament);
            $new_timeslot->setName($timeslot->getName());
            $new_timeslot->setCapacity($timeslot->getCapacity());
            $new_timeslot->setPenalty($timeslot->getPenalty());
            $new_timeslot->setRestperiod($timeslot->getRestperiod());
            $em->persist($new_timeslot);
            $tsconversion[$timeslot->getId()] = $new_timeslot;
        }
        $em->flush();
        return $tsconversion;
    }
    
    private function importPAttrs(Playground $playground, Playground $new_playground, array $tsconversion, array $cconversion) {
        $em = $this->getDoctrine()->getManager();
        foreach ($playground->getPlaygroundAttributes() as $pattr) {
            $new_pattr = new PlaygroundAttribute();
            $new_pattr->setPlayground($new_playground);
            $new_pattr->setTimeslot($tsconversion[$pattr->getTimeslot()->getId()]);
            $new_pattr->setDate($pattr->getDate());
            $new_pattr->setStart($pattr->getStart());
            $new_pattr->setEnd($pattr->getEnd());
            $new_pattr->setFinals($pattr->getFinals());
            foreach ($pattr->getCategories() as $category) {
                $new_pattr->getCategories()->add($cconversion[$category->getId()]);
            }
            $em->persist($new_pattr);
        }
        $em->flush();
    }
    
    private function importCategories(Tournament $source_tournament, Tournament $tournament) {
        $em = $this->getDoctrine()->getManager();
        $cconversion = array();
        /* @var $category Category */
        foreach ($source_tournament->getCategories() as $category) {
            $new_category = new Category();
            $new_category->setTournament($tournament);
            $new_category->setName($category->getName());
            $new_category->setGender($category->getGender());
            $new_category->setClassification($category->getClassification());
            $new_category->setAge($category->getAge());
            $new_category->setMatchtime($category->getMatchtime());
            $em->persist($new_category);
            $cconversion[$category->getId()] = $new_category;
            foreach ($category->getGroups() as $group) {
                $new_group = new Group();
                $new_group->setCategory($new_category);
                $new_group->setName($group->getName());
                $new_group->setClassification($group->getClassification());
                $em->persist($new_group);
            }
        }
        $em->flush();
        return $cconversion;
    }
}
