<?php

/* admin/menu.twig */
class __TwigTemplate_f8ff9ba82165845e7c84e97f162e582f1a37cf8ff3eeb0311fe5142fc4b63148 extends Twig_Template
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
<nav class=\"navbar navbar-inverse navbar-fixed-top\">
    <div class=\"container-fluid\">
        <div class=\"navbar-header\">
            <button type=\"button\" class=\"navbar-toggle collapsed\" data-toggle=\"collapse\" data-target=\"#navbar\" aria-expanded=\"false\" aria-controls=\"navbar\">
                <span class=\"sr-only\">Toggle navigation</span>
                <span class=\"icon-bar\"></span>
                <span class=\"icon-bar\"></span>
                <span class=\"icon-bar\"></span>
            </button>
            <a class=\"navbar-brand\" href=\"/admin\">Mapp Integrator</a>
        </div>
        <div id=\"navbar\" class=\"navbar-collapse collapse\">
            <ul class=\"nav navbar-nav navbar-right\">
                <!-- Add your items to the top menu -->
                <!-- <li><a href=\"/admin/urls\"><i class=\"fa fa-file-text\"></i> Urls</a></li> -->
            </ul>
        </div>
    </div>
</nav>";
    }

    public function getTemplateName()
    {
        return "admin/menu.twig";
    }

    public function getDebugInfo()
    {
        return array (  19 => 3,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/menu.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\menu.twig");
    }
}
