(() => {
	class BizprocBackgroundOpener
	{
		constructor()
		{
			this.bindEvents();
		}

		bindEvents()
		{
			BX.addCustomEvent('bizprocbackground::task::open', (props) => {
				// eslint-disable-next-line no-undef
				ComponentHelper.openLayout(
					{
						name: 'bizproc:tab',
						canOpenInDefault: true,
						componentParams: {
							openUrl: props.url || null,
							setTitle: true,
						},
						object: 'layout',
					},
				);
			});

			BX.addCustomEvent('bizprocbackground::tab::open', () => {
				// eslint-disable-next-line no-undef
				ComponentHelper.openLayout(
					{
						name: 'bizproc:tab',
						canOpenInDefault: true,
						componentParams: {
							setTitle: true,
						},
						object: 'layout',
					},
				);
			});
		}
	}

	this.BizprocBackgroundOpener = new BizprocBackgroundOpener();
})();
