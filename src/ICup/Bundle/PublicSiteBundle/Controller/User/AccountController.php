<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Password;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * General user account functions
 */
class AccountController extends Controller
{
    /**
     * Change password for logged in user
     * @Route("/user/chg/pass", name="_user_chg_pass")
     * @Template("ICupPublicSiteBundle:User:chg_pass.html.twig")
     */
    public function passAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - prepare a new database user
            $admin = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->findBy(array('username' => $user->getUsername()));
            if ($admin == null) {
                $admin = new User();
                $admin->setName($user->getUsername());
                $admin->setUsername($user->getUsername());
                $admin->setRole(User::$ADMIN);
                $admin->setStatus(User::$SYSTEM);
                $admin->setEmail('');
                $admin->setPid(0);
                $admin->setCid(0);
                $utilService->generatePassword($this, $admin, $user->getUsername());
                $em->persist($admin);
                $em->flush();
                $user = $admin;
            }
            else {
                $user = $admin[0];
            }
        }
        
        $pwd = new Password();
        $formDef = $this->createFormBuilder($pwd);
        $formDef->add('password', 'password', array('label' => 'FORM.NEWPASS.PASSWORD', 'required' => false, 'translation_domain' => 'club'));
        $formDef->add('password2', 'password', array('label' => 'FORM.NEWPASS.PASSWORD2', 'required' => false, 'translation_domain' => 'club'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.NEWPASS.CANCEL', 'translation_domain' => 'club'));
        $formDef->add('save', 'submit', array('label' => 'FORM.NEWPASS.SUBMIT', 'translation_domain' => 'club'));
        $form = $formDef->getForm();
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($form->isValid()) {
            $utilService->generatePassword($this, $user, $pwd->getPassword());
            $em->flush();
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        return array('form' => $form->createView(), 'user' => $user);
    }
}
