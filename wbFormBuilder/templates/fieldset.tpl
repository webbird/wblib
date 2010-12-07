  <fieldset class="fbouter">
    {{ :if header }}<div class="{{ icon_class }}">{{ header }}</div>{{ :ifend }}
    {{ :if errors }}<div class="{{ icon_class }} {{ error_class }}">{{ errors }}</div>{{ :ifend }}
    {{ :if info }}<div class="fbicon{{ :if info_class }} {{ info_class }}"{{ :ifend }}>{{ info }}</div>{{ :ifend }}
    {{ :if req_info }}<div class="fbicon{{ :if req_class }} {{ req_class }}{{ :ifend }}">{{ req_info }}</div>{{ :ifend }}
    {{ form }}
    <br style="clear: left;" />
    {{ :if buttons }}
    <div class="fbsubmit{{ :if buttonpane_class }} {{ buttonpane_class }}{{ :ifend }}">
      {{ :loop buttons }}{{ :if field }}{{ field }} {{ :ifend }}{{ :loopend }}
    </div>
    {{ :ifend }}
  </fieldset>
