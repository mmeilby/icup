<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use DateTime;

class PAttrPlanningController extends Controller
{
    /**
     * List the clubs by groups assigned in the category
     * @Route("/edit/list/parel/{playgroundattributeid}", name="_edit_list_parel")
     * @Template("ICupPublicSiteBundle:Host:listparel.html.twig")
     */
    public function listByCategoryAction($playgroundattributeid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $pattr PlaygroundAttribute */
        $pattr = $this->get('entity')->getPlaygroundAttributeById($playgroundattributeid);
        $playground = $pattr->getPlayground();
        /* @var $site Site */
        $site = $playground->getSite();
        /* @var $tournament Tournament */
        $tournament = $site->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $categories = $tournament->getCategories();
        $categoryList = array();
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = $category;
        }

        $assignedCategoryList = array();
        foreach ($pattr->getCategories() as $category) {
            $assignedCategoryList[$category->getId()] = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'gender' => $category->getGender(),
                'age' => $category->getAge(),
                'classification' => $category->getClassification(),
                'matchtime' => $category->getMatchtime(),
                'finals' => $pattr->getFinals()
            );
        }

        $unassignedCategoryList = array_diff_key($categoryList, $assignedCategoryList);
        
        return array('host' => $host,
                     'tournament' => $tournament,
                     'playground' => $playground,
                     'attribute' => $pattr,
                     'attr_schedule' => $pattr->getStartSchedule(),
                     'unassignedlist' => $unassignedCategoryList,
                     'assignedlist' => $assignedCategoryList);
    }

    /**
     * Assigns a team enrolled in a category to a specific group
     * @Route("/edit/assign/parel/add/{categoryid}/{playgroundattributeid}", name="_edit_parel_assign")
     * @Method("GET")
     */
    public function addAssignAction($categoryid, $playgroundattributeid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $this->get('logic')->assignCategory($categoryid, $playgroundattributeid);
        return $this->redirect($returnUrl);
    }
    
    /**
     * Removes a team assigned to a specific group
     * @Route("/edit/assign/parel/del/{categoryid}/{playgroundattributeid}", name="_edit_parel_unassign")
     * @Method("GET")
     */
    public function delAssignAction($categoryid, $playgroundattributeid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $this->get('logic')->removeAssignedCategory($categoryid, $playgroundattributeid);
        return $this->redirect($returnUrl);
    }
}
