<?php

/* ICupPublicSiteBundle:Default:playground.full.html.twig */
class __TwigTemplate_b49565688e8f78001df0d7b35db77752 extends Twig_Template
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
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "tournament"), "name"), "html", null, true);
        echo "</h1>
</a>
<p class=\"subcategory\">
    ";
        // line 8
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "playground"), "name"), "html", null, true);
        echo "
</p>
<p>
    <table>
        <thead>
            <tr>
                <td class=\"flag\"></td>
                <td class=\"no\">";
        // line 15
        echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.NO", array(), "messages");
        echo "</td>
                <td class=\"time\">";
        // line 16
        echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.TIME", array(), "messages");
        echo "</td>
                <td class=\"no\">";
        // line 17
        echo $this->env->getExtension('translator')->getTranslator()->trans("CATEGORY", array(), "messages");
        echo "</td>
                <td class=\"no\">";
        // line 18
        echo $this->env->getExtension('translator')->getTranslator()->trans("GROUP", array(), "messages");
        echo "</td>
                <td class=\"flag\"></td>
                <td class=\"team\">";
        // line 20
        echo $this->env->getExtension('translator')->getTranslator()->trans("TEAM", array(), "messages");
        echo " A</td>
                <td class=\"flag\"></td>
                <td class=\"team\">";
        // line 22
        echo $this->env->getExtension('translator')->getTranslator()->trans("TEAM", array(), "messages");
        echo " B</td>
                <td class=\"match\">";
        // line 23
        echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.RESULT", array(), "messages");
        echo "</td>
            </tr>
        </thead>
        <tboby>
";
        // line 27
        $context["catdate"] = "";
        // line 28
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, "matchlist"));
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
        foreach ($context['_seq'] as $context["_key"] => $context["match"]) {
            // line 29
            echo "    ";
            if ((twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), "d-M-Y") != $this->getContext($context, "catdate"))) {
                // line 30
                echo "            <tr class=\"";
                echo twig_escape_filter($this->env, twig_cycle(array(0 => "even", 1 => "odd"), $this->getAttribute($this->getContext($context, "loop"), "index")), "html", null, true);
                echo "\">
                <td class=\"date_solo\" colspan=\"10\">
                    ";
                // line 32
                echo twig_escape_filter($this->env, twig_capitalize_string_filter($this->env, $this->env->getExtension('translator')->trans(twig_join_filter(array(0 => "WEEK.", 1 => twig_upper_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), "D")))))), "html", null, true);
                echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), " j. "), "html", null, true);
                echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans(twig_join_filter(array(0 => "MONTH.", 1 => twig_upper_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), "M"))))), "html", null, true);
                echo "
                </td>
            </tr>
        ";
                // line 35
                $context["catdate"] = twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), "d-M-Y");
                // line 36
                echo "    ";
            }
            // line 37
            echo "            <tr class=\"";
            echo twig_escape_filter($this->env, twig_cycle(array(0 => "even", 1 => "odd"), $this->getAttribute($this->getContext($context, "loop"), "index")), "html", null, true);
            echo "\">
                <td class=\"flag\"></td>
                <td class=\"no\">";
            // line 39
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "matchno"), "html", null, true);
            echo "</td>
                <td class=\"time\">";
            // line 40
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), "H.i"), "html", null, true);
            echo "</td>
                <td class=\"no\">";
            // line 41
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "category"), "html", null, true);
            echo "</td>
                <td class=\"no\">";
            // line 42
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "group"), "html", null, true);
            echo "</td>
                <td class=\"flag\"><img src=\"";
            // line 43
            echo twig_escape_filter($this->env, $this->getContext($context, "imagepath"), "html", null, true);
            echo "/flags/";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "flagA"), "html", null, true);
            echo "\" alt=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryA")), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryA")), "html", null, true);
            echo "\"></td>
                <td class=\"team\"><a href=\"";
            // line 44
            echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showteam", array("teamid" => $this->getAttribute($this->getContext($context, "match"), "idA"), "groupid" => $this->getAttribute($this->getContext($context, "match"), "gid"))), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "teamA"), "html", null, true);
            echo "</a></td>
                <td class=\"flag\"><img src=\"";
            // line 45
            echo twig_escape_filter($this->env, $this->getContext($context, "imagepath"), "html", null, true);
            echo "/flags/";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "flagB"), "html", null, true);
            echo "\" alt=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryB")), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryB")), "html", null, true);
            echo "\"></td>
                <td class=\"team\"><a href=\"";
            // line 46
            echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showteam", array("teamid" => $this->getAttribute($this->getContext($context, "match"), "idB"), "groupid" => $this->getAttribute($this->getContext($context, "match"), "gid"))), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "teamB"), "html", null, true);
            echo "</a></td>
                <td class=\"match\">";
            // line 47
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "scoreA"), "html", null, true);
            echo "-";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "scoreB"), "html", null, true);
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['match'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 50
        echo "        </tboby>
        <tfoot>
            <tr>
                <td colspan=\"10\"></td>
            </tr>
        </tfoot>
    </table>
    </form>
</p>
";
    }

    public function getTemplateName()
    {
        return "ICupPublicSiteBundle:Default:playground.full.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  199 => 50,  180 => 47,  174 => 46,  164 => 45,  158 => 44,  148 => 43,  144 => 42,  140 => 41,  136 => 40,  132 => 39,  126 => 37,  123 => 36,  121 => 35,  113 => 32,  107 => 30,  104 => 29,  87 => 28,  85 => 27,  78 => 23,  74 => 22,  69 => 20,  64 => 18,  60 => 17,  56 => 16,  52 => 15,  42 => 8,  36 => 5,  31 => 4,  28 => 3,);
    }
}
