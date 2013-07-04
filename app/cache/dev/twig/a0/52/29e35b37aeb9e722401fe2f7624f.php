<?php

/* ICupPublicSiteBundle:Default:category.html.twig */
class __TwigTemplate_a05229e35b37aeb9e722401fe2f7624f extends Twig_Template
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
        echo "<a href=\"";
        echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showtournament"), "html", null, true);
        echo "\">
    <h1 class=\"category\">";
        // line 5
        echo $this->env->getExtension('translator')->getTranslator()->trans("CATEGORY", array(), "messages");
        echo " ";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "category"), "name"), "html", null, true);
        echo "</h1>
</a>
<p class=\"subcategory\">
    ";
        // line 8
        echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans(twig_join_filter(array(0 => $this->getAttribute($this->getContext($context, "category"), "gender"), 1 => $this->getAttribute($this->getContext($context, "category"), "classification")))), "html", null, true);
        echo "
    ";
        // line 9
        echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "category"), "classification")), "html", null, true);
        echo "
</p>
";
        // line 11
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, "grouplist"));
        foreach ($context['_seq'] as $context["_key"] => $context["group"]) {
            // line 12
            echo "    <h2 class=\"group\">";
            echo $this->env->getExtension('translator')->getTranslator()->trans("GROUP", array(), "messages");
            echo " ";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getContext($context, "group"), "group"), "name"), "html", null, true);
            echo "</h2>
    <p>
        <table>
            <thead>
                <tr>
                    <td class=\"flag\"></td>
                    <td class=\"team\">";
            // line 18
            echo $this->env->getExtension('translator')->getTranslator()->trans("TEAM", array(), "messages");
            echo "</td>
                    <td class=\"matches\">";
            // line 19
            echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.MATCHES", array(), "messages");
            echo "</td>
                    <td class=\"score\">";
            // line 20
            echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.GOALS.WON", array(), "messages");
            echo "</td>
                    <td class=\"goals\">";
            // line 21
            echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.GOALS.LOST", array(), "messages");
            echo "</td>
                    <td class=\"diff\">";
            // line 22
            echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.GOALS.DIFF", array(), "messages");
            echo "</td>
                    <td class=\"points\">";
            // line 23
            echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.POINTS", array(), "messages");
            echo "</td>
                </tr>
            </thead>
            <tboby>
    ";
            // line 27
            $context['_parent'] = (array) $context;
            $context['_seq'] = twig_ensure_traversable($this->getAttribute($this->getContext($context, "group"), "teams"));
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
            foreach ($context['_seq'] as $context["_key"] => $context["team"]) {
                // line 28
                echo "                <tr class=\"";
                echo twig_escape_filter($this->env, twig_cycle(array(0 => "even", 1 => "odd"), $this->getAttribute($this->getContext($context, "loop"), "index")), "html", null, true);
                echo "\">
                    <td class=\"flag\"><img src=\"";
                // line 29
                echo twig_escape_filter($this->env, $this->getContext($context, "imagepath"), "html", null, true);
                echo "/flags/";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "team"), "flag"), "html", null, true);
                echo "\" alt=\"";
                echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "team"), "country")), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "team"), "country")), "html", null, true);
                echo "\"></td>
                    <td class=\"team\"><a href=\"";
                // line 30
                echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showteam", array("teamid" => $this->getAttribute($this->getContext($context, "team"), "id"), "groupid" => $this->getAttribute($this->getAttribute($this->getContext($context, "group"), "group"), "id"))), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "team"), "name"), "html", null, true);
                echo "</a></td>
                    <td class=\"matches\">";
                // line 31
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "team"), "matches"), "html", null, true);
                echo "</td>
                    <td class=\"score\">";
                // line 32
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "team"), "score"), "html", null, true);
                echo "</td>
                    <td class=\"goals\">";
                // line 33
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "team"), "goals"), "html", null, true);
                echo "</td>
                    <td class=\"diff\">";
                // line 34
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "team"), "diff"), "html", null, true);
                echo "</td>
                    <td class=\"points\">";
                // line 35
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "team"), "points"), "html", null, true);
                echo "</td>
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
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['team'], $context['_parent'], $context['loop']);
            $context = array_merge($_parent, array_intersect_key($context, $_parent));
            // line 38
            echo "            </tboby>
            <tfoot>
                <tr>
                    <td colspan=\"7\"></td>
                </tr>
            </tfoot>
        </table>
    </p>
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['group'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
    }

    public function getTemplateName()
    {
        return "ICupPublicSiteBundle:Default:category.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  167 => 38,  150 => 35,  146 => 34,  142 => 33,  138 => 32,  134 => 31,  128 => 30,  118 => 29,  113 => 28,  96 => 27,  89 => 23,  85 => 22,  81 => 21,  77 => 20,  73 => 19,  69 => 18,  57 => 12,  53 => 11,  48 => 9,  44 => 8,  36 => 5,  31 => 4,  28 => 3,);
    }
}
