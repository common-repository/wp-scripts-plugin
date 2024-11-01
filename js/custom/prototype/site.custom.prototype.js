// site.custom example
/* 
For Prototype 
*/

Event.observe(window, 'load', function(){
	if (typeof Spoiler != 'object')
		return;
	$$('#sidebar .spl-box').each(function(el) {
		el = $(el);
		var tgl = $(el.getElementsByTagName('h3')[0]);
		var body = $(document.getElementsByClassName('spl-body', el)[0]);
		if(tgl && body) {
			tgl.onclick = function(){
				new Spoiler.Collapse(body, 'simple', tgl, {duration: 0.4});
				return false;
			}
			Element.addClassName(tgl, 'effcollapse');
			if(Element.hasClassName(el, 'hide_first')) {
				new Spoiler.Collapse(body, 'simple', tgl, {duration: 0.6});
			}
		}
	});	
});