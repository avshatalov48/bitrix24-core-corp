(() => {
	class BaseButton extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						...{
							flexDirection: 'row',
							justifyContent: 'center',
							borderWidth: 1,
							borderRadius: 23,
							height: 48,
						},
						...this.getStyle().button,
						...this.props.style.button,
					},
					onClick: this.props.onClick,
				},
				(this.props.icon && Image({
					style: {
						...{
							width: 28,
							height: 28,
							alignSelf: 'center',
						},
						...this.getStyle().icon,
						...this.props.style.icon
					},
					svg: {
						content: this.props.icon,
					},
				})),
				Text({
					style: {
						...{
							fontWeight: 'bold',
							fontSize: 15,
							ellipsize: 'end',
							numberOfLines: 1,
						},
						...this.getStyle().text,
						...this.props.style.text,
					},
					text: this.props.text,
				}),
			);
		}

		getStyle()
		{
			return {
				button: {},
				icon: {},
				text: {},
			};
		}
	}

	this.BaseButton = BaseButton;
})();
