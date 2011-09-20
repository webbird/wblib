    {{ :loop options }}
      <input {{ attributes }} {{ checked }}{{ :if tooltip }} tooltipText="{{ tooltip }}"{{ :ifend }} /> {{ text }}{{ :if break }}<br />{{ :ifend }}
    {{ :loopend }}
