<?php

/* admin/pages/mapping_add.twig */
class __TwigTemplate_7655b8fcfdf49916991c67eb3dd23124394619fd623dd722108367adb95ff485 extends Twig_Template
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
        <h4 class=\"modal-title\">Add New Mapping for Member Attributes</h4>
      </div>
      <div class=\"modal-body\">
\t\t<form role=\"form\" method=\"POST\" action=\"";
        // line 13
        echo twig_escape_filter($this->env, (isset($context["base_url"]) ? $context["base_url"] : null), "html", null, true);
        echo "/mapping\">
\t\t\t<div class=\"form-group\">
\t\t\t\t<label for=\"cep\">CEP Member Attribute</label>
\t\t\t\t<input type=\"text\" class=\"form-control\" id=\"cep\" name=\"cep\" placeholder=\"Type the member attribute name\" value=\"\">
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
</div>";
    }

    public function getTemplateName()
    {
        return "admin/pages/mapping_add.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  33 => 13,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "admin/pages/mapping_add.twig", "C:\\wamp64\\www\\mapp-integrator-dev\\templates\\admin\\pages\\mapping_add.twig");
    }
}
