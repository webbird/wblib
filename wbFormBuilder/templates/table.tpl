    <table class="{{ fb_table_class }}">
    {{ :if header }}
    <tr>
      <th colspan="3"{{ :if fb_header_class }} class="{{ fb_header_class }}"{{ :ifend }}>
        {{ header }}
      </th>
    </tr>{{ :ifend }}
    {{ :if req_info }}
    <tr>
      <td colspan="3"{{ :if fb_req_class }} class="{{ fb_req_class }}"{{ :ifend }}>
        {{ req_info }}
      </td>
    </tr>{{ :ifend }}
    {{ :if info }}
    <tr>
      <td colspan="3"{{ :if fb_info_class }} class="{{ fb_info_class }}"{{ :ifend }}>
        {{ info }}
      </td>
    </tr>{{ :ifend }}
{{ :loop elements }}
    {{ :if error }}
    <tr>
      <td colspan="3"{{ :if fb_error_class }} class="{{ fb_error_class }}"{{ :ifend }}>
        {{ error }}
      </td>
    </tr>{{ :ifend }}
<!-- BEGIN template comment -->
    {{ :if header }}
    <tr>
      <th colspan="3"{{ :if fb_header_class }} class="{{ fb_header_class }}"{{ :ifend }}>
        {{ header }}
      </th>
    </tr>
    {{ :else }}
<!-- END template comment -->
    <tr>
      <td{{ :if fb_left_class }} class="{{ fb_left_class }}"{{ :ifend }}>
        {{ label }}:
        {{ :if info }}<br /><span class="fbsmall">{{ info }}</span>{{ :ifend }}
      </td>
      <td{{ :if fb_req_class }} class="{{ fb_req_class }}"{{ :ifend }}>
        {{ :if req }}{{ req }}{{ :ifend }}
      </td>
      <td{{ :if fb_right_class }} class="{{ fb_right_class }}"{{ :ifend }}>
        {{ field }}
      </td>
    </tr>
<!-- BEGIN template comment -->
    {{ :ifend }}
<!-- END template comment -->
{{ :loopend }}
    <tr>
      <td colspan="3"{{ :if fb_buttonpane_class }} class="{{ fb_buttonpane_class }}"{{ :ifend }}>
      {{ :loop buttons }}{{ :if field }}{{ field }}{{ :ifend }}{{ :loopend }}
      </td>
    </tr>
    </table>