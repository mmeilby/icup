<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

/**
 * List the categories and groups available
 */
class AnnonymousController extends Controller
{
    /**
     * Add new club user 
     * @Route("/new/user", name="_ausr_new_user")
     * @Template("ICupPublicSiteBundle:User:ausr_new_user.html.twig")
     */
    public function newUserAction()
    {
        /* @var $user User */
        $user = $this->getUser();
        if ($user != null) {
            // Controller is called by authenticated user - switch to "MyPage"
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        else {
            $user = new User();
        }
        
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        if ($this->checkForm($form, $user)) {
            $user->setUsername($user->getEmail());
            $user->setStatus(User::$AUTH);
            $user->setRole(User::$CLUB);
            $user->setCid(0);
            $user->setPid(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $token = new UsernamePasswordToken($user, null, 'new_user', $user->getRoles());
            $this->get('security.context')->setToken($token);
            $this->get('event_dispatcher')->dispatch(
                    AuthenticationEvents::AUTHENTICATION_SUCCESS,
                    new AuthenticationEvent($token));

            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        
        return array('form' => $form->createView(), 'action' => 'add', 'user' => $user);
    }

    /**
     * Add new referee user 
     * @Route("/new/referee", name="_ausr_new_referee")
     * @Template("ICupPublicSiteBundle:User:ausr_new_referee.html.twig")
     */
    public function newRefereeAction()
    {
    }
    
    private function makeUserForm(User $user, $action) {
        $formDef = $this->createFormBuilder($user);
        $formDef->add('name', 'text', array('label' => 'FORM.NEWUSER.NAME',
                                            'required' => false,
                                            'disabled' => $action == 'del',
                                            'translation_domain' => 'club',
                                            'help' => 'FORM.NEWUSER.HELP.NAME',
                                            'icon' => 'fa fa-user'));
        $formDef->add('email', 'text', array('label' => 'FORM.NEWUSER.EMAIL',
                                            'required' => false,
                                            'disabled' => $action == 'del',
                                            'translation_domain' => 'club',
                                            'help' => 'FORM.NEWUSER.HELP.EMAIL',
                                            'icon' => 'fa fa-envelope'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.NEWUSER.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'club',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.NEWUSER.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-magic'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, User $user) {
        if ($form->isValid()) {
            if ($user->getName() == null || trim($user->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NONAME', array(), 'admin')));
            }
            if ($user->getEmail() == null || trim($user->getEmail()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NOEMAIL', array(), 'admin')));
            }
        }
        if ($form->isValid()) {
            $usr = $this->get('logic')->getUserByName($user->getEmail());
            if ($usr != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NAMEEXIST', array(), 'admin')));
            }
            /* @var $utilService Util */
            $utilService = $this->get('util');
            if ($utilService->generatePassword($user, $user->getEmail()) === FALSE) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.BADPASSWORD', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}
