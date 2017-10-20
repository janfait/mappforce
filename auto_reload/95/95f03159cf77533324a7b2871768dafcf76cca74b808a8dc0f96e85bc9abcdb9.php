<?php

/* admin/pages/mapping.twig */
class __TwigTemplate_1f09d11c5285ca51d55ffb19ae66f89f93dddd89cd4ce6fbbe9f82d3c8dab252 extends Twig_Template
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
        echo "
";
        // line 2
        $this->loadTemplate("admin/header.twig", "admin/pages/mapping.twig", 2)->display($context);
        // line 3
        $this->loadTemplate("admin/menu.twig", "admin/pages/mapping.twig", 3)->display($context);
        // line 4
        echo "
<div class=\"container-fluid\">
    <div class=\"row\">
        ";
        // line 7
        $this->loadTemplate("admin/sidebar.twig", "admin/pages/mapping.twig", 7)->display($context);
        // line 8
        echo "        <div class=\"col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main\">
            <h1 class=\"page-header\">Admin</h1>
            <p><strong>Hello, ";
        // line 10
        echo twig_escape_filter($this->env, $this->getAttribute((isset($context["user"]) ? $context["user"] : null), "firstName", array()), "html", null, true);
        echo " welcome to the Mapp Integrator admin page</strong></p>
        </div>
    </div>
\t<div class=\"row\">
        <div class=\"col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main\">
            <h2 class=\"\">Mapping</h2>
\t\t\t<h3>Lead</h3>
            <p>Select the Salesforce Lead fields you want the CEP attributes to be mapped to</p>
\t\t\t<h4>Standard Attributes</h4>
\t\t\t<table class=\"table table-responsive table-hover\">
\t\t\t\t<thead>
\t\t\t\t\t<tr><th>Name</th><th>Type</th><th>Mapping function</th><th>Map to Salesforce field</th></tr>
\t\t\t\t</thead>
\t\t\t
\t\t\t\t";
        // line 24
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["cep_standard_attributes"]) ? $context["cep_standard_attributes"] : null));
        foreach ($context['_seq'] as $context["_key"] => $context["attribute"]) {
            // line 25
            echo "\t\t\t\t\t<tr>
\t\t\t\t\t\t<td>";
            // line 26
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", array()), "html", null, true);
            echo "</td><td>";
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "type", array()), "html", null, true);
            echo "</td>
\t\t\t\t\t\t<td><select class=\"form-control\" name=\"user.";
            // line 27
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", array()), "html", null, true);
            echo ".sfdc\"></select></td>
\t\t\t\t\t\t<td><select class=\"form-control\" name=\"user.";
            // line 28
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", array()), "html", null, true);
            echo ".sfdc.function\"></select></td>
\t\t\t\t\t\t<td><a class=\"btn btn-info\" data-toggle=\"modal\" data-target=\"#edit\">Edit</a></td>
\t\t\t\t\t</tr>
\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['attribute'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 32
        echo "\t\t\t</table>

\t\t\t<h4>Custom Attributes</h4>
\t\t\t<table class=\"table table-responsive table-hover\">
\t\t\t\t<thead>
\t\t\t\t\t<th>Name</th><th>Type</th><th>Mapping function</th><th>Map to Salesforce field</th>
\t\t\t\t</thead>
\t\t\t
\t\t\t\t";
        // line 40
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["cep_custom_attributes"]) ? $context["cep_custom_attributes"] : null));
        foreach ($context['_seq'] as $context["_key"] => $context["attribute"]) {
            // line 41
            echo "\t\t\t\t\t<tr>
\t\t\t\t\t\t<td>";
            // line 42
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", array()), "html", null, true);
            echo "</td><td>";
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "type", array()), "html", null, true);
            echo "</td>
\t\t\t\t\t\t<td><select class=\"form-control\" name=\"user.";
            // line 43
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", array()), "html", null, true);
            echo ".sfdc\"></select></td>
\t\t\t\t\t\t<td><select class=\"form-control\" name=\"user.";
            // line 44
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", array()), "html", null, true);
            echo ".sfdc.function\"></select></td>
\t\t\t\t\t\t<td><a class=\"btn btn-info\" data-toggle=\"modal\" data-target=\"#edit\">Edit</a></td>
\t\t\t\t\t</tr>
\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['attribute'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 48
        echo "\t\t\t</table>
\t\t\t
\t\t\t
\t\t\t<h4>Member Attributes</h4>
\t\t\t
\t\t\t<div><a href=\"";
        // line 53
        echo twig_escape_filter($this->env, (isset($context["base_url"]) ? $context["base_url"] : null), "html", null, true);
        echo "/mapping/add\" class=\"btn btn-info\">Add New Member Attribute</a></div>
\t\t\t<table>
\t\t\t\t<thead>
\t\t\t\t\t<th>Name</th><th>Type</th><th>Mapping function</th><th>Map to Salesforce field</th>
\t\t\t\t</thead>
\t\t\t\t";
        // line 58
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["cep_member_attributes"]) ? $context["cep_member_attributes"] : null));
        foreach ($context['_seq'] as $context["_key"] => $context["attribute"]) {
            // line 59
            echo "\t\t\t\t\t<tr><td>";
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", array()), "html", null, true);
            echo "</td><td>";
            echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "type", array()), "html", null, true);
            echo "</td><td><select></select></td><td><select></select></td></tr>
\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['attribute'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 61
        echo "\t\t\t</table>
\t\t\t
        </div>
    </div>
</div>


";
        // line 68
        $this->loadTemplate("admin/pages/mapping_edit.twig", "admin/pages/mapping.twig", 68)->display($context);
        // line 69
        $this->loadTemplate("admin/pages/mapping_add.twig", "admin/pages/mapping.twig", 69)->display($context);
        // line 70
        echo "
";
        // line 71
        $this->loadTemplate("admin/footer.twig", "admin/pages/mapping.twig", 71)->display($context);
    }

    public function getTemplateName()
    {
        return "admin/pages/mapping.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  164 => 71,  161 => 70,  159 => 69,  157 => 68,  148 => 61,  137 => 59,  133 => 58,  125 => 53,  118 => 48,  108 => 44,  104 => 43,  98 => 42,  95 => 41,  91 => 40,  81 => 32,  71 => 28,  67 => 27,  61 => 26,  58 => 25,  54 => 24,  37 => 10,  33 => 8,  31 => 7,  26 => 4,  24 => 3,  22 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/pages/mapping.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\pages\\mapping.twig");
    }
}
