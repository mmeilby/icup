<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Symfony\Component\Form\FormError;

class ClubMergeController extends Controller
{
    /**
     * Merge clubs
     * @Route("/admin/club/merge/{clubid}/{sourceclubid}", name="_edit_club_merge", options={"expose"=true}))
     * @Template("ICupPublicSiteBundle:Edit:mergeclub.html.twig")
     */
    public function mergeAction($clubid, $sourceclubid) {
        $returnUrl = $this->get('util')->getReferer();
        
        $target_club = $this->get('entity')->getClubById($clubid);
        $source_club = $this->get('entity')->getClubById($sourceclubid);
        
        $form = $this->makeClubForm();
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $this->mergeClubs($source_club, $target_club);
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'source_club' => $source_club, 'target_club' => $target_club);
    }
    
    private function makeClubForm() {
        $formDef = $this->createFormBuilder();
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUBMERGE.CANCEL', 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUBMERGE.SUBMIT', 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function mergeClubs($source_club, $target_club) {
        $em = $this->getDoctrine()->getManager();
        $teams = $this->get('logic')->listTeamsByClub($source_club->getId());
        foreach ($teams as $team) {
            try {
                $category = $this->get('logic')->getEnrolledCategory($team->getId());
            }
            catch (ValidationException $ex) {
                $category = $this->get('logic')->getAssignedCategory($team->getId());
            }
            $enrolledteams = $this->get('logic')->listEnrolledTeamsByCategory($category->getId(), $target_club->getId());
            $noTeams = count($enrolledteams);
            if ($noTeams >= 26) {
                // Can not add more than 26 teams to same category - Team A -> Team Z - make an exception here...
                $division = 'm'.$team->getDivision();
            }
            else if ($noTeams == 0) {
                $division = '';
            }
            else if ($noTeams == 1) {
                $division = 'B';
                $firstteam = array_shift($enrolledteams);
                $firstteam->setDivision('A');
            }
            else {
                $division = chr($noTeams + 65);
            }
            $team->setPid($target_club->getId());
            $team->setName($target_club->getName());
            $team->setDivision($division);
            $em->flush();
        }
        $users = $this->get('logic')->listUsersByClub($source_club->getId());
        foreach ($users as $user) {
            $user->setCid($target_club->getId());
        }
        $em->remove($source_club);
        $em->flush();
    }
}
