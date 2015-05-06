<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
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
        $pattr = $this->get('entity')->getPlaygroundAttributeById($playgroundattributeid);
        $playground = $this->get('entity')->getPlaygroundById($pattr->getPid());
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $categories = $this->get('logic')->listCategories($tournament->getId());
        $categoryList = array();
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = $category;
        }

        $assignedCategoryList = array();
        $paRelations = $this->get('logic')->listPARelations($pattr->getId());
        foreach ($paRelations as $paRelation) {
            $category = $categoryList[$paRelation->getCid()];
            $assignedCategoryList[$paRelation->getCid()] = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'gender' => $category->getGender(),
                'age' => $category->getAge(),
                'classification' => $category->getClassification(),
                'matchtime' => $paRelation->getMatchtime(),
                'finals' => $paRelation->getFinals()
            );
        }

        $unassignedCategoryList = array_diff_key($categoryList, $assignedCategoryList);
        
        return array('host' => $host,
                     'tournament' => $tournament,
                     'playground' => $playground,
                     'attribute' => $pattr,
                     'attr_schedule' => DateTime::createFromFormat(
                                $this->container->getParameter('db_date_format').
                                '-'.
                                $this->container->getParameter('db_time_format'),
                                $pattr->getDate().'-'.$pattr->getStart()),
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
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $this->get('logic')->assignCategory($categoryid, $playgroundattributeid, $category->getMatchtime(), false);
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
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $this->get('logic')->removeAssignedCategory($categoryid, $playgroundattributeid);
        return $this->redirect($returnUrl);
    }
}
