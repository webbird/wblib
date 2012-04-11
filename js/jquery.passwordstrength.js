/*
    http://benjaminsterling.com/password-strength-indicator-and-generator/
    
    Changed by B. Martinovic for use with wbFormBuilder
    
*/

(function($) {
	$.fn.extend({
		passwordStrength: function( options ) {
			return this.each( function() {
				var that = this;that.opts = {};
				that.opts = $.extend({
                    classes : Array('is100','is90','is80','is70','is60','is50','is40','is30','is20','is10'),
					infotext: Array('poor' ,'poor','weak','weak','ok'  ,'ok'  ,'ok'  ,'good','good','strong'),
					targetDiv : '#passwordStrengthDiv',
					cache : {}
                }, options);

				that.div = jQuery(that.opts.targetDiv);
				that.defaultClass = that.div.attr('class');

				that.percents = (that.opts.classes.length) ? 100 / that.opts.classes.length : 100;

				 v = jQuery(this).keyup(function(){
					if( typeof el == "undefined" )
						this.el = jQuery(this);
					var s = getPasswordStrength (this.value);
					var p = this.percents;
					var t = Math.floor( s / p );

					if( 100 <= s )
						t = this.opts.classes.length - 1;

					this.div
						.removeAttr('class')
						.addClass( this.defaultClass )
						.addClass( this.opts.classes[ t ] );
					this.div.find('span')
						.removeAttr('class')
						.addClass(this.opts.infotext[ t ])
						.text(this.opts.infotext[ t ]);

				})
		/*
				// removed for use with wbFormBuilder
				.after('<a href="#">Generate Password</a>')
				.next()
				.click(function(){
					jQuery(this).prev().val( randomPassword() ).trigger('keyup');
					return false;
				})
		*/
			    ;
			});   // return this.each()

			function getPasswordStrength(H){
				var D=(H.length);
				if(D>5){
					D=5
				}
				var F=H.replace(/[0-9]/g,"");
				var G=(H.length-F.length);
				if(G>3){G=3}
				var A=H.replace(/\W/g,"");
				var C=(H.length-A.length);
				if(C>3){C=3}
				var B=H.replace(/[A-Z]/g,"");
				var I=(H.length-B.length);
				if(I>3){I=3}
				var E=((D*10)-20)+(G*10)+(C*15)+(I*10);
				if(E<0){E=0}
				if(E>100){E=100}
				return E
			}   // function getPasswordStrength(H)

			function randomPassword() {
				var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$_+";
				var size = 10;
				var i = 1;
				var ret = ""
				while ( i <= size ) {
					$max = chars.length-1;
					$num = Math.floor(Math.random()*$max);
					$temp = chars.substr($num, 1);
					ret += $temp;
					i++;
				}
				return ret;
			}   // function randomPassword()

		}


	});
})(jQuery);