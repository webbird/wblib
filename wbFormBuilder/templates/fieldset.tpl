  <fieldset class="fbouter">
    {{ :if errors }}<div class="{{ fb_icon_class}} {{ fb_error_class }}">{{ errors }}</div>{{ :ifend }}
    {{ :if info }}<div class="fbicon{{ :if fb_info_class }} {{ fb_info_class }}"{{ :ifend }}>{{ info }}</div>{{ :ifend }}
    {{ :if req_info }}<div class="fbicon{{ :if fb_req_class }} {{ fb_req_class }}{{ :ifend }}">{{ req_info }}</div>{{ :ifend }}
    {{ form }}
    <br style="clear: left;" />
    <div class="fbsubmit{{ :if fb_buttonpane_class }} {{ fb_buttonpane_class }}{{ :ifend }}">
      {{ :loop buttons }}{{ :if field }}{{ field }} {{ :ifend }}{{ :loopend }}
    </div>
  </fieldset>
