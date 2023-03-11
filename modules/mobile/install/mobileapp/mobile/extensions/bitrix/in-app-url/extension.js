/**
 * @module in-app-url
 */
jn.define('in-app-url', (require, exports, module) => {

	const { InAppUrl } = require('in-app-url/in-app-url');

	const inAppUrl = new InAppUrl();

	/**
	 * Register your routes extensions here,
	 * and don't forget to add them into deps.php
	 */
	const routes = [
		'crm/in-app-url/routes',
		'in-app-url/routes',
	];

	routes.map(ext => {

		try
		{
			if (jn.define.moduleMap[ext])
			{
				const initFn = require(ext);
				initFn(inAppUrl);
			}
			else
			{
				console.warn(`in-app-url: routes extension not found: ${ext}`);
			}
		}
		catch (err)
		{
			console.error(`in-app-url: unable to load routes extension: ${ext}`, err);
		}

	});

	module.exports = { inAppUrl };

});
