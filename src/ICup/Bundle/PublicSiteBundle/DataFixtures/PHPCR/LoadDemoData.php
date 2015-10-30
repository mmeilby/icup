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
use Symfony\Component\Yaml\Parser;

/**
 * Loads the initial demo data of the demo website.
 */
class LoadDemoData implements FixtureInterface
{
    private $translator;
    private $domains;
    
    /**
     * Load data fixtures with the passed DocumentManager
     *
     * @param DocumentManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->setupTranslator();
        // load the blocks
        foreach ($this->domains as $domain => $sections) {
            $parent = $manager->find(null, '/cms/content/blocks/'.$domain);
            if (null == $parent) {
                NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks/' . $domain);
                $parent = $manager->find(null, '/cms/content/blocks/'.$domain);
            }
            foreach ($sections as $section => $messages) {
                $this->blockCreator($parent, $section, $domain, $manager);
            }
        }
        // save the changes
        $manager->flush();
    }
    
    private function blockCreator($parent, $name, $parentname, ObjectManager $manager) {
        $block = $manager->find(null, '/cms/content/blocks/'.$parentname.'/'.$name);
        if (null == $block) {
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
        
        $yaml = new Parser();
        $this->domains = $yaml->parse(file_get_contents($transPath.'/messages.da.yml'));
    }
    
    private function addTranslatorResource($format, $path, $locale) {
        $this->translator->addResource($format, $path.'/messages.'.$locale.'.yml', $locale);
    }
}


/*
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
        if (null == $manager->find(null, '/cms/content/blocks')) {
            NodeHelper::createPath($manager->getPhpcrSession(), '/cms/content/blocks');
        }
*/
