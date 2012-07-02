    <input{{ :if attributes }} {{ attributes }}{{ :ifend }}{{ :if tooltip }} tooltipText="{{ tooltip }}"{{ :ifend }} />
    {{ :if pwstrength }}
    <div id="passwordStrengthDiv_{{ name }}" class="passwordStrengthDiv is0" style="margin-left:50px;" title="{{ :lang Password strength }}">
	  <span style="margin:0;padding:0;height:7px;line-height:7px;font-size:9px;margin-left:-50px;" class="poor">&nbsp;</span>
	</div>
    {{ :ifend }}
    