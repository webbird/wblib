	<div class="fbradiogroup fb{{type}}">
    {{ :loop options }}
      <input {{ attributes }} {{ checked }}{{ :if tooltip }} tooltipText="{{ tooltip }}"{{ :ifend }} />
	    <label for="{{ id }}">{{ text }}</label>
		{{ :if break }}<br />{{ :ifend }}
    {{ :loopend }}
    </div>
