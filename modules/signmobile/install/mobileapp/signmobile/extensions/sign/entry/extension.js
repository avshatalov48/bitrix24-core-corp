/**
 * @module sign/entry
 */
jn.define('sign/entry', (require, exports, module) => {
	const { Loc } = require('loc');

	class Entry
	{
		static entryVersion()
		{
			return 1;
		}

		static openE2bMaster()
		{
			const name = 'sign:sign.b2e.grid';
			const version = availableComponents[name] && availableComponents[name].version || '1.0';
			PageManager.openComponent('JSStackComponent', {
				scriptPath: `/mobileapp/jn/${name}/?version=${version}`,
				componentCode: name,
				canOpenInDefault: true,
				params: {
					startE2bMaster: true,
				},
				rootWidget: {
					name: 'layout',
					settings: {
						objectName: 'layout',
						titleParams: {
							text: Loc.getMessage('SIGN_MOBILE_ENTRY_COMPONENT_TITLE'),
							type: "section",
						},
					},
				},
			});
		}
	}

	if (typeof jnComponent?.preload === 'function')
	{
		const componentCode = 'sign:sign.b2e.grid';

		// eslint-disable-next-line no-undef
		const { publicUrl } = availableComponents[componentCode] || {};

		if (publicUrl)
		{
			setTimeout(() => jnComponent.preload(publicUrl), 1000);
		}
	}

	module.exports = { Entry };
});
