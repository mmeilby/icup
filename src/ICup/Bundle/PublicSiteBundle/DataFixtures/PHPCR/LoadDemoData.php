<?php

namespace ICup\Bundle\PublicSiteBundle\DataFixtures\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use PHPCR\Util\NodeHelper;
use Symfony\Cmf\Bundle\MenuBundle\Doctrine\Phpcr\MenuNode;
use Symfony\Cmf\Bundle\SimpleCmsBundle\Doctrine\Phpcr\Page;
use Symfony\Cmf\Bundle\BlockBundle\Doctrine\Phpcr\SimpleBlock;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;

/**
 * Loads the initial demo data of the demo website.
 */
class LoadDemoData implements FixtureInterface
{
    private $translator;
    
    /**
     * Load data fixtures with the passed DocumentManager
     *
     * @param DocumentManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->setupTranslator();
        
        $parent = $manager->find(null, '/cms/simple');
        
        // pass add_locale_pattern as true to prefix the route pattern with /{_locale}
        $page = new Page(array('add_locale_pattern' => true));

        $page->setPosition($parent, 'hello-world');
        $page->setTitle('Hello World!');
        $page->setBody('Really interesting stuff...');
        $page->setLabel('Hello World');

        $manager->persist($page);
        $manager->bindTranslation($page, 'en');

        $page->setTitle('Hallo Welt!');
        $page->setBody('Super interessante Sachen...');
        $page->setLabel('Hallo Welt!');

        $manager->bindTranslation($page, 'de');

        $manager->flush();

        $page2 = new Page();
        $page2->setPosition($parent, 'hej');
        $page2->setTitle('Hej verden!');
        $page2->setBody('Meget interessante ting...');
        $page2->setLabel('Hello World');
        $page2->setDefault('_template', 'ICupPublicSiteBundle:Page:home.html.twig');
        $manager->persist($page2);

        // add menu item for home
        $menuRoot = $manager->find(null, '/cms/simple');
        $homeMenuNode = new MenuNode('home');
        $homeMenuNode->setLabel('Home');
        $homeMenuNode->setParent($menuRoot);
        $homeMenuNode->setContent($parent);

        $manager->persist($homeMenuNode);

        // add menu item for login
        $loginMenuNode = new MenuNode('login');
        $loginMenuNode->setLabel('Admin Login');
        $loginMenuNode->setParent($menuRoot);
        $loginMenuNode->setRoute('_demo_login');

        $manager->persist($loginMenuNode);

        // load the blocks
        NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks');
        NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks/frontpage');
        NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks/mypage');
        NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks/connectclub');
        NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks/contact');
        NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks/enrollment');
        NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks/information');

        $this->blockCreator('section_1', 'frontpage', $manager);
        $this->blockCreator('wellcome', 'mypage', $manager);
        $this->blockCreator('chooseclub', 'mypage', $manager);
        $this->blockCreator('follower', 'connectclub', $manager);
        $this->blockCreator('address', 'contact', $manager);
        $this->blockCreator('terms', 'enrollment', $manager);
        $this->blockCreator('intro', 'information', $manager);
        $this->blockCreator('price', 'information', $manager);
        $this->blockCreator('firsttime', 'information', $manager);
        $this->blockCreator('advices', 'information', $manager);
        $this->blockCreator('matches', 'information', $manager);
        $this->blockCreator('packing', 'information', $manager);
        $this->blockCreator('accomodation', 'information', $manager);
        $this->blockCreator('dining', 'information', $manager);
        $this->blockCreator('matchtime', 'information', $manager);
        $this->blockCreator('playgrounds', 'information', $manager);
       
        // save the changes
        $manager->flush();
    }
    
    private function blockCreator($name, $parentname, ObjectManager $manager) {
        $parent = $manager->find(null, '/cms/content/blocks/'.$parentname);
        $block = new SimpleBlock(array('add_locale_pattern' => true));
        $block->setParentObject($parent);
        $block->setName($name);
        $block->setTitle($this->translator->trans($parentname.'.'.$name.'.title', array(), 'messages', 'da'));
        $block->setBody($this->translator->trans($parentname.'.'.$name.'.body', array(), 'messages', 'da'));
        $manager->persist($block);
        $manager->bindTranslation($block, 'da');
        foreach (array('en', 'de', 'fr', 'es', 'it', 'po') as $locale) {
            $block->setTitle($this->translator->trans($parentname.'.'.$name.'.title', array(), 'messages', $locale));
            $block->setBody($this->translator->trans($parentname.'.'.$name.'.body', array(), 'messages', $locale));
            $manager->bindTranslation($block, $locale);
        }
    }
    
    private function setupTranslator() {
        $transPath = __DIR__.'/translations';
        $format = 'yaml';
        $this->translator = new Translator('en');
        $this->translator->setFallbackLocales(array('en'));
        $this->translator->addLoader($format, new YamlFileLoader());
        $this->addTranslatorResource($format, $transPath, 'da');
        $this->addTranslatorResource($format, $transPath, 'en');
        $this->addTranslatorResource($format, $transPath, 'de');
        $this->addTranslatorResource($format, $transPath, 'fr');
        $this->addTranslatorResource($format, $transPath, 'es');
        $this->addTranslatorResource($format, $transPath, 'it');
        $this->addTranslatorResource($format, $transPath, 'po');
    }
    
    private function addTranslatorResource($format, $path, $locale) {
        $this->translator->addResource($format, $path.'/messages.'.$locale.'.yml', $locale);
    }
}
