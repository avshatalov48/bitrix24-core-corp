/**
 * @module im/messenger/model/sidebar/src/common-chats/default-element
 */

jn.define('im/messenger/model/sidebar/src/common-chats/default-element', (require, exports, module) => {
	const { UserRole, UserType } = require('im/messenger/const');

	const commonChatItem = {
		avatar: '',
		color: '',
		dateMessage: '',
		description: '',
		dialogId: '',
		diskFolderId: 0,
		entityId: '',
		entityType: '',
		extranet: false,
		id: 0,
		isNew: false,
		name: '',
		owner: 0,
		parentChatId: 0,
		parentMessageId: 0,
		role: UserRole.none,
		type: UserType.user,
	};

	module.exports = {
		commonChatItem,
	};
});
