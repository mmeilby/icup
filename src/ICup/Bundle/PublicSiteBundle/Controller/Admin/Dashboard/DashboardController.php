<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Dashboard;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use DateTime;
use Symfony\Cmf\Bundle\MediaBundle\File\UploadFileHelperInterface;
use PHPCR\Util\NodeHelper;
use Symfony\Component\HttpFoundation\Request;
use ICup\Bundle\PublicSiteBundle\Entity\FileForm;

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
     * @Route("/admin/dashboard/upload", name="_admin_dashboard_upload")
     */
    public function uploadAction(Request $request)
    {
        $fileForm = new FileForm();
        $form = $this->getUploadForm($fileForm);
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($fileForm->getPath() == 'images') {
                /** @var UploadFileHelperInterface $uploadFileHelper */
                $uploadFileHelper = $this->get('cmf_media.upload_image_helper');
                $path = '/cms/media/images';
            }
            else {
                /** @var UploadFileHelperInterface $uploadFileHelper */
                $uploadFileHelper = $this->get('cmf_media.upload_file_helper');
                $path = '/cms/media/'.$fileForm->getPath();
            }
            $uploadedFile = $request->files->get('form');
            $file = $uploadFileHelper->handleUploadedFile($uploadedFile['file']);
            $file->setDescription($fileForm->getName());
            // persist
            $dm = $this->get('doctrine_phpcr')->getManager('default');
            $parent = $dm->find(null, $path);
            if (!$parent) {
                NodeHelper::createPath($dm->getPhpcrSession(), $path);
                $parent = $dm->find(null, $path);
            }
            $file->setParent($parent);
            $dm->persist($file);
            $dm->flush();
        }
        
        $user = $this->get('util')->getCurrentUser();
        $fileClass = 'Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\File';
        $dm = $this->get('doctrine_phpcr')->getManager('default');
        $files = $dm->getRepository($fileClass)->findAll();
        return $this->render('ICupPublicSiteBundle:Edit:dashboard_upload.html.twig', array(
                    'currentuser' => $user,
                    'upload_form' => $form->createView(),
                    'files' => $files,
        ));
    }

    private function getUploadForm(FileForm $fileForm) {
        return $this->createFormBuilder($fileForm)
                ->add('name', 'text', array('label' => 'FORM.UPLOAD.NAME',
                                            'required' => false,
                                            'disabled' => false,
                                            'translation_domain' => 'admin'))
                ->add('path', 'choice', array('label' => 'FORM.UPLOAD.PATH',
                                            'required' => false,
                                            'choices' => array('files' => 'FILES', 'images' => 'IMAGES', 'enrollment' => 'DOWNLOADS'),
                                            'empty_value' => false,
                                            'disabled' => false,
                                            'translation_domain' => 'admin'))
                ->add('file', 'file', array('label' => 'FORM.UPLOAD.FILE',
                                            'required' => false,
                                            'disabled' => false,
                                            'translation_domain' => 'admin'))
                ->getForm();
    }
}
