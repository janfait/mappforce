<?php

/* admin/pages/mapping_edit.twig */
class __TwigTemplate_b69a453c96d338e4b5a744d158188ab3d0c567954a1cf744e0656eab720ed8ff extends Twig_Template
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
<!-- Modal -->
<div id=\"add\" class=\"modal fade\" role=\"dialog\">
  <div class=\"modal-dialog\">

    <!-- Modal content-->
    <div class=\"modal-content\">
      <div class=\"modal-header\">
        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
        <h4 class=\"modal-title\">Edit existing mapping</h4>
      </div>
      <div class=\"modal-body\">
\t\t<form role=\"form\" method=\"POST\" action=\"";
        // line 13
        echo twig_escape_filter($this->env, (isset($context["base_url"]) ? $context["base_url"] : null), "html", null, true);
        echo "/mapping\">
\t\t\t<div class=\"form-group\">
\t\t\t\t<label for=\"cep\">CEP Attribute</label>
\t\t\t\t<input type=\"text\" class=\"form-control\" id=\"cep\" name=\"cep\" placeholder=\"\" value=\"";
        // line 16
        echo twig_escape_filter($this->env, (isset($context["mapping_id"]) ? $context["mapping_id"] : null), "html", null, true);
        echo "\" disabled>
\t\t\t</div>
\t\t\t<div class=\"form-group\">
\t\t\t\t<label for=\"cep_value\">CEP Value</label>
\t\t\t\t<select class=\"form-control\" id=\"cep_value\" name=\"cep_value\"></select>
\t\t\t</div>
\t\t\t<div class=\"form-group\">
\t\t\t\t<label for=\"function\">Mapping Function</label>
\t\t\t\t<select class=\"form-control\" id=\"function\" name=\"function\">
\t\t\t\t\t<option value=\"none\" selected>None</option>
\t\t\t\t\t<option value=\"none\">Add</option>
\t\t\t\t\t<option value=\"none\">Subtract</option>
\t\t\t\t\t<option value=\"none\">Concatenate to</option>
\t\t\t\t</select>
\t\t\t</div>
\t\t\t<div class=\"form-group\">
\t\t\t\t<label for=\"sfdc\">Map to SFDC field</label>
\t\t\t\t<select name=\"sfdc\" class=\"form-control\" id=\"sfdc\" name=\"sfdc\"></select>
\t\t\t</div>
\t\t\t<div class=\"form-group\">
\t\t\t\t<label for=\"sfdc_value\">Map to SFDC value</label>
\t\t\t\t<input type=\"text\" name=\"sfdc_value\" class=\"form-control\" id=\"sfdc_value\" name=\"sfdc_value\">
\t\t\t</div>
\t\t\t<button type=\"submit\" class=\"btn btn-info\">Submit</button>
\t\t</form>
      </div>
    </div>

  </div>
</div>


";
    }

    public function getTemplateName()
    {
        return "admin/pages/mapping_edit.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  39 => 16,  33 => 13,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/pages/mapping_edit.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\pages\\mapping_edit.twig");
    }
}
