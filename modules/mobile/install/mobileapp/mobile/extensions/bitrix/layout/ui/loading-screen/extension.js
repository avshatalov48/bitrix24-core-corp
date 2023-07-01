(() => {

	/**
	 * @class LoadingScreenComponent
	 */
	class LoadingScreenComponent extends LayoutComponent
	{
		constructor(props)
		{
			props = props || {};
			props.backgroundColor = props.backgroundColor || '#f5f7f8';
			props.loaderColor = props.loaderColor || '#828b95';
			props.loaderSize = props.loaderSize || 'small';

			super(props);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						justifyContent: 'center',
						alignItems: 'center',
						backgroundColor: this.props.backgroundColor,
					},
				},
				Loader({
					style: {
						width: 50,
						height: 50,
					},
					tintColor: this.props.loaderColor,
					animating: true,
					size: this.props.loaderSize,
				})
			)
		}
	}

	jnexport(LoadingScreenComponent);

})();