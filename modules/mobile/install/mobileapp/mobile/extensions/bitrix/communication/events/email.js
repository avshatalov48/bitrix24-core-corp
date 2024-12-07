/**
 * @module communication/events/email
 */

jn.define('communication/events/email', (require, exports, module) => {
	const { BaseEvent } = require('communication/events/base');
	const { inAppUrl } = require('in-app-url');
	const { Type } = require('type');
	const { stringify } = require('utils/string');

	class EmailEvent extends BaseEvent
	{
		prepareValue(value)
		{
			let email;
			let params;
			let isEmailHidden;

			if (Type.isPlainObject(value))
			{
				email = value.email;
				params = value.params;
				isEmailHidden = value.isEmailHidden;
			}
			else if (Type.isString(value))
			{
				email = value;
				params = {};
				isEmailHidden = false;
			}

			email = stringify(email).trim();
			params = Type.isPlainObject(params) ? params : {};

			if (email === '')
			{
				return null;
			}

			return { email, params, isEmailHidden };
		}

		async open()
		{
			if (this.isEmpty())
			{
				return;
			}

			void this.openSendingForm();
		}

		async openSendingForm()
		{
			const { MailOpener } = await requireLazy('crm:mail/opener') || {};

			if (MailOpener)
			{
				const { email, params, isEmailHidden } = this.getValue();
				const {
					ownerId,
					ownerType,
				} = params.owner;

				MailOpener.openSend({
					contacts: [{
						email,
						id: ownerId,
						typeName: ownerType,
						isEmailHidden,
					}],
					owner: params.owner,
				});

				return;
			}

			inAppUrl.open(`mailto:${email}`);
		}

		async isBitrixMailActive()
		{
			const { MailOpener } = await requireLazy('crm:mail/opener') || {};

			return MailOpener && MailOpener.isActiveMail();
		}
	}

	module.exports = { EmailEvent };
});
