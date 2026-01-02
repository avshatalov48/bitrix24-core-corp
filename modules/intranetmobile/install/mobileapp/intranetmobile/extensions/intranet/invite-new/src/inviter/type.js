/**
 * @module intranet/invite-new/src/inviter/type
 */
jn.define('intranet/invite-new/src/inviter/type', (require, exports, module) => {
	const InviteCases = {
		LINK: 'link',
		SMS: 'sms',
		EMAIL: 'email',
		QR: 'qr',
	};

	module.exports = { InviteCases };
});
