<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
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
        $utilService->validateEditorAdminUser($user, $hostid);

        $tournament = new Tournament();
        $tournament->setPid($hostid);
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
                if ($source_tournament->getPid() != $tournament->getPid()) {
                    throw new ValidationException("NOTTHESAMETOURNAMENT",
                        "Not allowed to import from different host, source=" . $source_tournament->getPid() . ", target=" . $tournament->getPid());
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
        $tournaments = $this->get('logic')->listTournaments($tournament->getPid());
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
        $sites = $this->get('logic')->listSites($source_tournament->getId());
        foreach ($sites as $site) {
            $new_site = new Site();
            $new_site->setPid($tournament->getId());
            $new_site->setName($site->getName());
            $em->persist($new_site);
            $em->flush();
            $playgrounds = $this->get('logic')->listPlaygrounds($site->getId());
            foreach ($playgrounds as $playground) {
                $new_playground = new Playground();
                $new_playground->setPid($new_site->getId());
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
        $timeslots = $this->get('logic')->listTimeslots($source_tournament->getId());
        foreach ($timeslots as $timeslot) {
            $new_timeslot = new Timeslot();
            $new_timeslot->setPid($tournament->getId());
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
        $pattrs = $this->get('logic')->listPlaygroundAttributes($playground->getId());
        foreach ($pattrs as $pattr) {
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
        $categories = $this->get('logic')->listCategories($source_tournament->getId());
        foreach ($categories as $category) {
            $new_category = new Category();
            $new_category->setPid($tournament->getId());
            $new_category->setName($category->getName());
            $new_category->setGender($category->getGender());
            $new_category->setClassification($category->getClassification());
            $new_category->setAge($category->getAge());
            $new_category->setMatchtime($category->getMatchtime());
            $em->persist($new_category);
            $cconversion[$category->getId()] = $new_category;
            $groups = $this->get('logic')->listGroupsByCategory($category->getId());
            foreach ($groups as $group) {
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
