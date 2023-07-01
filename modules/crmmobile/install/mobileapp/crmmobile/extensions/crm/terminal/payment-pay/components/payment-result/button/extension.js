/**
 * @module crm/terminal/payment-pay/components/payment-result/button
 */
jn.define('crm/terminal/payment-pay/components/payment-result/button', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { mergeImmutable } = require('utils/object');

	/**
	 * @class Button
	 */
	class Button extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.testId = props.testId || '';
		}

		render()
		{
			return View(
				{
					testId: this.testId,
					style: this.styles.button,
					onClick: () => {
						if (this.props.onClick)
						{
							this.props.onClick();
						}
					},
				},
				this.buttonText && Text({
					style: this.styles.buttonText,
					text: this.buttonText,
				}),
			);
		}

		get styles()
		{
			return mergeImmutable(styles, this.props.styles || {});
		}

		get buttonText()
		{
			return BX.prop.getString(this.props, 'buttonText', null);
		}
	}

	const styles = {
		button: {
			flexDirection: 'row',
			justifyContent: 'center',
			alignItems: 'center',
			borderRadius: 6,
			borderWidth: 1,
			height: 48,
			width: 282,
			marginHorizontal: 46,
		},
		buttonText: {
			color: '#FFFFFF',
			fontSize: 17,
			fontWeight: '500',
		},
	};

	module.exports = { Button };
});
