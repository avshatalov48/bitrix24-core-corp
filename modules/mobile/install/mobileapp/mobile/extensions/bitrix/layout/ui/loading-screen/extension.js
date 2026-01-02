/**
 * @module layout/ui/loading-screen
 */
jn.define('layout/ui/loading-screen', (require, exports, module) => {
	const { Color } = require('tokens');

	/**
	 * @typedef LoadingScreenComponentProps
	 * @property {string} testId
	 * @property {string} [backgroundColor]
	 * @property {'small' | 'large'} [loaderSize]
	 * @property {string} [tintColor]
	 *
	 * @class LoadingScreenComponent
	 */
	class LoadingScreenComponent extends LayoutComponent
	{
		render()
		{
			const {
				backgroundColor,
				loaderSize,
				loaderColor,
				testId,
			} = this.props;

			return View(
				{
					testId,
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						justifyContent: 'center',
						alignItems: 'center',
						backgroundColor: backgroundColor || Color.bgPrimary.toHex(),
					},
				},
				Loader({
					style: {
						width: 50,
						height: 50,
					},
					tintColor: loaderColor || Color.base3.toHex(),
					animating: true,
					size: loaderSize || 'small',
				}),
			);
		}
	}

	module.exports = {
		/**
		 * @param {LoadingScreenComponentProps} props
		 * @return View
		 */
		LoadingScreen: (props) => new LoadingScreenComponent(props),
		LoadingScreenComponent,
	};
});

// todo remove after all global usages in other modules will be cleaned
(function() {
	const require = (ext) => jn.require(ext);
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');

	jnexport(LoadingScreenComponent);
})();
