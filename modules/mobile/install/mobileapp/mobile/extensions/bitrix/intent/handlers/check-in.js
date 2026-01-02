BX.addCustomEvent('onIntentHandle', (intent) => {
	/** @var {MobileIntent} intent */
	intent.addHandler(() => {
		const value = intent.check(['check-in', 'check-in-settings']);
		if (value === 'check-in' || value === 'check-in-settings')
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
