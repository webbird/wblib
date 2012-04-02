<!-- wblib/wbFormBuilder/form.tpl -->
{{ :include form_js.tpl }}
<form {{ attributes }} {{ :if style }}style="{{ style }}"{{ :ifend }}>
  <input type="hidden" name="fbformkey" value="{{ token }}" />
  {{ :if toplink }}<a name="fbtop">&nbsp;</a>{{ :ifend }}
  {{ :loop hidden }}{{ field }}{{ :loopend }}
  {{ contents }}
  {{ :if toplink }}<div id="fbtoplink"><a href="#fbtop">{{ toplink }}</a></div>{{ :ifend }}
</form>

<script type="text/javascript">
    function tryReadyBottom(time_elapsed) {
        // make sure that jQuery is available
        if ( typeof jQuery == 'undefined' ) {
            if (time_elapsed <= 5000) {
                setTimeout("tryReadyBottom(" + (time_elapsed + 200) + ")", 200);
            } else {
                alert("Timed out while loading jQuery.")
            }
        }
        else {
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
    }
    tryReadyBottom(0);
</script>
<!-- /form.tpl -->