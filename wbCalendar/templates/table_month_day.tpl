  <span class="wbcal-daynumber{{ :if spanclass }} {{ spanclass }}{{ :ifend }}">
      {{ :if hovertext }}<a class="cltitle" title="|{{ hovertext }}">{{ :ifend }}{{ day }}{{ :if hovertext }}</a>{{ :ifend }}
      </a>
  </span><br />
  {{ :loop rows }}
  <span class="wbcal-row">
    {{ :loop events }}
      {{ :if text }}<span class="wbcal-event-text">{{ text }}</span>{{ :else }}<br />{{ :ifend }}
    {{ :loopend }}
  </span><br />
  {{ :loopend }}