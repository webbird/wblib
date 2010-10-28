  <fieldset>
    {{ :if header }}<legend{{ :if fb_header_class }} class="{{ fb_header_class }}"{{ :ifend }}>{{ header }}</legend>{{ :ifend }}
    {{ :if req_info }}<div class="fbicon{{ :if fb_req_class }} {{ fb_req_class }}{{ :ifend }}">{{ req_info }}</div>{{ :ifend }}
    {{ :if info }}<div{{ :if fb_info_class }} class="{{ fb_info_class }}"{{ :ifend }}>{{ info }}</div>{{ :ifend }}
    <ol>
    {{ :loop elements }}
      <li>{{ label }}<span{{ :if req }} class="fbrequired"{{ :ifend }}>&nbsp;</span>{{ field }}
          {{ :if error }}<span class="{{ fb_error_class }}">{{ error }}</span>{{ :ifend }}
      </li>
    {{ :loopend }}
    </ol>
    <br clear="both" />
    <div class="fbsubmit{{ :if fb_buttonpane_class }} {{ fb_buttonpane_class }}{{ :ifend }}">
      {{ :loop buttons }}{{ :if field }}{{ field }}{{ :ifend }}{{ :loopend }}
    </div>
  </fieldset>
