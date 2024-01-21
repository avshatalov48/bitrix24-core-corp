(() => {
	const AppTheme = jn.require('apptheme');

	/**
	 * @class LoadingScreenComponent
	 */
	class LoadingScreenComponent extends LayoutComponent
	{
		render()
		{
			const { backgroundColor, loaderSize, loaderColor } = this.props;

			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						justifyContent: 'center',
						alignItems: 'center',
						backgroundColor: backgroundColor || AppTheme.colors.bgPrimary,
					},
				},
				Loader({
					style: {
						width: 50,
						height: 50,
					},
					tintColor: loaderColor || AppTheme.colors.base3,
					animating: true,
					size: loaderSize || 'small',
				}),
			);
		}
	}

	jnexport(LoadingScreenComponent);

	/**
	 * @module layout/ui/loading-screen
	 */
	jn.define('layout/ui/loading-screen', (require, exports, module) => {
		module.exports = { LoadingScreenComponent };
	});
})();
