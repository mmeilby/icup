<?php

/* ICupPublicSiteBundle:Default:tournament.html.twig */
class __TwigTemplate_81cf716a7497a9dab25cc285028c0107 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = $this->env->loadTemplate("base.html.twig");

        $this->blocks = array(
            'body' => array($this, 'block_body'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_body($context, array $blocks = array())
    {
        // line 4
        echo "<h1 class=\"category\">";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "tournament"), "name"), "html", null, true);
        echo "</h1>
<p class=\"subcategory\">";
        // line 5
        echo $this->env->getExtension('translator')->getTranslator()->trans("RESULTS", array(), "messages");
        echo "</p>
<div style=\"float: left; padding-right: 20px;\">
    <table>
        <thead>
            <tr>
                <td>";
        // line 10
        echo $this->env->getExtension('translator')->getTranslator()->trans("CATEGORIES", array(), "messages");
        echo "</td>
            </tr>
        </thead>
        <tboby>
";
        // line 14
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, "categories"));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["category"]) {
            // line 15
            echo "            <tr class=\"";
            echo twig_escape_filter($this->env, twig_cycle(array(0 => "even", 1 => "odd"), $this->getAttribute($this->getContext($context, "loop"), "index")), "html", null, true);
            echo "\">
                <td class=\"categories\">
                    <a href=\"";
            // line 17
            echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showcategory", array("categoryid" => $this->getAttribute($this->getContext($context, "category"), "id"))), "html", null, true);
            echo "\">
                        <b>";
            // line 18
            echo $this->env->getExtension('translator')->getTranslator()->trans("CATEGORY", array(), "messages");
            echo " ";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "category"), "name"), "html", null, true);
            echo "</b>
                    </a><br />
                        ";
            // line 20
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans(twig_join_filter(array(0 => $this->getAttribute($this->getContext($context, "category"), "gender"), 1 => $this->getAttribute($this->getContext($context, "category"), "classification")))), "html", null, true);
            echo "
                        ";
            // line 21
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "category"), "classification")), "html", null, true);
            echo "
                </td>
            </tr>
";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['category'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 25
        echo "        </tboby>
        <tfoot>
            <tr>
                <td colspan=\"1\"></td>
            </tr>
        </tfoot>
    </table>
</div>
<div style=\"float: left;\">
    <table>
        <thead>
            <tr>
                <td>";
        // line 37
        echo $this->env->getExtension('translator')->getTranslator()->trans("PLAYGROUNDS", array(), "messages");
        echo "</td>
            </tr>
        </thead>
        <tboby>
";
        // line 41
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, "playgrounds"));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["playground"]) {
            // line 42
            echo "            <tr class=\"";
            echo twig_escape_filter($this->env, twig_cycle(array(0 => "even", 1 => "odd"), $this->getAttribute($this->getContext($context, "loop"), "index")), "html", null, true);
            echo "\">
                <td class=\"playgrounds\">
                    <a href=\"";
            // line 44
            echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showplayground_full", array("playgroundid" => $this->getAttribute($this->getContext($context, "playground"), "id"))), "html", null, true);
            echo "\">
                        ";
            // line 45
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "playground"), "name"), "html", null, true);
            echo "
                    </a>
                </td>
            </tr>
";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['playground'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 50
        echo "        </tboby>
        <tfoot>
            <tr>
                <td colspan=\"1\"></td>
            </tr>
        </tfoot>
    </table>
</div>
";
    }

    public function getTemplateName()
    {
        return "ICupPublicSiteBundle:Default:tournament.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  174 => 50,  155 => 45,  151 => 44,  145 => 42,  128 => 41,  121 => 37,  107 => 25,  89 => 21,  85 => 20,  78 => 18,  74 => 17,  68 => 15,  51 => 14,  44 => 10,  36 => 5,  31 => 4,  28 => 3,);
    }
}
