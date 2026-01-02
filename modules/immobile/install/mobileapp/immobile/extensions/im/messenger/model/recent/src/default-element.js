/**
 * @module im/messenger/model/recent/default-element
 */

jn.define('im/messenger/model/recent/default-element', (require, exports, module) => {
	const { MessageStatus } = require('im/messenger/const');

	const recentDefaultElement = Object.freeze({
		id: '',
		message: {
			id: 0,
			senderId: 0,
			date: new Date(),
			status: MessageStatus.received,
			subTitleIcon: '',
			sending: false,
			text: '',
			params: {
				withFile: false,
				withAttach: false,
			},
		},
		dateMessage: null,
		lastActivityDate: new Date(),
		unread: false,
		pinned: false,
		liked: false,
		invitation: {
			isActive: false,
			originator: 0,
			canResend: false,
		},
		options: {},
		uploadingState: null,
	});

	module.exports = {
		recentDefaultElement,
	};
});
