(() => {
	/**
	 * @class ListItems.LoadingElement
	 */
	class LoadingElement extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.text = this.props.text || BX.message('SIMPLELIST_LOADING_ELEMENT_TEXT');
			this.styles = Object.assign(styles, this.props.styles);
			this.params = Object.assign(params, this.props.params);
		}

		render()
		{
			return View(
				{
					style: this.styles.view,
				},

				Loader({
					style: this.styles.loader,
					tintColor: this.params.loaderColor,
					animating: true,
					size: this.params.loaderSize,
				}),

				Text({
					text: this.text,
					style: this.styles.text,
				}),
			);
		}
	}

	const styles = {
		view: {
			flexDirection: 'row',
			justifyContent: 'center',
			alignItems: 'center',
		},
		loader: {
			height: 30,
		},
		text: {
			marginTop: 1,
			marginLeft: 6,
			fontSize: 17,
			color: '#828b95'
		}
	}

	const params = {
		loaderColor: '#828b95',
		loaderSize: 'small',
	}

	this.ListItems = this.ListItems || {};
	this.ListItems.LoadingElement = LoadingElement;
})();
