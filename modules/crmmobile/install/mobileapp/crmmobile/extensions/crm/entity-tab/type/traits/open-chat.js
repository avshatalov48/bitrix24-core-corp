/**
 * @module crm/entity-tab/type/traits/open-chat
 */
jn.define('crm/entity-tab/type/traits/open-chat', (require, exports, module) => {
	const { EntityChatOpener } = require('crm/entity-chat-opener');

	function openChat(params, action, itemId) // action, itemId, customParam)
	{
		return new Promise((resolve, reject) => {
			EntityChatOpener.openChatByEntity(params.entityTypeId, itemId).then(
				() => {
					resolve();
				},
				(error) => {
					console.error(error);
					reject(error);
				},
			)
				.catch(({ errors }) => {
					console.error(errors);
					reject(errors);
				});
		});
	}

	module.exports = { openChat };
});
