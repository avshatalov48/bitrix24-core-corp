BX.addCustomEvent("onIntentHandle", intent => {
	/** @var {MobileIntent} intent */
	intent.addHandler( () => {
		const value = intent.check(['preset_terminal', 'terminal'])
		if (value === 'terminal' || value === 'preset_terminal')
		{
			if (Application.getApiVersion() < 49)
			{
				const { Feature } = jn.require('feature');
				Feature.showDefaultUnsupportedWidget();
				return;
			}

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
	})
});
