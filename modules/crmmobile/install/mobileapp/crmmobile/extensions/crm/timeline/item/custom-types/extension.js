/**
 * @module crm/timeline/item/custom-types
 */
jn.define('crm/timeline/item/custom-types', (require, exports, module) => {
	const { CallActivity } = require('crm/timeline/item/custom-types/call-activity');
	const { Modification } = require('crm/timeline/item/custom-types/modification');
	const { OpenlineChat } = require('crm/timeline/item/custom-types/openline-chat');

	module.exports = {
		CallActivity,
		Modification,
		OpenlineChat,
	};
});
