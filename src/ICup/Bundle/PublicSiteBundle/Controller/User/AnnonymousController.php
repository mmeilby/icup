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
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

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
        
        $tournamentKey = $utilService->getTournamentKey();
        if ($tournamentKey != '_') {
            $tournament = $this->get('logic')->getTournamentByKey($tournamentKey);
        }
        else {
            $tournament = null;
        }
        return array('form' => $form->createView(), 'action' => 'add', 'user' => $user, 'tournament' => $tournament);
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
        $formDef->add('name', 'text', array('label' => 'FORM.USER.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('email', 'text', array('label' => 'FORM.USER.EMAIL', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('username', 'text', array('label' => 'FORM.USER.USERNAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('password', 'text', array('label' => 'FORM.USER.PASSWORD', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.USER.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.USER.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, User $user) {
        if ($form->isValid()) {
            if ($user->getName() == null || trim($user->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NONAME', array(), 'admin')));
            }
            if ($user->getUsername() == null || trim($user->getUsername()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NOUSERNAME', array(), 'admin')));
            }
            if ($user->getEmail() == null || trim($user->getEmail()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NOEMAIL', array(), 'admin')));
            }
            if ($user->getPassword() == null || trim($user->getPassword()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NOPASSWORD', array(), 'admin')));
            }
        }
        if ($form->isValid()) {
            $usr = $this->get('logic')->getUserByName($user->getUsername());
            if ($usr != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NAMEEXIST', array(), 'admin')));
            }
            /* @var $utilService Util */
            $utilService = $this->get('util');
            if ($utilService->generatePassword($user, $user->getPassword()) === FALSE) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.BADPASSWORD', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}
