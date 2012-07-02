<!-- wblib/wbFormBuilder/form.tpl -->

{{ :include form_js.tpl }}

<form {{ attributes }} {{ :if style }}style="{{ style }}"{{ :ifend }}>
  <input type="hidden" name="fbformkey" value="{{ token }}" />
  {{ :if toplink }}<a name="fbtop">&nbsp;</a>{{ :ifend }}
  {{ :loop hidden }}{{ field }}{{ :loopend }}
  {{ contents }}
  {{ :if toplink }}<div id="fbtoplink"><a href="#fbtop">{{ toplink }}</a></div>{{ :ifend }}
</form>

<!-- /form.tpl -->