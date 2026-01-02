/**
 * @module im/messenger/model/sidebar/src/links/default-element
 */

jn.define('im/messenger/model/sidebar/src/links/default-element', (require, exports, module) => {
	const linkItem = {
		id: 0,
		messageId: 0,
		chatId: 0,
		authorId: 0,
		dateCreate: new Date(),
		fileId: 0,
	};

	function linkDefaultElement()
	{
		return {
			links: new Map(),
			hasNextPage: true,
			isHistoryLimitExceeded: false,
		};
	}

	module.exports = {
		linkDefaultElement,
		linkItem,
	};
});
