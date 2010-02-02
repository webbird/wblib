  <fieldset>
    {{ :loop elements }}
    {{ label }}{{ req }}
    {{ field }}
    {{ :if error }}<span class="{{ fb_error_class }}">{{ error }}</span>{{ :ifend }}
    <br />
    {{ :loopend }}
  </fieldset>