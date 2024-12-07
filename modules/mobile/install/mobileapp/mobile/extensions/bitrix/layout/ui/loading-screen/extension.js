(() => {
	const AppTheme = jn.require('apptheme');

	/**
	 * @class LoadingScreenComponent
	 */
	class LoadingScreenComponent extends LayoutComponent
	{
		get colors()
		{
			return this.props.showAirStyle ? AppTheme.realColors : AppTheme.colors;
		}

		render()
		{
			const { backgroundColor, loaderSize, loaderColor, testId } = this.props;

			return View(
				{
					testId,
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						justifyContent: 'center',
						alignItems: 'center',
						backgroundColor: backgroundColor || this.colors.bgPrimary,
					},
				},
				Loader({
					style: {
						width: 50,
						height: 50,
					},
					tintColor: loaderColor || this.colors.base3,
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
