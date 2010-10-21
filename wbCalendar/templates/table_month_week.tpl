  <tr>
    {{ :if labels }}
    <td class="wbcal-label">
      {{ :loop labels }}<br /><span class="wbcal-label">{{ label }}</span>{{ :loopend }}
    </td>
    {{ :ifend }}
    {{ :loop days }}
    <td class="wbcal-day{{ :if tdclass }} {{ tdclass }}{{ :ifend }}">
      <span class="{{ dayclass }}">{{ day }}</span>{{ :if events }}<br />{{ :ifend }}
      {{ :loop events }}{{ event }}{{ :loopend }}
    </td>
    {{ :loopend }}
    <td>{{ weeknumber }}</td>
  </tr>