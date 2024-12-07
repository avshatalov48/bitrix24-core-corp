/**
 * @module im/messenger/provider/rest
 */
jn.define('im/messenger/provider/rest', (require, exports, module) => {
	const { DialogRest } = require('im/messenger/provider/rest/dialog');
	const { ChatRest } = require('im/messenger/provider/rest/chat');
	const { MessageRest } = require('im/messenger/provider/rest/message');
	const { RecentRest } = require('im/messenger/provider/rest/recent');
	const { PromotionRest } = require('im/messenger/provider/rest/promotion');
	const { UserRest } = require('im/messenger/provider/rest/user');
	const { OpenLinesRest } = require('im/messenger/provider/rest/openlines');
	const { CopilotRest } = require('im/messenger/provider/rest/copilot');

	module.exports = {
		DialogRest: new DialogRest(),
		ChatRest: new ChatRest(),
		MessageRest: new MessageRest(),
		RecentRest: new RecentRest(),
		PromotionRest: new PromotionRest(),
		UserRest: new UserRest(),
		OpenLinesRest: new OpenLinesRest(),
		CopilotRest: new CopilotRest(),
	};
});
