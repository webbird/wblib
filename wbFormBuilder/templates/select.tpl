    <select {{ attributes }}>
    {{ :if options }}
    {{ :loop options }}
      <option {{ attributes}}>{{ value }}</option>
    {{ :loopend }}
    {{ :else }}
    {{ value }}
    {{ :ifend }}
    </select>