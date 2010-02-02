    <table class="{{ fb_table_class }}">
    {{ :if header }}
    <tr>
      <th colspan="3" class="{{ fb_header_class }}">
        {{ header }}
      </th>
    </tr>{{ :ifend }}
    {{ :if req_info }}
    <tr>
      <td colspan="3" class="{{ fb_req_class }}">
        {{ req_info }}
      </td>
    </tr>{{ :ifend }}
    {{ :if info }}
    <tr>
      <td colspan="3" class="{{ fb_info_class }}">
        {{ info }}
      </td>
    </tr>{{ :ifend }}
    {{ :loop elements }}
    {{ :if error }}
    <tr>
      <td colspan="3" class="{{ fb_error_class }}">
        {{ error }}
      </td>
    </tr>{{ :ifend }}
    <tr>
      <td class="{{ fb_left_class }}">
        {{ label }}
      </td>
      <td class="{{ fb_req_class }}">
        {{ req }}
      </td>
      <td class="{{ fb_right_class }}">
        {{ field }}
      </td>
    </tr>
    {{ :loopend }}
    </table>