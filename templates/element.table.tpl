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
        {{ content }}
      </td>
    </tr><!-- /element.table.tpl -->