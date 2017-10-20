<?php

/* admin/head.twig */
class __TwigTemplate_22956e09dac32edd9959bf416ae2a7dc5056d6f1363ddcc5542cdbfe0b482da3 extends Twig_Template
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
        echo "<head>
    <meta charset=\"utf-8\">
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <title>Mapp Integrator</title>
    ";
        // line 6
        $this->loadTemplate("bootstrap/bootstrap.css.twig", "admin/head.twig", 6)->display($context);
        // line 7
        echo "    <link rel=\"stylesheet\" href=\"css/admin.css\" />
\t";
        // line 8
        $this->loadTemplate("bootstrap/bootstrap.js.twig", "admin/head.twig", 8)->display($context);
        // line 9
        echo "</head>";
    }

    public function getTemplateName()
    {
        return "admin/head.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  33 => 9,  31 => 8,  28 => 7,  26 => 6,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/head.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\head.twig");
    }
}
