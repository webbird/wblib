<!-- wblib/wbFormBuilder/form.tpl -->
<script type="text/javascript" src="{{ WBLIB_BASE_URL }}/wblib/css/default/tooltip/rounded-corners.js"></script>
<script type="text/javascript" src="{{ WBLIB_BASE_URL }}/wblib/css/default/tooltip/form-field-tooltip.js"></script>
<form {{ attributes }} {{ :if style }}style="{{ style }}"{{ :ifend }}>
  {{ :if toplink }}<a name="fbtop">&nbsp;</a>{{ :ifend }}
  {{ :loop hidden }}{{ field }}{{ :loopend }}
  {{ contents }}
  {{ :if toplink }}<div id="fbtoplink"><a href="#fbtop">{{ toplink }}</a></div>{{ :ifend }}
</form>
<script type="text/javascript">
  var tooltipObj = new DHTMLgoodies_formTooltip();
  tooltipObj.setTooltipPosition('right');
  tooltipObj.setImagePath('{{ WBLIB_BASE_URL }}/wblib/css/default/tooltip/images/');
  tooltipObj.initFormFieldTooltip();
</script>
<!-- /form.tpl -->