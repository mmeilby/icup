<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Host;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Password;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class EditorController extends Controller
{
    /**
     * List the editors related to logged in editor admin
     * @Route("/edit/user/list/host", name="_edit_editors_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listeditors.html.twig")
     */
    public function listEditorsAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            // Validate current user - is it an editor?
            $utilService->validateHostUser($user);
            // Get the host from current user
            $hostid = $user->getPid();
            $host = $this->get('entity')->getHostById($hostid);
            $users = $this->get('entity')->getUserRepo()->findBy(array('pid' => $hostid));

            return array('host' => $host, 'users' => $users);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
    
    /**
     * List the editors related to a host
     * @Route("/admin/user/list/host/{hostid}", name="_edit_editor_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listeditors.html.twig")
     */
    public function listUsersAction($hostid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            if (!$utilService->isAdminUser($user)) {
                throw new ValidationException("MUSTBEADMIN", "userid=".$user->getId().", role=".$user->getRole());
            }
            $host = $this->get('entity')->getHostById($hostid);
            $users = $this->get('entity')->getUserRepo()->findBy(array('pid' => $hostid));

            return array('host' => $host, 'users' => $users);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
}
