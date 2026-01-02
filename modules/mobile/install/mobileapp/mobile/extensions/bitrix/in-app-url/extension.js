/**
 * @module in-app-url
 */
jn.define('in-app-url', (require, exports, module) => {
	const { InAppUrl } = require('in-app-url/src/in-app-url');

	const inAppUrl = new InAppUrl();

	/**
	 * Register your routes extensions here,
	 * and don't forget to add them into deps.php
	 */
	const routes = [
		'sign/in-app-url/routes',
		'crm/in-app-url/routes',
		'tasks/in-app-url/routes',
		'im/in-app-url/routes',
		'calendar/in-app-url/routes',
		'stafftrack/in-app-url/routes',
		'lists/in-app-url/routes',
		'bizproc/in-app-url/routes',
		'disk/in-app-url/routes',
		'in-app-url/routes',
		'in-app-url/routes/development-routes',
		'in-app-url/routes/timeman',
		'in-app-url/routes/stafftrack',
		'in-app-url/routes/settings',
		'in-app-url/routes/bitrix24',
		'intranet/in-app-url/routes',
		'call/in-app-url/routes',
	];

	routes.forEach((ext) => {
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
