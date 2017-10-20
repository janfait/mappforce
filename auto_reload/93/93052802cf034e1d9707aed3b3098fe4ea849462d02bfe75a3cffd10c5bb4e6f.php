<?php

/* admin/footer.twig */
class __TwigTemplate_2027cdea2c3d66cfcad545880f3e7649fc7e1b9e9b52a731487e4309eaa8405e extends Twig_Template
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
        // line 2
        echo "</body>
</html>";
    }

    public function getTemplateName()
    {
        return "admin/footer.twig";
    }

    public function getDebugInfo()
    {
        return array (  19 => 2,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/footer.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\footer.twig");
    }
}
