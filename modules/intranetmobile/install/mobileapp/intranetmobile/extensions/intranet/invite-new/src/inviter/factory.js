/**
 * @module intranet/invite-new/src/inviter/factory
 */
jn.define('intranet/invite-new/src/inviter/factory', (require, exports, module) => {
	const { LinkInviter } = require('intranet/invite-new/src/inviter/link');
	const { SMSInviter } = require('intranet/invite-new/src/inviter/sms');
	const { EmailInviter } = require('intranet/invite-new/src/inviter/email');
	const { QRInviter } = require('intranet/invite-new/src/inviter/qr');
	const { InviteCases } = require('intranet/invite-new/src/inviter/type');

	/**
	 * @class InviterFactory
	 */
	class InviterFactory
	{
		/**
		 * @param {InviteCases} inviteCase
		 * @param {Object} props
		 * @returns {BaseInviter}
		 */
		static createInviterByCase = (inviteCase, props) => {
			const preparedProps = {
				...props,
				inviteCase,
			};
			switch (inviteCase)
			{
				case InviteCases.LINK:
					return new LinkInviter(preparedProps);
				case InviteCases.SMS:
					return new SMSInviter(preparedProps);
				case InviteCases.EMAIL:
					return new EmailInviter(preparedProps);
				case InviteCases.QR:
					return new QRInviter(preparedProps);
				default:
					return null;
			}
		};
	}

	module.exports = { InviterFactory };
});
