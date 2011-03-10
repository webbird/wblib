    <select {{ attributes }}{{ :if tooltip }} tooltipText="{{ tooltip }}"{{ :ifend }}>
    {{ :if options }}
    {{ :loop options }}
      <option value="{{ key }}" {{ selected }}>{{ value }}</option>
    {{ :loopend }}
    {{ :else }}{{ :comment The content can be given pre-rendered, using wbListBuilder, for example }}
    {{ content }}
    {{ :ifend }}
    </select>