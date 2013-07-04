<?php

/* base.html.twig */
class __TwigTemplate_e2cf5dae579a38f00d43003e786cdda5 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'stylesheets' => array($this, 'block_stylesheets'),
            'body' => array($this, 'block_body'),
            'javascripts' => array($this, 'block_javascripts'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!DOCTYPE html>
<html>
    <head>
        <meta charset=\"UTF-8\" />
        <title>";
        // line 5
        $this->displayBlock('title', $context, $blocks);
        echo "</title>
        ";
        // line 6
        if (isset($context['assetic']['debug']) && $context['assetic']['debug']) {
            // asset "e81fe90_0"
            $context["asset_url"] = isset($context['assetic']['use_controller']) && $context['assetic']['use_controller'] ? $this->env->getExtension('routing')->getPath("_assetic_e81fe90_0") : $this->env->getExtension('assets')->getAssetUrl("_controller/bundles/icuppublicsite/css/e81fe90_part_1_basic_1.css");
            // line 7
            echo "            <link rel=\"stylesheet\" href=\"";
            echo twig_escape_filter($this->env, $this->getContext($context, "asset_url"), "html", null, true);
            echo "\" />
        ";
        } else {
            // asset "e81fe90"
            $context["asset_url"] = isset($context['assetic']['use_controller']) && $context['assetic']['use_controller'] ? $this->env->getExtension('routing')->getPath("_assetic_e81fe90") : $this->env->getExtension('assets')->getAssetUrl("_controller/bundles/icuppublicsite/css/e81fe90.css");
            echo "            <link rel=\"stylesheet\" href=\"";
            echo twig_escape_filter($this->env, $this->getContext($context, "asset_url"), "html", null, true);
            echo "\" />
        ";
        }
        unset($context["asset_url"]);
        // line 9
        echo "        ";
        $this->displayBlock('stylesheets', $context, $blocks);
        // line 10
        echo "        <link rel=\"icon\" type=\"image/x-icon\" href=\"";
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("favicon.ico"), "html", null, true);
        echo "\" />
    </head>
    <body>
        <div class=\"centerpage\">
            ";
        // line 14
        $this->displayBlock('body', $context, $blocks);
        // line 15
        echo "        </div>
        ";
        // line 16
        $this->displayBlock('javascripts', $context, $blocks);
        // line 17
        echo "    </body>
</html>
";
    }

    // line 5
    public function block_title($context, array $blocks = array())
    {
        echo "Interamnia World Cup 2013 - RESULTS - icup.dk";
    }

    // line 9
    public function block_stylesheets($context, array $blocks = array())
    {
    }

    // line 14
    public function block_body($context, array $blocks = array())
    {
    }

    // line 16
    public function block_javascripts($context, array $blocks = array())
    {
    }

    public function getTemplateName()
    {
        return "base.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  91 => 16,  86 => 14,  81 => 9,  75 => 5,  69 => 17,  67 => 16,  64 => 15,  62 => 14,  54 => 10,  51 => 9,  37 => 7,  33 => 6,  29 => 5,  23 => 1,);
    }
}
