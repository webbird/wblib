<!-- wblib/wbFormBuilder/form_js.tpl -->
<script type="text/javascript">

    // load LABjs
    if ( typeof $LAB == "undefined" || typeof $LAB == "undefined" ) {
        var fileref = document.createElement("script");
        fileref.setAttribute( "type", "text/javascript" );
        fileref.setAttribute( "src", "{{ WBLIB_BASE_URL }}/wblib/js/LABjs/LAB-debug.min.js" );
        if (typeof fileref != "undefined" ) { document.getElementsByTagName("head")[0].appendChild(fileref); }
    }

    // avoid to override an already loaded UI theme
    function checkUITheme() {
    var rules = document.styleSheets[0].rules || document.styleSheets[0].cssRules;
    var found = false;
    for (var i in rules) {
        if (typeof rules[i]['selectorText'] != 'undefined' && rules[i]['selectorText'].indexOf("ui-widget") >= 0) {
            found = true;
        }
    }
    if ( ! found ) {
        // add CSS
        var fileref=document.createElement("link");
        fileref.setAttribute("rel", "stylesheet");
        fileref.setAttribute("type", "text/css");
        fileref.setAttribute("media", "screen");
        fileref.setAttribute("href", "{{ WBLIB_BASE_URL }}/wblib/css/jquery_ui/theme.css");
        if (typeof fileref!="undefined") {
            document.getElementsByTagName("head")[0].appendChild(fileref);
        }
    }
    }   // end function checkUITheme() {

    // tabbed interface
    function createTabs() {
        var tabs = '';
        var num  = 0;
        $("fieldset.fbouter").find("fieldset").each(
            function() {
                var id = jQuery(this).attr('id');
                tabs = tabs + '<li><a href="#' + id + '">' + jQuery(this).find("legend").text().trim() + '</a></li>';
                num  = num  + 1;
                jQuery(this).find("legend").hide();
            }
        );
        if ( num > 1 ) {
            checkUITheme();  // make sure we have an UI theme
            $("fieldset.fbouter").prepend('<ul>'+tabs+'</ul>');
            $("fieldset.fbouter").tabs();
        }
    }   // end function createTabs()

{{ :if load_ui_theme }}
    checkUITheme();
{{ :ifend }}
    
    // Continually polls to see if LAB is loaded.
    function wblib_LABjs_Ready(time_elapsed) {
        if ( typeof $LAB == "undefined" || typeof $LAB == "undefined" ) {
            if (time_elapsed <= 5000) {
                setTimeout("wblib_LABjs_Ready(" + (time_elapsed + 200) + ")", 200);
            } else {
                alert("Timed out while loading LABjs.")
            }
        }
        else {
            $LAB.setGlobalDefaults({Debug:true})
                .wait( function() {
                    if (typeof window.jQuery === "undefined")
                        // load jQuery first, then dependencies
                        $LAB.script("{{ WBLIB_BASE_URL }}/wblib/js/jQuery/jquery-core.min.js")
                            .wait(wblib_loadDependencies);
                    else
                        // jQuery already there, proceed to loading dependencies.
                        wblib_loadDependencies();
                    })
            ;
        }
    }   // function tryReady()
    wblib_LABjs_Ready(0);

    function wblib_loadDependencies() {
        $LAB.script('{{ WBLIB_BASE_URL }}/wblib/js/tooltip/rounded-corners.js').wait()
            .script('{{ WBLIB_BASE_URL }}/wblib/js/tooltip/form-field-tooltip.js').wait(
                function() {
                    var tooltipObj = new DHTMLgoodies_formTooltip();
                    tooltipObj.setTooltipPosition('right');
                    tooltipObj.setImagePath('{{ WBLIB_BASE_URL }}/wblib/js/tooltip/images/');
                    tooltipObj.setCloseMessage('{{ :lang Close }}');
                    tooltipObj.setDisableTooltipMessage("{{ :lang Don't show this message again }}");
                    tooltipObj.initFormFieldTooltip();
                }
            )
            .script('{{ WBLIB_BASE_URL }}/wblib/js/jquery.jqEasyCharCounter.js').wait()
            .script('{{ WBLIB_BASE_URL }}/wblib/js/jquery.passwordstrength.js').wait()
{{ :if use_filetype_check }}
            .script('{{ WBLIB_BASE_URL }}/wblib/js/filetypes.js').wait(){{ :ifend }}
{{ :if use_calendar }}
            .script('{{ WBLIB_BASE_URL }}/wblib/js/jQuery/jquery-ui.min.js').wait(
                function() {
                    var calendar_image = '{{ WBLIB_BASE_URL }}/wblib/wbFormBuilder/templates/calendar.gif';
                }
            )
            .script('{{ WBLIB_BASE_URL }}/wblib/js/jquery.datepicker.js').wait()
{{ :ifend }}
{{ :if form2tab }}
            .script('{{ WBLIB_BASE_URL }}/wblib/js/jQuery/jquery-ui.min.js').wait(
                function() { createTabs(); }
            )
{{ :ifend }}
{{ :if use_editor }}
            .script('{{ WBLIB_BASE_URL }}/wblib/js/cleditor/jquery.cleditor.min.js').wait(
                function() {
                    var fileref=document.createElement("link");
                    fileref.setAttribute("rel", "stylesheet");
                    fileref.setAttribute("type", "text/css");
                    fileref.setAttribute("media", "screen");
                    fileref.setAttribute("href", "{{ WBLIB_BASE_URL }}/wblib/js/cleditor/jquery.cleditor.css");
                    if (typeof fileref!="undefined") {
                        document.getElementsByTagName("head")[0].appendChild(fileref);
                    }
                }
            )
            .script('{{ WBLIB_BASE_URL }}/wblib/js/cleditor/jquery.cleditor.xhtml.min.js').wait(
                function() {
                    $.cleditor.defaultOptions.width = 486;
                    $.cleditor.defaultOptions.height = 300;
                }
            )
{{ :ifend }}
{{ :if use_editor_html5 }}
            .script('{{ WBLIB_BASE_URL }}/wblib/js/jQueryTE/jquery-te-1.4.0.min.js').wait(
                function() {
                    var fileref=document.createElement("link");
                    fileref.setAttribute("rel", "stylesheet");
                    fileref.setAttribute("type", "text/css");
                    fileref.setAttribute("media", "screen");
                    fileref.setAttribute("href", "{{ WBLIB_BASE_URL }}/wblib/js/jQueryTE/jquery-te-1.4.0.css");
                    if (typeof fileref!="undefined") {
                        document.getElementsByTagName("head")[0].appendChild(fileref);
                    }
                }
            )
{{ :ifend }}
            .wait(
                function() {
                    jQuery(document).ready(function($) {
                        $('div.passwordStrengthDiv').each(
                            function() {
                                var input_id = $(this).attr('id').replace('passwordStrengthDiv_','');
                                $('input#'+input_id).passwordStrength({targetDiv:'#passwordStrengthDiv_'+input_id});
                            }
                        );
                        {{ js }}
                    });
                }
            )
        ;
    }

</script>
<!-- END wblib/wbFormBuilder/form_js.tpl -->