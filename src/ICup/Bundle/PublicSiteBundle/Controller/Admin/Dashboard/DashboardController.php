<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Dashboard;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use DateTime;
use Symfony\Cmf\Bundle\MediaBundle\File\UploadFileHelperInterface;
use PHPCR\Util\NodeHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Admin dashboard
 */
class DashboardController extends Controller
{
    /**
     * Show myICup page for authenticated users
     * @Route("/edit/dashboard", name="_edit_dashboard")
     * @Template("ICupPublicSiteBundle:Edit:dashboard.html.twig")
     */
    public function dashboardAction(Request $request)
    {
        $user = $this->get('util')->getCurrentUser();
        if ($user->isEditor()) {
            $hostid = $user->getPid();
            $form = $this->getSearchForm($hostid);
        }
        else {
            /* @var $session Session */
            $session = $request->getSession();
            $hostid = $session->get('Host', 0);
            
            $form = $this->getSearchForm($hostid);
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();
                $hostid = $formData['host'];
                $session->set('Host', $hostid);
            }
        }
        
        $parameters = array(
            'currentuser' => $user,
            'search_form' => $form->createView(),
        );
        
        if ($hostid) {
            /* @var $host Host */
            $host = $this->get('entity')->getHostById($hostid);
            $parameters['host'] = $host;
            $parameters['users'] = $this->get('logic')->listUsersByHost($host->getId());
            $tournaments = $this->get('logic')->listTournaments($host->getId());
            $tstat = array();
            $today = new DateTime();
            foreach ($tournaments as $tournament) {
                $tstat[$tournament->getId()] = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            }
            $parameters['tournaments'] = $tournaments;
            $parameters['tstat'] = $tstat;
        }
            
        return $parameters;
    }

    private function getSearchForm($hostid) {
        $choices = array();
        $hosts = $this->get('logic')->listHosts(); 
        foreach ($hosts as $host) {
            $choices[$host->getId()] = $host->getName();
        }

        $formData = array('host' => $hostid);
        $formDef = $this->createFormBuilder($formData);
        $formDef->add('host', 'choice', array('label' => 'FORM.DASHBOARD.HOSTS', 'required' => false, 'choices' => $choices, 'empty_value' => 'FORM.DASHBOARD.DEFAULT', 'translation_domain' => 'admin'));
        $formDef->add('view', 'submit', array('label' => 'FORM.DASHBOARD.SHOW',
                                              'translation_domain' => 'admin',
                                              'icon' => 'fa fa-search'));
        return $formDef->getForm();
    }
    
    /**
     * Show myICup page for club admin users
     * @Route("/host/dashboard/tournament", name="_tournament_dashboard")
     * @Method("GET")
     */
    public function tournamentDashboardAction()
    {
        
    }
    
    /**
     * Show myICup page for club admin users
     * @Route("/admin/dashboard/upload", name="_admin_dashboard_upload")
     * @Method("GET")
     */
    public function myPageUploadAction()
    {
        $user = $this->get('util')->getCurrentUser();
        $fileClass = 'Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\File';
        $dm = $this->get('doctrine_phpcr')->getManager('default');
        $files = $dm->getRepository($fileClass)->findAll();
        return $this->render('ICupPublicSiteBundle:User:mypage_admin.html.twig', array(
                    'currentuser' => $user,
                    'upload_form' => $this->getUploadForm()->createView(),
                    'files' => $files,
        ));
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
                $parent = $dm->find(null, '/cms/media/enrollment');
                if (!$parent) {
                    NodeHelper::createPath($dm->getPhpcrSession(), '/cms/media/enrollment');
                    $parent = $dm->find(null, '/cms/media/enrollment');
                }
                $file->setParent($parent);
                $dm->persist($file);
                $dm->flush();
            }
        }
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    private function getUploadForm() {
//        return $this->container->get('form.factory')->createNamedBuilder(null, 'form')
        return $this->createFormBuilder()
                ->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => false, 'translation_domain' => 'admin'))
                ->add('file', 'file', array('label' => 'FORM.CLUB.COUNTRY',
                                            'required' => false,
                                            'disabled' => false,
                                            'translation_domain' => 'admin'))
                ->getForm();
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
    
    private function getTournaments() {
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $keyList = array(
            TournamentSupport::$TMNT_ENROLL => 'enroll',
            TournamentSupport::$TMNT_GOING => 'active',
            TournamentSupport::$TMNT_DONE => 'done',
            TournamentSupport::$TMNT_ANNOUNCE => 'announce'
        );
        $statusList = array(
            'enroll' => array(),
            'active' => array(),
            'done' => array(),
            'announce' => array()
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
}
