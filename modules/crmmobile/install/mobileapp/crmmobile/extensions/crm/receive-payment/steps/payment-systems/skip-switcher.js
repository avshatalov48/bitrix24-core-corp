/**
 * @module crm/receive-payment/steps/payment-systems/skip-switcher
 */
jn.define('crm/receive-payment/steps/payment-systems/skip-switcher', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { transparent } = require('utils/color');
	const { PureComponent } = require('layout/pure-component');
	const { BooleanField } = require('layout/ui/fields/boolean');

	/**
	 * @class SkipSwitcher
	 */
	class SkipSwitcher extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				value: props.value,
			};
			this.onChangeHandler = props.onChangeHandler;
		}

		render()
		{
			return View(
				{
					style: {
						marginVertical: 10,
						borderRadius: 12,
						backgroundColor: transparent(AppTheme.colors.accentExtraAqua, 0.2),
					},
				},
				BooleanField({
					value: this.state.value,
					showTitle: false,
					config: {
						description: Loc.getMessage('M_RP_PS_SKIP_STEP'),
						styles: {
							container: {
								marginLeft: 24,
								marginTop: 17,
							},
							description: {
								marginLeft: 10,
								fontSize: 16,
								color: this.state.value ? AppTheme.colors.base1 : AppTheme.colors.base3,
							},
						},
					},
					onChange: (value) => {
						this.setState({ value });
						this.saveIsNeedToSkipPaymentSystems(value);
						if (this.onChangeHandler)
						{
							this.onChangeHandler(value);
						}
					},
				}),
				Text({
					style: {
						marginLeft: 80,
						marginRight: 16,
						marginBottom: 24,
						fontSize: 13,
						color: AppTheme.colors.base3,
					},
					text: Loc.getMessage('M_RP_PS_SKIP_STEP_DESC'),
				}),
			);
		}

		saveIsNeedToSkipPaymentSystems(isNeedToSkipPaymentSystems)
		{
			BX.ajax.runAction(
				'crmmobile.ReceivePayment.Option.saveIsNeedToSkipPaymentSystems',
				{
					json: { isNeedToSkipPaymentSystems },
				},
			);
		}
	}

	module.exports = { SkipSwitcher };
});
