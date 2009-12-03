  <form {{ formattribs }}>
    {{ hidden }}
    {{ :if info }}<div class="{{ fb_info_class }} {{ infoclass }}">
      {{ info }}
    </div>{{ :ifend }}
    <h1>{{ header }}</h1>
    <fieldset class="{{ fb_fieldset_class }}">
      {{ content }}
      <div class="{{ fb_button_class }}">
        {{ buttons }}
      </div>
      <br clear="both" />
    </fieldset>
  </form>