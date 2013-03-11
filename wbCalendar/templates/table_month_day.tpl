    <span class="wbcal_daynumber{{ :if spanclass }} {{ spanclass }}{{ :ifend }}">
        {{ :if hovertext }}<a class="ctip" title="" rel="#d{{ day }}">{{ :ifend }}
	    {{ day }}
	    {{ :if hovertext }}</a>
		<span id="d{{ day }}" style="display:none">
		    {{ hovertext }}
		</span>
	  {{ :ifend }}
    </span><br />
{{ :loop rows }}
  		<span class="wbcal_row">
	{{ :loop events }}
      		{{ :if text }}<span class="wbcal_event_text">{{ text }}</span>{{ :else }}<br />{{ :ifend }}
	{{ :loopend }}
    	</span><br />
{{ :loopend }}
