$.fn.hasAttr = function(name) {
   return this.attr(name) !== undefined;
};
    if ( ! jQuery('#{{ element }}').parent().hasClass('jqte_hiddenField') )
    {
        jQuery("#{{ element }}").jqte();
        var origval = '';
        // easyCharCounter does not work with jqte, as it is not used on a textarea
        $("#{{ element }}").parent().parent().after('<div id="{{ element }}_wordcount">');
        var maxlength = '{{ maxlength }}';
        if ( jQuery.isNumeric(maxlength) ) {
            var value     = $("#{{ element }}").val().length;
            var counter   = $('body').find('#{{ element }}_wordcount');
            counter.html('Characters: ' + (maxlength - value) + '/' + maxlength);

            $('.jqte_editor').each(function() {
                var $this = $(this);
                var counter   = $('body').find('#{{ element }}_wordcount');
                var maxlength = '{{ maxlength }}';
                if ( jQuery.isNumeric(maxlength) ) {
                    $this.bind('input keyup keydown', function(e) {
                        var value = $this.html().length;
                        if(value <= maxlength) {
                            counter.html('{{ characters }}: ' + (maxlength - value) + '/' + maxlength);
                            if(counter.hasClass('fberror')) {
                                counter.removeClass('fberror');
                            }
                            if ( jQuery('#save_regform').hasAttr('disabled') ) {
                                jQuery('#save_regform').removeAttr("disabled");
                                jQuery('#save_regform').val(origval);
                            }
                        } else {
                            counter.html('Only [' + maxlength + '] characters allowed!');
                            counter.addClass('fberror');
                            if ( ! jQuery('#save_regform').hasAttr('disabled') ) {
                                jQuery('#save_regform').prop("disabled", "disabled");
                                origval = jQuery('#save_regform').val();
                                jQuery('#save_regform').val('Unable to save!');
                            }
                        }
                    });
                }
            });
        }
    }



