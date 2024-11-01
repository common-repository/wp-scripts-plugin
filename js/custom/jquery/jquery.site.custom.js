// site.custom example
/* 
For jQuery 
*/

jQuery(document).ready(function() {
	if ( typeof Spoiler != 'object' )
		return;
	jQuery('#sidebar .widget').each(function(i) {
		var el = jQuery(this);
		var body = el.find('.spl-body:first');
		if (!body.length)
			return;//means continue;
		var tgl = el.find('h3:first').click(function(e){
			Spoiler.Collapse(body, 'slide', this, 4);
		}).addClass('effcollapse');
		if(el.hasClass('hide_first')) {
			Spoiler.Collapse(body, 'simple', tgl, 6);
		}
	});

});

if ( typeof humanMsg.displayMsg == 'function' ) {
	function alert( message ){
		/* An alias for showMessage */
		humanMsg.displayMsg( message );
	}
}
