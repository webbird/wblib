    <table class="{{ table_class }}">
    {{ :if header }}
    <tr>
      <th colspan="3"{{ :if header_class }} class="{{ header_class }}"{{ :ifend }}>
        {{ header }}
      </th>
    </tr>{{ :ifend }}
    {{ :if req_info }}
    <tr>
      <td colspan="3"{{ :if req_class }} class="{{ req_class }}"{{ :ifend }}>
        {{ req_info }}
      </td>
    </tr>{{ :ifend }}
    {{ :if info }}
    <tr>
      <td colspan="3"{{ :if info_class }} class="{{ info_class }}"{{ :ifend }}>
        {{ info }}
      </td>
    </tr>{{ :ifend }}
    {{ :if errors }}
    <tr>
      <td colspan="3" class="{{ icon_class}} {{ error_class }}">
        {{ errors }}
      </td>
    </tr>{{ :ifend }}

{{ :loop elements }}
    {{ :if error }}
    <tr>
      <td colspan="3"{{ :if error_class }} class="{{ error_class }}"{{ :ifend }}>
        {{ error }}
      </td>
    </tr>{{ :ifend }}

    {{ :if header }}
        {{ header }}
    {{ :else }}
    <tr>
      <td{{ :if left_class }} class="{{ left_class }}"{{ :ifend }}>
        {{ label }}:
        {{ :if info }}<br /><span class="fbsmall">{{ info }}</span>{{ :ifend }}
      </td>
      <td{{ :if req_class }} class="{{ req_class }}"{{ :ifend }}>
        {{ :if req }}{{ req }}{{ :ifend }}
      </td>
      <td{{ :if right_class }} class="{{ right_class }}"{{ :ifend }}>
        {{ field }}
      </td>
    </tr>

    {{ :ifend }}

{{ :loopend }}
    <tr>
      <td colspan="3"{{ :if buttonpane_class }} class="{{ buttonpane_class }}"{{ :ifend }}>
      {{ :loop buttons }}{{ :if field }}{{ field }}{{ :ifend }}{{ :loopend }}
      </td>
    </tr>
    </table>