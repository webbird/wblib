  <span class="wbcal-daynumber">{{ day }}</span><br />
  {{ :loop rows }}
  <span class="wbcal-row">
    {{ :loop events }}
      {{ :if text }}<span class="wbcal-event-text">{{ text }}</span>{{ :else }}<br />{{ :ifend }}
    {{ :loopend }}
  </span><br />
  {{ :loopend }}