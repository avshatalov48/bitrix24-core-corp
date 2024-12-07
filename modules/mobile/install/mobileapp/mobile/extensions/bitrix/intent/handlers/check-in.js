BX.addCustomEvent('onIntentHandle', (intent) => {
	const require = (ext) => jn.require(ext);
	const { Feature } = require('feature');

	/** @var {MobileIntent} intent */
	intent.addHandler(() => {
		const value = intent.check(['check-in', 'check-in-settings']);
		if (Feature.isAirStyleSupported() && (value === 'check-in' || value === 'check-in-settings'))
		{
			requireLazy('stafftrack:entry')
				.then(({ Entry }) => {
					if (Entry)
					{
						Entry.openCheckIn({
							dialogName: null,
							dialogId: null,
							openSettings: value === 'check-in-settings',
						});
					}
				})
				.catch(console.error);
		}
	});
});
