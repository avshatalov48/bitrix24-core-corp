(() => {
	/**
	 * @class BottomPadding
	 */
	class BottomPadding extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: false
			};
		}

		show()
		{
			this.setState({visible: true});
		}

		hide()
		{
			this.setState({visible: false});
		}

		render()
		{
			return View({
				style: {
					paddingBottom: 90,
					display: this.state.visible ? 'flex' : 'none'
				}
			});
		}
	}

	this.BottomPadding = BottomPadding;
})();
