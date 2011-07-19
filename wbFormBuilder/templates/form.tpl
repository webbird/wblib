<!-- wblib/wbFormBuilder/form.tpl -->
<script type="text/javascript" src="{{ WBLIB_BASE_URL }}/wblib/js/tooltip/rounded-corners.js"></script>
<script type="text/javascript" src="{{ WBLIB_BASE_URL }}/wblib/js/tooltip/form-field-tooltip.js"></script>
<script type="text/javascript" src="{{ WBLIB_BASE_URL }}/wblib/js/jquery.jqEasyCharCounter.js"></script>
<script type="text/javascript" src="{{ WBLIB_BASE_URL }}/wblib/js/cleditor/jquery.cleditor.min.js"></script>
<script type="text/javascript">
  $.cleditor.defaultOptions.width = 486;
  $.cleditor.defaultOptions.height = 300;
</script>
<form {{ attributes }} {{ :if style }}style="{{ style }}"{{ :ifend }}>
  <input type="hidden" name="fbformkey" value="{{ token }}" />
  {{ :if toplink }}<a name="fbtop">&nbsp;</a>{{ :ifend }}
  {{ :loop hidden }}{{ field }}{{ :loopend }}
  {{ contents }}
  {{ :if toplink }}<div id="fbtoplink"><a href="#fbtop">{{ toplink }}</a></div>{{ :ifend }}
</form>

<script type="text/javascript">
  if ( typeof jQuery != 'undefined' ) {
    jQuery(document).ready(function($) {
      if ( typeof DHTMLgoodies_formTooltip != "undefined" ) {
        var tooltipObj = new DHTMLgoodies_formTooltip();
        tooltipObj.setTooltipPosition('right');
        tooltipObj.setImagePath('{{ WBLIB_BASE_URL }}/wblib/js/tooltip/images/');
        tooltipObj.setCloseMessage('{{ :lang Close }}');
        tooltipObj.setDisableTooltipMessage("{{ :lang Don't show this message again }}");
        tooltipObj.initFormFieldTooltip();
      }
      {{ js }}
    });
  }
</script>
<!-- /form.tpl -->