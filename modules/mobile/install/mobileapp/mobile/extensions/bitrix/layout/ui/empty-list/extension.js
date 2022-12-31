(() => {

	/**
	 * @class EmptyListComponent
	 * @deprecated Please use layout/ui/empty-screen instead
	 */
	class EmptyListComponent extends LayoutComponent
	{
		constructor(props)
		{
			props.style = props.style || {};

			super(props);

			this.state = {
				text: props.text,
				svg: props.svg,
				textColor: props.style.textColor || '#828B95',
				backgroundColor: props.style.backgroundColor || false,
			};
		}

		render()
		{
			return View(
				{
					style: this.getContainerStyle(),
				},

				this.renderIcon(),
				this.renderText()
			);
		}

		getContainerStyle()
		{
			const style = {
				flexDirection: 'column',
				flexGrow: 1,
				justifyContent: 'center',
				alignItems: 'center',
				paddingTop: 35,
				paddingBottom: 35,
			};

			if (this.state.backgroundColor)
			{
				style.backgroundColor = this.state.backgroundColor;
			}

			return style;
		}

		renderIcon()
		{
			return Image({
				style: {
					width: 102,
					height: 102,
					marginBottom: 20,
				},
				svg: this.state.svg,
			});
		}

		renderText()
		{
			return Text({
				style: {
					color: this.state.textColor,
					fontSize: 17,
					textAlign: 'center',
				},
				text: this.state.text,
			});
		}
	}

	jnexport(EmptyListComponent)

})();