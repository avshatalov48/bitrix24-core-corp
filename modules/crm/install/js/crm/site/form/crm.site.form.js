(function () {
	if (window.b24form && window.b24form.util)
	{
		return;
	}

	if (!window.b24form)
	{
		window.b24form = {};
	}

	/** @requires module:webpacker */
	/** @var Object webPacker */
	/** @var {Object} module Current module.*/
	window.b24form.util = webPacker;
	window.b24form.common = {
		properties: module.properties || {},
		languages: module.languages || {},
		language: module.language || 'en',
		messages: module.messages || {},
	};

	function loadApp()
	{
		var min = b24form.common.properties.isResourcesMinified ? '.min' : '';
		var time = Date.now() / (3600 * 24 * 1000) | 0;

		var link = document.createElement('link');
		link.type = 'text/css';
		link.rel = 'stylesheet';
		link.href = b24form.util.getAddress() + '/bitrix/js/crm/site/form/dist/app.bundle' + min + '.css?' + time;
		b24form.util.resource.appendToHead(link);

		var script = document.createElement('script');
		script.type = 'text/javascript';
		if (b24form.Loader)
		{
			script.onload = b24form.Loader.loadForms.bind(b24form.Loader);
		}
		script.src = b24form.util.getAddress() + '/bitrix/js/crm/site/form/dist/app.bundle' + min + '.js?' + time;
		b24form.util.resource.appendToHead(script);
	}

	loadApp();
})();