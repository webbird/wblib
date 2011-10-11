    <input{{ :if attributes }} {{ attributes }}{{ :ifend }}{{ :if tooltip }} tooltipText="{{ tooltip }}"{{ :ifend }} />
    {{ :if pwstrength }}
    <div id="passwordStrengthDiv_{{ name }}" class="is0" title="{{ :lang Password strength }}">
	  <span style="margin:0;padding:0;height:7px;line-height:7px;font-size:9px;" class="poor">&nbsp;</span>
	</div>
    <script type="text/javascript">
      jQuery(document).ready(function($){
		$('input#{{ name }}').passwordStrength({targetDiv:'#passwordStrengthDiv_{{ name }}'});
	  });
    </script>
    {{ :ifend }}
    