<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditGroupOrderController extends Controller
{
    /**
     * Add or update the group information
     * @Route("/edit/tmnt/{tournament}/new/grouporder/{categoryId}", name="_newgrouporder")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:editgrouporder.html.twig")
     */
    public function newAction($tournament, $categoryId) {
        $this->get('util')->setupController($tournament);
        $tournamentId = $this->get('util')->getTournamentId();
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryId);

        $form = $this->makeform($category);
        return array('form' => $form->createView(), 'tournament' => $tournament, 'category' => $category);
    }

    /**
     * Add or update the group information
     * @Route("/edit/tmnt/{tournament}/new/grouporder/{categoryId}", name="_newgrouporderpost")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("POST")
     * @Template("ICupPublicSiteBundle:Edit:editgrouporder.html.twig")
     */
    public function newPostAction($tournament, $categoryId) {
        $this->get('util')->setupController($tournament);
        $tournamentId = $this->get('util')->getTournamentId();
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryId);

        $form = $this->makeform($category);
        $request = $this->getRequest();
        $form->bind($request);
        if ($form->isValid()) {
            $formData = $form->getData();
            $em->persist($formData);
            $em->flush();
        }
        return array('form' => $form->createView(), 'tournament' => $tournament, 'category' => $category);
    }
    
    private function makeform($category) {
        $em = $this->getDoctrine()->getManager();
        $groups = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')
                            ->findBy(array('pid' => $category->getId()));
        $groupnames = array();
        foreach ($groups as $group) {
            $groupnames[$group->getId()] = $group->getName();
        }

        $qb = $em->createQuery("select t ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g ".
                               "where g.pid=:category and ".
                                     "o.pid=g.id and ".
                                     "t.id=o.cid ".
                               "order by t.id");
        $qb->setParameter('category', $category->getId());
        $teams = $qb->getResult();
        $teamnames = array();
        foreach ($teams as $team) {
            $name = $team->getName();
            if ($team->getDivision() != '') {
                $name.= ' "'.$team->getDivision().'"';
            }
            $teamnames[$team->getId()] = $name;
        }

        $formData = new GroupOrder();
        $formDef = $this->createFormBuilder($formData);
        $formDef->add('pid', 'choice', array('label' => 'Gruppe', 'required' => false, 'choices' => $groupnames, 'empty_value' => 'Vælg...'));
        $formDef->add('cid', 'choice', array('label' => 'Hold', 'required' => false, 'choices' => $teamnames, 'empty_value' => 'Vælg...'));
        return $formDef->getForm();
    }
}
