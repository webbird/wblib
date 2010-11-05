<!-- wblib/wbFormBuilder/form.tpl -->
  {{ :include formbuilder.js }}
<form {{ attributes }} {{ :if style }}style="{{ style }}"{{ :ifend }}>
  {{ :if toplink }}<a name="fbtop">&nbsp;</a>{{ :ifend }}
  {{ :if info }}<div class="{{ fb_icon_class}} {{ fb_info_class }}">{{ info }}</div>{{ :ifend }}
  {{ :loop hidden }}{{ field }}{{ :loopend }}
  {{ contents }}
  {{ :if toplink }}<div id="fbtoplink"><a href="#fbtop">{{ toplink }}</a></div>{{ :ifend }}
</form><!-- /form.tpl -->