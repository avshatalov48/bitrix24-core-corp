/**
 * @module crm/terminal/payment-pay/components/payment-button/button
 */
jn.define('crm/terminal/payment-pay/components/payment-button/button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { PureComponent } = require('layout/pure-component');
	const { withPressed } = require('utils/color');
	const { Random } = require('utils/random');

	/**
	 * @class PaymentButton
	 */
	class PaymentButton extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.testId = props.testId || '';

			this.randomUid = Random.getString(10);
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.onClick = this.onClickHandler.bind(this);
		}

		render()
		{
			return View(
				{
					testId: this.testId,
					style: {
						paddingTop: 10,
						paddingBottom: 10,
						paddingRight: 5,
						paddingLeft: 5,
						borderRadius: 6,
						borderWidth: 1,
						borderColor: AppTheme.colors.accentMainPrimary,
						backgroundColor: withPressed(AppTheme.colors.bgPrimary),
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
						width: '100%',
						height: PaymentButton.getHeight(),
						...this.containerStyles,
					},
					onClick: this.onClick,
				},
				this.iconUri && View(
					{
						style: {
							marginRight: 5,
							...this.iconContainerStyles,
						},
					},
					Image({
						style: {
							alignSelf: 'center',
							...this.iconStyles,
						},
						uri: this.iconUri,
					}),
				),
				this.text && Text({
					style: {
						color: AppTheme.colors.base1,
						fontSize: 17,
						fontWeight: '500',
						flexShrink: 1,
						...this.textStyles,
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.text,
				}),
			);
		}

		onClickHandler()
		{
			this.customEventEmitter.emit(
				'TerminalPayment::onPaymentMethodSelected',
				[this.paymentMethod],
			);
		}

		get paymentMethod()
		{
			return this.props.paymentMethod || {};
		}

		get text()
		{
			return BX.prop.getString(this.props, 'text', null);
		}

		get iconUri()
		{
			return BX.prop.getString(this.props, 'iconUri', null);
		}

		get styles()
		{
			return this.props.styles || {};
		}

		get containerStyles()
		{
			return this.styles.container || {};
		}

		get iconContainerStyles()
		{
			return this.styles.iconContainer || {};
		}

		get iconStyles()
		{
			return this.styles.icon || {};
		}

		get textStyles()
		{
			return this.styles.text || {};
		}

		get uid()
		{
			return this.props.uid || this.randomUid;
		}

		static getHeight()
		{
			return HEIGHT;
		}
	}

	const HEIGHT = 48;

	module.exports = { PaymentButton };
});
