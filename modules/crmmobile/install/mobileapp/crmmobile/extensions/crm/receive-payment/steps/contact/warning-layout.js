/**
 * @module crm/receive-payment/steps/contact/warning-layout
 */
jn.define('crm/receive-payment/steps/contact/warning-layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WarningBlock } = require('layout/ui/warning-block');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @class WarningLayout
	 */
	class WarningLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				showPhoneWarning: false,
			};

			this.hasSmsProviders = props.hasSmsProviders;
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.handleClientUpdate = this.handleClientUpdate.bind(this);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('UI.Fields.Client::onUpdate', this.handleClientUpdate);
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('UI.Fields.Client::onUpdate', this.handleClientUpdate);
		}

		handleClientUpdate(data)
		{
			const contact = data.value.contact[0] ?? null;
			const phoneExists = this.doesContactHavePhone(contact);
			if (contact)
			{
				this.setState({ showPhoneWarning: !phoneExists });
			}
			this.customEventEmitter.emit('ReceivePayment::onContactPhoneChecked', { phoneExists, contact });
		}

		doesContactHavePhone(contact)
		{
			return Boolean(contact && contact.phone && contact.phone[0] && contact.phone[0].value);
		}

		render()
		{
			return View(
				{
					style: {
						marginBottom: 8,
					},
				},
				!this.hasSmsProviders && new WarningBlock({
					title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_SMS_PROVIDERS_TITLE'),
					description: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_SMS_PROVIDERS_TEXT'),
					layout: PageManager,
					redirectUrl: '/saleshub/',
					analyticsSection: 'crm',
				}),
				this.state.showPhoneWarning && new WarningBlock({
					title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_CONTACT_PHONE_TITLE'),
					description: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_CONTACT_PHONE_TEXT'),
				}),
			);
		}
	}

	module.exports = { WarningLayout };
});
