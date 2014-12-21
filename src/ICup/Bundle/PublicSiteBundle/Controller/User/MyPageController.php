<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use DateTime;
use Symfony\Cmf\Bundle\MediaBundle\File\UploadFileHelperInterface;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\File;
use Symfony\Component\HttpFoundation\Request;

/**
 * myPage - myICup - user's home page with context dependent content
 */
class MyPageController extends Controller
{
    /**
     * Show myICup page for authenticated users
     * @Route("/user/mypage", name="_user_my_page")
     * @Method("GET")
     */
    public function myPageAction()
    {
        
        $user = $this->get('util')->getCurrentUser();
        try {
            // If user is an admin user throw RedirectException and redirect to admin myPage
            $this->redirectMyAdminPage($user);
            // If user is an editor user throw RedirectException and redirect to editor myPage
            $this->redirectMyEditorPage($user);
            // If user is an unrelated user throw RedirectException and redirect to myPage for unrelated users
            $this->redirectMyUserPage($user);
            // At this point - user is a related club user/admin
            return $this->getMyClubUserPage($user);
        }
        catch (RedirectException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Show myICup page for club admin users
     * @Route("/club/mypage/users", name="_user_my_page_users")
     * @Method("GET")
     */
    public function myPageUsersAction()
    {
        
        $user = $this->get('util')->getCurrentUser();
        $clubid = $user->getCid();
        $this->get('util')->validateClubAdminUser($user, $clubid);
        $club = $this->get('entity')->getClubById($clubid);
        $users = $this->get('logic')->listUsersByClub($clubid);
        // Redirect to my page users list
        return $this->render('ICupPublicSiteBundle:User:mypage_users.html.twig',
                array('club' => $club,
                      'users' => $users,
                      'currentuser' => $user));
    }

    private function redirectMyAdminPage($user) {
        if (!($user instanceof User)) {
            // Controller is called by default admin
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_def_admin.html.twig'));
            throw $rexp;
        }
        /* @var $user User */
        if ($user->isAdmin()) {
            $fileClass = 'Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\File';
            $dm = $this->get('doctrine_phpcr')->getManager('default');
            $files = $dm->getRepository($fileClass)->findAll();
            // Admins should get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_admin.html.twig', array(
                        'currentuser' => $user,
                        'upload_form' => $this->getUploadForm()->createView(),
                        'files' => $files,
            )));
            throw $rexp;
        }
    }

    private function redirectMyEditorPage(User $user) {
       if ($user->isEditor()) {
            /* @var $host Host */
            $host = $this->get('entity')->getHostById($user->getPid());
            $users = $this->get('logic')->listUsersByHost($host->getId());
            $tournaments = $this->get('logic')->listTournaments($host->getId());
            $tstat = array();
            $today = new DateTime();
            foreach ($tournaments as $tournament) {
                $tstat[$tournament->getId()] = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            }
            // Editors should get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_editor.html.twig',
                                             array('host' => $host,
                                                   'tournaments' => $tournaments,
                                                   'tstat' => $tstat,
                                                   'users' => $users,
                                                   'currentuser' => $user)));
            throw $rexp;
        }
    }
    
    private function redirectMyUserPage(User $user) {
        if (!$user->isClub() || !$user->isRelated()) {
            // Non related users get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_nonrel.html.twig',
                                             array_merge(array('currentuser' => $user), $this->getTournaments())));
            throw $rexp;
        }
    }

    private function getMyClubUserPage(User $user) {
        $clubid = $user->getCid();
        $club = $this->get('entity')->getClubById($clubid);
        $users = $this->get('logic')->listUsersByClub($clubid);
        $prospectors = array();
        foreach ($users as $usr) {
            if ($usr->getStatus() === User::$PRO) {
                $prospectors[] = $usr;
            }
        }
        $tournamentList = $this->getEnrollments($user);
        // Redirect to my page
        return $this->render('ICupPublicSiteBundle:User:mypage.html.twig',
                array_merge(
                    array('club' => $club,
                          'prospectors' => $prospectors,
                          'currentuser' => $user,
                          'tournamentlist' => $tournamentList),
                    $this->getTournaments(),
                    $this->listTeams($club)));
    }

    private function listTeams($club)
    {
        $today = new DateTime();
        $tournaments = $this->get('logic')->listAvailableTournaments();
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat == TournamentSupport::$TMNT_GOING || $stat == TournamentSupport::$TMNT_DONE) {
                $categories = $this->get('logic')->listCategories($tournament->getId());
                $categoryList = array();
                foreach ($categories as $category) {
                    $categoryList[$category->getId()] = $category;
                }
                $teams = $this->get('tmnt')->listTeamsByClub($tournament->getId(), $club->getId());
                $teamList = array();
                foreach ($teams as $team) {
                    $name = $team['name'];
                    if ($team['division'] != '') {
                        $name.= ' "'.$team['division'].'"';
                    }
                    $team['name'] = $name;
                    $teamList[$team['catid']][$team['id']] = $team;
                }

                return array('teams' => $teamList, 'categories' => $categoryList);
            }
        }
        return array('teams' => array());
    }

    private function getEnrollments(User $user) {
        $today = new DateTime();
        $enrolled = $this->get('logic')->listAnyEnrolledByClub($user->getCid());
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'enrolled' => 0);
            }
        }
        
        foreach ($enrolled as $enroll) {
            $tid = $enroll['tid'];
            if (key_exists($tid, $tournamentList)) {
                $tournamentList[$tid]['enrolled'] = $enroll['enrolled'];
            }
        }
        return $tournamentList;
    }
    
    private function getTournaments() {
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $keyList = array(
            TournamentSupport::$TMNT_ENROLL => 'enroll',
            TournamentSupport::$TMNT_GOING => 'active',
            TournamentSupport::$TMNT_DONE => 'done'
        );
        $statusList = array(
            'enroll' => array(),
            'active' => array(),
            'done' => array()
        );
        $today = new DateTime();
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'status' => $stat);
                $statusList[$keyList[$stat]][] = $tournament;
            }
        }
        return array('tournaments' => $tournamentList, 'statuslist' => $statusList);
    }
    
    private function getUploadForm() {
        return $this->container->get('form.factory')->createNamedBuilder(null, 'form')
                ->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => false, 'translation_domain' => 'admin'))
                ->add('file', 'file', array('label' => 'FORM.CLUB.COUNTRY',
                                            'required' => false,
                                            'disabled' => false,
                                            'translation_domain' => 'admin'))
                ->getForm();
    }
    
    /**
     * Show myICup page for authenticated users
     * @Route("/user/mypage/upload", name="_user_my_page_upload")
     * @Method("POST")
     */
    public function uploadAction(Request $request) {
        $form = $this->getUploadForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                /** @var UploadFileHelperInterface $uploadFileHelper */
                $uploadFileHelper = $this->get('cmf_media.upload_file_helper');
                $uploadedFile = $request->files->get('file');
                $file = $uploadFileHelper->handleUploadedFile($uploadedFile);
                $file->setDescription($request->get('name'));
                // persist
                $dm = $this->get('doctrine_phpcr')->getManager('default');
                $parent = $dm->find(null, '/cms/media');
                $file->setParent($parent);
                $dm->persist($file);
                $dm->flush();
            }
        }
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

}
