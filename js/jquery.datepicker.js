var startdateid  = 'startdate';
var enddateid    = 'enddate';
var durationdays = 0;
var dplang       = 'en';
var time_elapsed = 0;

function setStartID( field ) {
  startdateid = field;
}
function setEndID( field ) {
  enddateid = field;
}
function setDuration( days ) {
  durationdays = days;
}
function setLang( lang ) {
  dplang = lang;
}


if ( typeof jQuery !== undefined ) {

  jQuery(document).ready(function($){

	// make sure datepicker is loaded before using it
    function datepickerReady() {
      if ( typeof($.datepicker) == 'undefined' ) {
        if ( time_elapsed <= 5000 ) {
		  time_elapsed = time_elapsed + 200;
          window.setTimeout( datepickerReady, 200 );
        }
        else {
          alert( "Timed out while loading datepicker." );
        }
      }
      else {
          $.datepicker.setDefaults( $.datepicker.regional[ dplang ] );
          // don't allow manual input
          $('.datepicker').keypress(function(e){e.preventDefault();});
          $('.datepicker').datepicker({
            showOn: "both", // show calendar popup on click into edit field and on button
		    buttonImageOnly: true, // no text on button
		    showButtonPanel: true, // button panel in popup
		    changeMonth: true, // month dropdown in popup
		    changeYear: true, // year dropdown in popup
		    beforeShow: checkDates // callback function
          });
      }
    }

    datepickerReady(0);

    // this sets the min date of the "end date" to the value of "start date"
    function checkDates( input, instance ) {
	  if ( parseInt(durationdays) > 0 ) {
        if ( input.id == enddateid ) {
          var minDate = new Date( jQuery('#'+startdateid).datepicker("getDate") );
          minDate.setDate( minDate.getDate() + parseInt( durationdays ) );
          return {
            minDate: jQuery('#'+startdateid).datepicker("getDate"),
            maxDate: minDate
          };
        } else if ( input.id == startdateid ) {
          return {
            maxDate: jQuery('#'+enddateid).datepicker("getDate")
          };
        }
	  }
    }

  });
}