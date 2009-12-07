<form {{ formattribs }}>
  <a name="fbtop"></a>
  {{ hidden }}
  <table {{ tableattrs }}>
    {{ :if header }}
    <thead>
      <tr>
        <th colspan="3" class="{{ fb_header_class }}">
          {{ header }}
        </th>
      </tr>
    </thead>
    {{ :ifend }}
    {{ :if info }}
    <tr>
      <td colspan="3">
        <div class="{{ fb_info_class }} {{ infoclass }}">
        {{ info }}
        </div>
      </td>
    </tr>{{ :ifend }}
    <tr><td colspan="3">{{ required_info }}</td></tr>
    {{ content }}
    <tr>
      <td colspan="3" class="{{ fb_button_class }}">
        <a name="formbuttons">{{ buttons }}</a>
      </td>
    </tr>
  </table>
  {{ :if toplink }}<div id="fbtoplink"><a href="#fbtop">{{ toplink }}</a></div>{{ :ifend }}
</form><!-- /form.table.tpl -->