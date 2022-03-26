(() => {
	class ButtonsToolbar extends LayoutComponent
	{
		render()
		{
			return new UI.BottomToolbar({
				isWithSafeArea: (this.props.isWithSafeArea || false),
				items: this.props.buttons.map((button, index) => {
					return View(
						{
							style: {
								flex: 1,
								marginLeft: (index === 0 ? 2 : 8),
								marginRight: (index === this.props.buttons.length - 1 ? 2 : 0),
							},
						},
						button
					);
				}),
			});
		}
	}

	this.ButtonsToolbar = ButtonsToolbar;
})();
