{{ :comment table based week sheet }}
    <tr>
{{ :if labels }}
	    <td class="wbcal_label">
	      {{ :loop labels }}<br /><span class="wbcal_label">{{ label }}</span>{{ :loopend }}
	    </td>
{{ :ifend }}
{{ :loop days }}
	    <td class="wbcal_day{{ :if tdclass }} {{ tdclass }}{{ :ifend }}">
	      <span class="{{ dayclass }}">{{ day }}</span>
	    </td>
{{ :loopend }}
    	<td class="wbcal_week">{{ weeknumber }}</td>
  	</tr>