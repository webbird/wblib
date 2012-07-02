<!-- wblib/wbFormBuilder/form_js.tpl -->
<script type="text/javascript">

	// load LABjs
	if ( typeof $LAB == "undefined" || typeof $LAB == "undefined" ) {
		var fileref = document.createElement("script");
		fileref.setAttribute( "type", "text/javascript" );
		fileref.setAttribute( "src", "{{ WBLIB_BASE_URL }}/wblib/js/LABjs/LAB-debug.min.js" );
		if (typeof fileref != "undefined" ) { document.getElementsByTagName("head")[0].appendChild(fileref); }
	}

	{{ :if load_ui_theme }}
	// add CSS
	var fileref=document.createElement("link");
    fileref.setAttribute("rel", "stylesheet");
    fileref.setAttribute("type", "text/css");
    fileref.setAttribute("media", "screen");
    fileref.setAttribute("href", "{{ WBLIB_BASE_URL }}/wblib/css/jquery_ui/theme.css");
    if (typeof fileref!="undefined") {
        document.getElementsByTagName("head")[0].appendChild(fileref);
    }
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
			.script('{{ WBLIB_BASE_URL }}/wblib/js/jQuery/jquery-ui.min.js').wait()
			.script('{{ WBLIB_BASE_URL }}/wblib/js/jquery.datepicker.js').wait(
			    function() {
			        var calendar_image = '{{ WBLIB_BASE_URL }}/wblib/wbFormBuilder/templates/calendar.gif';
			    }
			){{ :ifend }}
			{{ :if use_editor }}
			.script('{{ WBLIB_BASE_URL }}/wblib/js/cleditor/jquery.cleditor.js').wait(
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
			){{ :ifend }}
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
