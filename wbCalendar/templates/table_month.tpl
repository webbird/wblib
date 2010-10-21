  <table class="wbcal-month">
  <tr><th class="wbcal-monthname" colspan="{{ cols }}">{{ month }}</th></tr>
  <tr>
    {{ :if labels }}<td class="wbcal-dayname wbcal-label">{{ :lang Label }}</td>{{ :ifend }}
    {{ :loop daynames }}
    <th class="wbcal-dayname">{{ name }}</th>
    {{ :loopend }}
    <th class="wbcal-weeknumber">{{ :lang Week }}</th>
  </tr>
  {{sheet}}
  </table><br />