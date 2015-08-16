<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use DateTime;

/**
 * List the attributes available for a playground
 */
class ListPlaygroundAttributesController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/edit/pa/list/{playgroundid}", name="_edit_playground_attr_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listplaygroundattr.html.twig")
     */
    public function listAction($playgroundid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $attributes = $this->get('logic')->listPlaygroundAttributes($playgroundid);
        $attrList = array();
        /* @var $attr PlaygroundAttribute */
        foreach ($attributes as $attr) {
            $categories = array();
            $categoryList = $this->get('logic')->listPACategories($attr->getId());
            foreach ($categoryList as $category) {
                $categories[] = $category->getName();
            }
            if ($attr->getTimeslot() > 0) {
                $timeslot = $this->get('entity')->getTimeslotById($attr->getTimeslot())->getName();
            }
            else {
                $timeslot = $this->get('translator')->trans('FORM.PLAYGROUNDATTR.DEFAULT');
            }
            $attrList[] = array(
                'id' => $attr->getId(),
                'timeslot' => $timeslot,
                'start' => $attr->getStartSchedule(),
                'end' => $attr->getEndSchedule(),
                'categories' => $categories,
                'finals' => $attr->getFinals()
            );
        }

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('host' => $host, 'tournament' => $tournament, 'playground' => $playground, 'attributes' => $attrList);
    }
}
