BX.addCustomEvent('onIntentHandle', (intent) => {
	/** @var {MobileIntent} intent */
	intent.addHandler(() => {
		const value = intent.check(['preset_terminal', 'terminal']);
		if (value === 'terminal' || value === 'preset_terminal')
		{
			ComponentHelper.openLayout({
				name: 'crm:crm.terminal.list',
				object: 'layout',
				widgetParams: {
					titleParams: {
						useLargeTitleMode: true,
					},
				},
			});
		}
	});
});
