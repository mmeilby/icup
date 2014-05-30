<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Password;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * General user account functions
 */
class ChangePasswordController extends Controller
{
    /**
     * Change password for logged in user
     * @Route("/user/chg/pass", name="_user_chg_pass")
     * @Template("ICupPublicSiteBundle:User:chg_pass.html.twig")
     */
    public function passAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        
        $pwd = new Password();
        $form = $this->makePassForm($pwd);
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($this->checkForm($form, $pwd)) {
            $utilService->generatePassword($user, $pwd->getPassword());
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        return array('form' => $form->createView(), 'user' => $user);
    }
    
    private function makePassForm($pwd) {
        $formDef = $this->createFormBuilder($pwd);
        $formDef->add('password', 'password', array('label' => 'FORM.NEWPASS.PASSWORD', 'required' => false, 'translation_domain' => 'club'));
        $formDef->add('password2', 'password', array('label' => 'FORM.NEWPASS.PASSWORD2', 'required' => false, 'translation_domain' => 'club'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.NEWPASS.CANCEL', 'translation_domain' => 'club'));
        $formDef->add('save', 'submit', array('label' => 'FORM.NEWPASS.SUBMIT', 'translation_domain' => 'club'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, Password $pwd) {
        if ($form->isValid()) {
            if ($pwd->getPassword() == null || trim($pwd->getPassword()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWPASS.NOPASSWORD', array(), 'club')));
                return false;
            }
            if ($pwd->getPassword() != $pwd->getPassword2()) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWPASS.NOTEQUAL', array(), 'club')));
                return false;
            }
            return true;
        }
        return false;
    }
}
