<?php

/* ICupPublicSiteBundle:Default:editmatch.html.twig */
class __TwigTemplate_b083cdd6256e378faefe8493b7a50522 extends Twig_Template
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
        echo "<p class=\"subgroup\">";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "playground"), "name"), "html", null, true);
        echo "</p>
<p>
    <form action=\"";
        // line 6
        echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_editmatchpost"), "html", null, true);
        echo "\" method=\"post\">
    <input type=\"submit\" value=\"Save\">
    <table>
        <thead>
            <tr>
                <td class=\"no\">";
        // line 11
        echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.NO", array(), "messages");
        echo "</td>
                <td class=\"time\">";
        // line 12
        echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.TIME", array(), "messages");
        echo "</td>
                <td class=\"flag\"></td>
                <td class=\"team\">";
        // line 14
        echo $this->env->getExtension('translator')->getTranslator()->trans("TEAM", array(), "messages");
        echo " A</td>
                <td class=\"flag\"></td>
                <td class=\"team\">";
        // line 16
        echo $this->env->getExtension('translator')->getTranslator()->trans("TEAM", array(), "messages");
        echo " B</td>
                <td colspan=\"2\">";
        // line 17
        echo $this->env->getExtension('translator')->getTranslator()->trans("LEGEND.RESULT", array(), "messages");
        echo "</td>
            </tr>
        </thead>
        <tboby>
";
        // line 21
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
            // line 22
            echo "            <tr class=\"";
            echo twig_escape_filter($this->env, twig_cycle(array(0 => "even", 1 => "odd"), $this->getAttribute($this->getContext($context, "loop"), "index")), "html", null, true);
            echo "\">
                <td class=\"date\" colspan=\"8\">
                    ";
            // line 24
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), "d-M-Y"), "html", null, true);
            echo "
                    ";
            // line 25
            echo $this->env->getExtension('translator')->getTranslator()->trans("CATEGORY", array(), "messages");
            echo " ";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "category"), "html", null, true);
            echo " ";
            echo $this->env->getExtension('translator')->getTranslator()->trans("GROUP", array(), "messages");
            echo " ";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "group"), "html", null, true);
            echo "    
                </td>
            </tr>
            <tr class=\"";
            // line 28
            echo twig_escape_filter($this->env, twig_cycle(array(0 => "even", 1 => "odd"), $this->getAttribute($this->getContext($context, "loop"), "index")), "html", null, true);
            echo "\">
                <td class=\"no\">";
            // line 29
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "matchno"), "html", null, true);
            echo "</td>
                <td class=\"time\">";
            // line 30
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "schedule"), "H.i"), "html", null, true);
            echo "</td>
                <td class=\"flag\"><img src=\"";
            // line 31
            echo twig_escape_filter($this->env, $this->getContext($context, "imagepath"), "html", null, true);
            echo "/flags/";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "flagA"), "html", null, true);
            echo "\" alt=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryA")), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryA")), "html", null, true);
            echo "\"></td>
                <td class=\"team\"><a href=\"";
            // line 32
            echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showteam", array("teamid" => $this->getAttribute($this->getContext($context, "match"), "idA"), "groupid" => 0)), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "teamA"), "html", null, true);
            echo "</a></td>
                <td class=\"flag\"><img src=\"";
            // line 33
            echo twig_escape_filter($this->env, $this->getContext($context, "imagepath"), "html", null, true);
            echo "/flags/";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "flagB"), "html", null, true);
            echo "\" alt=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryB")), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('translator')->trans($this->getAttribute($this->getContext($context, "match"), "countryB")), "html", null, true);
            echo "\"></td>
                <td class=\"team\"><a href=\"";
            // line 34
            echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("_showteam", array("teamid" => $this->getAttribute($this->getContext($context, "match"), "idB"), "groupid" => 0)), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "teamB"), "html", null, true);
            echo "</a></td>
                <td class=\"match\">
                    <input type=\"text\" name=\"score_";
            // line 36
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "ridA"), "html", null, true);
            echo "\" size=\"1\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "scoreA"), "html", null, true);
            echo "\" />
                </td>
                <td class=\"match\">
                    <input type=\"text\" name=\"score_";
            // line 39
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "ridB"), "html", null, true);
            echo "\" size=\"1\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, "match"), "scoreB"), "html", null, true);
            echo "\" />
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['match'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 43
        echo "        </tboby>
        <tfoot>
            <tr>
                <td colspan=\"8\"></td>
            </tr>
        </tfoot>
    </table>
    </form>
</p>
";
    }

    public function getTemplateName()
    {
        return "ICupPublicSiteBundle:Default:editmatch.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  182 => 43,  162 => 39,  154 => 36,  147 => 34,  137 => 33,  131 => 32,  121 => 31,  117 => 30,  113 => 29,  109 => 28,  97 => 25,  93 => 24,  87 => 22,  70 => 21,  63 => 17,  59 => 16,  54 => 14,  49 => 12,  45 => 11,  37 => 6,  31 => 4,  28 => 3,);
    }
}
