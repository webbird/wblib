    <fieldset id="block-{{ block_number }}" class="{{ fieldset_class }}">
      {{ :if header }}{{ header }}{{ :ifend }}
      <ol>
{{ :loop elements }}{{ :if ! header }}
        <li class="fbtype{{ type }}">
          {{ :if label }}{{ label }}{{ :ifend }}
          <span class="fbspan20{{ :if req }} fbrequired{{ :ifend }}"><span style="color:red;font-weight:bold;font-size:1.3em">{{ req }}</span></span>
          {{ field }}
          {{ :if error }}<span class="fbspan fberror" style="float: right;">{{ error }}</span>{{ :ifend }}
          {{ :if info }}<span class="fbspan fbinfo" id="{{ name }}_info">{{ info }}</span>{{ :ifend }}
        </li>
{{ :ifend }}{{ :loopend }}
      </ol>
    </fieldset>