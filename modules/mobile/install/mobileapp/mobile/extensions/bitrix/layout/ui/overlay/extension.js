(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/overlay/';

	class Overlay extends LayoutComponent
	{
		render()
		{
			return View({
				style: {
					position: 'absolute',
					height: '100%',
					width: '100%',
					opacity: 0,
					justifyContent: 'center',
					backgroundColor: AppTheme.colors.accentMainSuccess,
				},
				ref: (view) => {
					this.view = view;
				},
			}, Image({
				style: {
					alignSelf: 'center',
					alignItems: 'center',
					resizeMode: 'contain',
					width: 180,
					height: 180,
				},
				svg: { uri: `${currentDomain}${pathToExtension}images/${this.props.type}.svg` },
			}));
		}

		show(options = {})
		{
			const duration = options.duration || 1000;

			return new Promise((resolve) => {
				this.view.animate({
					duration,
					opacity: 0.8,
				}, () => {
					resolve();
				});
			});
		}

		hide()
		{
			return new Promise((resolve) => {
				this.view.animate({
					duration: 300,
					opacity: 0,
				}, () => {
					resolve();
				});
			});
		}
	}

	this.UI = this.UI || {};

	Overlay.Types = {
		SUCCESS: 'success',
	};
	this.UI.Overlay = Overlay;
})();
