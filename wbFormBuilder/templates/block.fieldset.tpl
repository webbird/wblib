    <fieldset class="{{ fieldset_class }}">
      {{ :if header }}{{ header }}{{ :ifend }}
      <ol>
{{ :loop elements }}{{ :if ! header }}
        <li>
          {{ :if label }}{{ label }}{{ :ifend }}
          <span{{ :if req }} class="fbrequired"{{ :ifend }}>&nbsp;</span>
          {{ field }}
          {{ :if error }}<span class="fbspan fberror">{{ error }}</span>{{ :ifend }}
          {{ :if info }}<span class="fbspan fbinfo" id="{{ name }}_info">{{ info }}</span>{{ :ifend }}
        </li>
{{ :ifend }}{{ :loopend }}
      </ol>
    </fieldset>