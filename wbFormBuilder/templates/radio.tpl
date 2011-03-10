    {{ :loop options }}
      <input {{ attributes }} {{ checked }}{{ :if tooltip }} tooltipText="{{ tooltip }}"{{ :ifend }} /> {{ text }}
    {{ :loopend }}
