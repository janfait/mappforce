<?php

/* admin/header.twig */
class __TwigTemplate_8d27601ec9909d358bdbee1daf4c1cc887ad262349eb30dea0577c9f8301a6a9 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!doctype html>
<html lang=\"it\">
";
        // line 3
        $this->loadTemplate("admin/head.twig", "admin/header.twig", 3)->display($context);
        // line 4
        echo "<body class=\"wrap wider\">";
    }

    public function getTemplateName()
    {
        return "admin/header.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  25 => 4,  23 => 3,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/header.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\header.twig");
    }
}
