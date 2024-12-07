/**
 * @module call/calls-card/card-content/elements/button
 */
jn.define('call/calls-card/card-content/elements/button', (require, exports, module) => {
	const DEFAULT_COLOR = '#FFFFFF';
	const PRESSED_COLOR = '#2FC6F6';

	/**
	 * @class Button
	 */
	class Button extends LayoutComponent
	{
		constructor(props) {
			super(props);
			this.state = {
				selected: false,
			};
		}

		get icon()
		{
			return BX.prop.getString(this.props, 'icon', null);
		}

		get buttonText()
		{
			return BX.prop.getString(this.props, 'text', null);
		}

		get isSwitchable()
		{
			return BX.prop.getBoolean(this.props, 'isSwitchable', false);
		}

		get eventName()
		{
			return BX.prop.getString(this.props, 'eventName', null);
		}

		get enabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}

		get testId()
		{
			return BX.prop.getString(this.props, 'testId', null);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						alignItems: 'center',
						marginHorizontal: 6,
						minWidth: 69.75,
					},
					clickable: false,
				},
				View(
					{
						style: {
							width: 52,
							height: 52,
							marginBottom: 2,
							flexDirection: 'column',
							justifyContent: 'center',
							alignItems: 'center',
						},
						clickable: false,
					},
					View(
						{
							style: {
								width: 47,
								height: 47,
								borderRadius: 23.5,
								backgroundColor: {
									default: this.getBackgroundColor(),
									pressed: this.enabled ? PRESSED_COLOR : DEFAULT_COLOR,
								},
								opacity: this.enabled ? 0.3 : 0.07,
							},
							testId: this.testId,
							onTouchesBegan: () => {
								if (this.isSwitchable)
								{
									if (this.eventName && this.props.onUiEvent && this.enabled)
									{
										this.setState({
											selected: !this.state.selected
										}, () => {
											this.props.onUiEvent({
												eventName: this.eventName,
												params: {
													selected: this.state.selected,
												},
											});

											if (this.props.onClick)
											{
												this.props.onClick({selected: this.state.selected});
											}
										});
									}
								}
							},
							onClick: () => {
								if (!this.isSwitchable)
								{
									if (this.eventName && this.props.onUiEvent && this.enabled)
									{
										this.props.onUiEvent({
											eventName: this.eventName,
											params: {
												selected: this.state.selected,
											},
										});

										if (this.props.onClick)
										{
											this.props.onClick({selected: this.state.selected});
										}
									}
								}
							},
						},
					),
					View(
						{
							style: {
								width: 47,
								height: 47,
								marginTop: -47,
								justifyContent: 'center',
								alignItems: 'center',
							},
							clickable: false,
						},
						Image({
							style: {
								width: 35,
								height: 35,
								opacity: this.enabled ? 1 : 0.4,
							},
							svg: {
								content: this.icon,
							},
						}),
					),
				),
				Text({
					style: {
						color: '#FFFFFF',
						fontSize: 12,
						opacity: 0.5,
						marginTop: -3,
					},
					text: this.buttonText,
				}),
			);
		}

		getBackgroundColor()
		{
			if (this.isSwitchable && this.state.selected)
			{
				return PRESSED_COLOR;
			}

			return DEFAULT_COLOR;
		}
	}

	module.exports = { Button };
});