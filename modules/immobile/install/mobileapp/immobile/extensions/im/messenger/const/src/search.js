/**
 * @module im/messenger/const/search
 */
jn.define('im/messenger/const/search', (require, exports, module) => {
	const SearchEntityIdTypes = {
		user: 'user',
		imUser: 'im-user',
		bot: 'im-bot',
		chat: 'im-chat',
		chatUser: 'im-chat-user',
		department: 'department',
		network: 'imbot-network',
	};

	module.exports = { SearchEntityIdTypes };
});
