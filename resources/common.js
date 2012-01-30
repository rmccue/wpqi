this.advanced_options = function() {
	var div = document.getElementById('advanced-options');
	var toggle = document.getElementById('advanced-options-toggle');

	if ( ! div || ! toggle )
		return;
	if ( div.className && div.className.indexOf('force-show-block') )
		return;
	if ( ! toggle.onchange )
		toggle.onchange = function() { advanced_options(); };
	div.style.display = toggle.checked ? 'block' : 'none';
}

this.common_loader = function() {
	if ( 'function' == typeof(this.advanced_options) )
		this.advanced_options();
}

window.onload = common_loader