<!-- wblib/wbFormBuilder/form.tpl -->
  {{ :if errors }}<div class="{{ fb_error_class }}">{{ errors }}</div>{{ :ifend }}
  {{ :if info }}<div class="{{ fb_info_class }}">{{ info }}</div>{{ :ifend }}
<form {{ attributes }} {{ :if style }}style="{{ style }}"{{ :ifend }}>
  {{ hidden }}
  <a name="fbtop">&nbsp;</a>
  {{ contents }}
  {{ :if toplink }}<div id="fbtoplink"><a href="#fbtop">{{ toplink }}</a></div>{{ :ifend }}
</form><!-- /form.tpl -->