<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\ClubRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Voucher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Exception;

class ClubMergeController extends Controller
{
    /**
     * Merge clubs
     * @Route("/admin/club/merge/{clubid}/{sourceclubid}", name="_edit_club_merge", options={"expose"=true}))
     * @Template("ICupPublicSiteBundle:Edit:mergeclub.html.twig")
     * @param $clubid
     * @param $sourceclubid
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function mergeAction($clubid, $sourceclubid, Request $request) {
        $returnUrl = $this->get('util')->getReferer();
        
        $target_club = $this->get('entity')->getClubById($clubid);
        $source_club = $this->get('entity')->getClubById($sourceclubid);
        
        $form = $this->makeClubForm();
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
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUBMERGE.CANCEL',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUBMERGE.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function mergeClubs(Club $source_club, Club $target_club) {
        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();
        try {
            $teams = $source_club->getTeams();
            /* @var $category Category */
            foreach ($teams as $team) {
                /* @var $team Team */
                try {
                    $category = $team->getCategory();
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
                $team->setClub($target_club);
                $team->setName($target_club->getName());
                $team->setDivision($division);
                $em->flush();
            }
            foreach ($source_club->getOfficials() as $official) {
                /* @var $official ClubRelation */
                $target_club->getOfficials()->add($official);
                $official->setClub($target_club);
            }
            foreach ($source_club->getVouchers() as $voucher) {
                /* @var $voucher Voucher */
                $target_club->getVouchers()->add($voucher);
                $voucher->setClub($target_club);
            }
            $source_club->getTeams()->clear();
            $source_club->getOfficials()->clear();
            $source_club->getVouchers()->clear();
            $em->remove($source_club);
            $em->flush();
            $em->commit();
        }
        catch (Exception $e) {
            $em->rollBack();
            throw $e;
        }
    }
}
