<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Entity\NewsForm;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

/**
 * Maintain tournament news
 */
class NewsController extends Controller
{
    /**
     * Add new news record
     * @Route("/edit/news/add/{tournamentid}", name="_edit_news_add")
     * @Template("ICupPublicSiteBundle:Host:editnews.html.twig")
     */
    public function addAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $newsForm = new NewsForm();
        $newsForm->setPid($tournament->getId());
        $newsForm->setNewstype(News::$TYPE_TIMELIMITED);
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $newsForm->setDate(date_format(new DateTime(), $dateformat));
        $locale = $request->getLocale();
        $newsForm->setLanguage($locale);
        $form = $this->makeNewsForm($newsForm, 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $newsForm)) {
            $otherNews = $this->get('tmnt')->getNewsByNo($tournament->getId(), $newsForm->getNewsno());
            foreach ($otherNews as $onews) {
                if ($onews->getLanguage() == $newsForm->getLanguage()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NEWSEXISTS', array(), 'admin')));
                    break;
                }
            }
            if ($form->isValid()) {
                $news = new News();
                $news->setPid($tournamentid);
                $news->setCid(0);
                $news->setMid(0);
                $this->updateNews($newsForm, $news);
                $em = $this->getDoctrine()->getManager();
                $em->persist($news);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'tournament' => $tournament);
    }
    
    /**
     * Change information of an existing news record
     * @Route("/edit/news/chg/{newsid}", name="_edit_news_chg")
     * @Template("ICupPublicSiteBundle:Host:editnews.html.twig")
     */
    public function chgAction($newsid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $news News */
        $news = $this->get('entity')->getNewsById($newsid);
        $tournament = $this->get('entity')->getTournamentById($news->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $newsForm = $this->copyNewsForm($news);
        $form = $this->makeNewsForm($newsForm, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $newsForm)) {
            $otherNews = $this->get('tmnt')->getNewsByNo($tournament->getId(), $newsForm->getNewsno());
            foreach ($otherNews as $onews) {
                if ($onews->getLanguage() == $newsForm->getLanguage() && $onews->getId() != $newsForm->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.CANTCHANGENEWS', array(), 'admin')));
                    break;
                }
            }
            if ($form->isValid()) {
                $this->updateNews($newsForm, $news);
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'tournament' => $tournament);
    }
    
    /**
     * Remove news record from the register
     * @Route("/edit/news/del/{newsid}", name="_edit_news_del")
     * @Template("ICupPublicSiteBundle:Host:editnews.html.twig")
     */
    public function delAction($newsid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $news = $this->get('entity')->getNewsById($newsid);
        $tournament = $this->get('entity')->getTournamentById($news->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $newsForm = $this->copyNewsForm($news);
        $form = $this->makeNewsForm($newsForm, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($news);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'del', 'tournament' => $tournament);
    }

    private function updateNews(NewsForm $newsForm, News &$news) {
        $news->setNewsno($newsForm->getNewsno());
        $news->setNewstype($newsForm->getNewstype());
        $news->setLanguage($newsForm->getLanguage());
        $news->setTitle($newsForm->getTitle());
        $news->setContext($newsForm->getContext());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $eventdate = date_create_from_format($dateformat, $newsForm->getDate());
        $news->setDate(Date::getDate($eventdate));
    }
    
    private function copyNewsForm(News $news) {
        $newsForm = new NewsForm();
        $newsForm->setId($news->getId());
        $newsForm->setPid($news->getPId());
        $newsForm->setNewsno($news->getNewsno());
        $newsForm->setNewstype($news->getNewstype());
        $newsForm->setLanguage($news->getLanguage());
        $newsForm->setTitle($news->getTitle());
        $newsForm->setContext($news->getContext());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $eventdate = Date::getDateTime($news->getDate());
        $newsForm->setDate(date_format($eventdate, $dateformat));
        $newsForm->setCid($news->getCid());
        $newsForm->setMid($news->getMid());
        return $newsForm;
    }

    private function makeNewsForm(NewsForm $newsForm, $action) {
        $newstypes = array();
        foreach (
            array(
                News::$TYPE_PERMANENT,
                News::$TYPE_TIMELIMITED
            )
        as $id) {
            $newstypes[$id] = 'FORM.NEWS.TYPES.'.$id;
        }
        $languages = array();
        foreach ($this->get('util')->getSupportedLocales() as $locale) {
            $language = $this->get('translator')->trans("LANG_LOCAL.".strtoupper($locale), array(), 'common');
            $languages[$locale] = $language;
        }
        asort($languages);
        $show = $action != 'del';
        
        $formDef = $this->createFormBuilder($newsForm);
        $formDef->add('newsno', 'text', array('label' => 'FORM.NEWS.NO',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('language', 'choice', array('label' => 'FORM.NEWS.LANGUAGE',
                                                  'choices' => $languages,
                                                  'empty_value' => 'FORM.NEWS.DEFAULT',
                                                  'required' => false,
                                                  'disabled' => !$show,
                                                  'translation_domain' => 'admin'));
        $formDef->add('newstype', 'choice', array('label' => 'FORM.NEWS.TYPE',
            'choices' => $newstypes, 'empty_value' => 'FORM.NEWS.DEFAULT',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('date', 'text', array('label' => 'FORM.NEWS.DATE',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('title', 'text', array('label' => 'FORM.NEWS.SUBJECT',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('context', 'text', array('label' => 'FORM.NEWS.CONTEXT',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.NEWS.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.NEWS.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, NewsForm $newsForm) {
        if (!$form->isValid()) {
            return false;
        }
        if ($newsForm->getNewsno() == null || trim($newsForm->getNewsno()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NONO', array(), 'admin')));
        }
        if ($newsForm->getNewstype() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NONEWS', array(), 'admin')));
        }
        if ($newsForm->getLanguage() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NOLANGUAGE', array(), 'admin')));
        }
        if ($newsForm->getTitle() == null || trim($newsForm->getTitle()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NOTITLE', array(), 'admin')));
        }
        if ($newsForm->getContext() == null || trim($newsForm->getContext()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NOCONTEXT', array(), 'admin')));
        }
        if ($newsForm->getDate() == null || trim($newsForm->getDate()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NODATE', array(), 'admin')));
        }
        else {
            $date = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $newsForm->getDate());
            if ($date === false) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.BADDATE', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}
