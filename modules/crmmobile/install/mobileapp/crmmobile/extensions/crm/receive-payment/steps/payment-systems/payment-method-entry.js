/**
 * @module crm/receive-payment/steps/payment-systems/payment-method-entry
 */
jn.define('crm/receive-payment/steps/payment-systems/payment-method-entry', (require, exports, module) => {
	const { PaymentSystemService } = require('crm/terminal/services/payment-system');
	const { BooleanField } = require('layout/ui/fields/boolean');
	const { handleErrors } = require('crm/error');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @class PaymentMethodEntry
	 */
	class PaymentMethodEntry extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.paymentSystemService = new PaymentSystemService();
			this.state = {
				isEnabled: props.item.props.enabled,
				paySystemId: props.item.props.id,
			};

			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.onPaymentMethodToggle = this.onPaymentMethodToggle.bind(this);
		}

		getItem()
		{
			return this.props.item;
		}

		render()
		{
			return View(
				{},
				BooleanField({
					value: this.state.isEnabled,
					showTitle: false,
					config: {
						description: this.getItem().props.title,
						styles: {
							container: {
								marginLeft: 19,
								marginTop: 14,
							},
						},
					},
					onChange: this.onPaymentMethodToggle,
				}),
			);
		}

		emitSwitch(value)
		{
			this.customEventEmitter.emit('ReceivePayment::onSwitchPaySystem', {
				id: this.getItem().props.id,
				active: value,
				name: this.getItem().props.title,
			});
		}

		onPaymentMethodToggle(value)
		{
			const paySystemId = this.state.paySystemId;

			if (paySystemId)
			{
				const action = value ? 'activatePaySystem' : 'deactivatePaySystem';

				BX.ajax.runAction(
					`sale.paysystem.entity.${action}`,
					{
						data: {
							id: paySystemId,
						},
					},
				).then((response) => {
					this.emitSwitch(value);
					this.setState({
						isEnabled: value,
					});
				}).catch(handleErrors);
			}
			else if (value)
			{
				this.paymentSystemService
					.create({
						handler: this.props.handler,
						type: this.getItem().props.modeId,
					})
					.then((newPaySystemId) => {
						this.emitSwitch(true);
						this.setState({
							paySystemId: newPaySystemId,
							isEnabled: true,
						});
					})
					.catch(handleErrors)
				;
			}
		}
	}

	module.exports = { PaymentMethodEntry };
});
