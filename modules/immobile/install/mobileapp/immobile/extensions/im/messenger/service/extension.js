/**
 * @module im/messenger/service
 */
jn.define('im/messenger/service', (require, exports, module) => {

	const { DialogService } = jn.require('im/messenger/service/dialog');
	const { ChatService } = jn.require('im/messenger/service/chat');
	const { MessageService } = jn.require('im/messenger/service/message');
	const { RecentService } = jn.require('im/messenger/service/recent');
	const { PromotionService } = jn.require('im/messenger/service/promotion');
	const { UserService } = jn.require('im/messenger/service/user');

	module.exports = {
		DialogService: new DialogService(),
		ChatService: new ChatService(),
		MessageService: new MessageService(),
		RecentService: new RecentService(),
		PromotionService: new PromotionService(),
		UserService: new UserService(),
	};
});
