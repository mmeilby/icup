<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
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
        /* @var $playground Playground */
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        /* @var $site Site */
        $site = $playground->getSite();
        /* @var $tournament Tournament */
        $tournament = $site->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $attributes = $playground->getPlaygroundAttributes();
        $attrList = array();
        /* @var $attr PlaygroundAttribute */
        foreach ($attributes as $attr) {
            $categories = array();
            foreach ($attr->getCategories() as $category) {
                $categories[] = $category->getName();
            }
            if ($attr->getTimeslot()) {
                $timeslot = $attr->getTimeslot()->getName();
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
                'finals' => $attr->getFinals(),
                'classification' => $attr->getClassification()
            );
        }

        return array('host' => $host, 'tournament' => $tournament, 'playground' => $playground, 'attributes' => $attrList);
    }
}
