/* Powered by mootools core builder (http://mootools.net) */
var WpScripts = {

	initialize: function(){
		this.inputs = jQuery('#wp_scripts_option label.check input');
		this.inputs.change(function(){
			var input = jQuery(this);
			if ( input.attr('checked') )
				return WpScripts.check(input);
			else
				return WpScripts.uncheck(input);
		});
	},
	
	debug: function(txt) {
		jQuery('#debug').append(' ' + txt);
	},

	uncheckByName: function(input){
		input.parents('tr:first').find('input[type=radio]').not(input).each(function(){
			WpScripts.uncheck(jQuery(this), true);
		});
	},

	uncheck: function(input, force){
		var deps = input.attr('deps');
		if ( input.attr('checked') )
			input.attr('checked', false);

		if ( input.parent(0).hasClass('tmpchecked') )
			input.parent(0).removeClass('tmpchecked');
//		if (deps){
			WpScripts.uncheckDepending(input.attr('id'));
//		}
	},

	check: function(input, force){
		var deps = input.attr('deps');
		if ( !input.attr('checked') )
			input.attr('checked', true);
		if ( !input.parent(0).hasClass('tmpchecked') && !input.parent(0).hasClass('checked') )
			input.parent(0).addClass('tmpchecked');
		if (input.attr('type') == 'radio'){
			WpScripts.uncheckByName(input);
		}
		if (deps){
			WpScripts.checkDependants(deps.split(','));
		}
	},

	checkDependants: function(deps){
		jQuery(deps).each(function(i, dep){
			var input = jQuery('#' + dep);
			if (input && !input.attr('checked')) WpScripts.check(input,true);
		});
	},

	uncheckDepending: function(component){
		WpScripts.inputs.filter(':checked').each(function(){
			var input = jQuery(this);
			var deps = input.attr('deps');
			if (!deps)
				return;
			if ( jQuery.inArray(component, deps.split(',')) > -1 )
				WpScripts.uncheck(input,true);
		});
	}

};

jQuery(document).ready(function(){
	WpScripts.initialize();
});