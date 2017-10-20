<?php

/* admin/sidebar.twig */
class __TwigTemplate_daddd24fdb8fc10891469ea9103c7822ec0a6bc1cb1832acd5f3230729b61742 extends Twig_Template
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
        // line 3
        echo "
<div class=\"col-sm-3 col-md-2 sidebar\">
    <ul class=\"nav nav-sidebar\">
        <li><a href=\"./\">Home</a></li>
\t\t<li><a href=\"";
        // line 7
        echo twig_escape_filter($this->env, (isset($context["base_url"]) ? $context["base_url"] : null), "html", null, true);
        echo "/settings\">Settings</a></li>
        <li><a href=\"";
        // line 8
        echo twig_escape_filter($this->env, (isset($context["base_url"]) ? $context["base_url"] : null), "html", null, true);
        echo "/mapping\">Mapping</a></li>
\t\t<li><a href=\"";
        // line 9
        echo twig_escape_filter($this->env, (isset($context["base_url"]) ? $context["base_url"] : null), "html", null, true);
        echo "/logs\">Logs</a></li>
\t\t<br>
\t\t<li><a href=\"#\">Mapp CEP Login</a></li>
\t\t<li><a href=\"mailto:jan.fait@mapp.com\">Help</a></li>
    </ul>
</div>";
    }

    public function getTemplateName()
    {
        return "admin/sidebar.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  33 => 9,  29 => 8,  25 => 7,  19 => 3,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/sidebar.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\sidebar.twig");
    }
}
