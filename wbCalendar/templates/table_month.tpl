{{ :include header.tpl }}
{{ :comment table based month sheet; weeks are added at markup "sheet" }}
<table class="wbcal_table wbcal_month">
  	<tr>
		<th class="wbcal_monthname" colspan="{{ cols }}">{{ month }}</th>
	</tr>
    <tr>
{{ :if labels }}
		<td class="wbcal_dayname wbcal_label">{{ :lang Label }}</td>
{{ :ifend }}
{{ :loop daynames }}
    	<th class="wbcal_dayname">{{ name }}</th>
{{ :loopend }}
    	<th class="wbcal_dayname wbcal_weeknumber">{{ :lang Week }}</th>
  </tr>
  {{ sheet }}
</table><br />

{{ :include legend.tpl }}