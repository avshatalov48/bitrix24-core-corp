(() => {
	class BaseButton extends LayoutComponent
	{
		animate(options)
		{
			if (this.buttonRef)
			{
				this.buttonRef.animate(options);
			}
		}

		render()
		{
			const { icon, text, onClick } = this.props;
			const rounded = this.isRounded();

			let { style, testId } = this.props;
			style = style || {};
			testId = testId || this.constructor.name.toUpperCase();

			return View(
				{
					style: {
						...{
							flexDirection: 'row',
							justifyContent: 'center',
							height: rounded ? 48 : 52,
							borderRadius: rounded ? 24 : 0,
						},
						...this.getStyle().button,
						...(style.button || {}),
					},
					testId,
					ref: (ref) => this.buttonRef = ref,
					onClick: onClick,
				},
				(icon && Image({
					style: {
						...{
							width: 28,
							height: 28,
							alignSelf: 'center',
						},
						...this.getStyle().icon,
						...(style.icon || {}),
					},
					svg: {
						content: icon,
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
						...(style.text || {}),
					},
					text,
				}),
			);
		}

		isRounded()
		{
			return BX.prop.getBoolean(this.props, 'rounded', false);
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
