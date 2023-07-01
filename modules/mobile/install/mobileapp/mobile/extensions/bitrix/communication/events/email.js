/**
 * @module communication/events/email
 */

jn.define('communication/events/email', (require, exports, module) => {
	const { BaseEvent } = require('communication/events/base');
	const { openEmailMenu } = require('communication/email-menu');
	const { inAppUrl } = require('in-app-url');
	const { Type } = require('type');
	const { get } = require('utils/object');
	const { stringify } = require('utils/string');

	const getMailOpener = () => {
		try
		{
			const { MailOpener } = require('crm/mail/opener');

			return MailOpener;
		}
		catch (e)
		{
			console.log(e, 'MailOpener not found');

			return null;
		}
	};

	class EmailEvent extends BaseEvent
	{
		prepareValue(value)
		{
			let email;
			let params;

			if (Type.isPlainObject(value))
			{
				email = value.email;
				params = value.params;
			}
			else if (Type.isString(value))
			{
				email = value;
				params = {};
			}

			email = stringify(email).trim();
			params = Type.isPlainObject(params) ? params : {};

			if (email === '')
			{
				return null;
			}

			return { email, params };
		}

		open()
		{
			if (this.isEmpty())
			{
				return;
			}

			if (this.isBitrixMailActive())
			{
				this.openSendingForm();
			}
			else
			{
				openEmailMenu(this.getValue());
			}
		}

		openSendingForm()
		{
			const { email, params } = this.getValue();

			const MailOpener = getMailOpener();

			if (MailOpener)
			{
				const {
					ownerId,
					ownerType,
				} = params.owner;

				MailOpener.openSend({
					contacts: [{
						email,
						id: ownerId,
						typeName: ownerType,
					}],
					owner: params.owner,
				});

				return;
			}

			inAppUrl.open(`mailto:${email}`);
		}

		isBitrixMailActive()
		{
			const MailOpener = getMailOpener();

			return MailOpener && MailOpener.isActiveMail();
		};
	}

	module.exports = { EmailEvent };
});
