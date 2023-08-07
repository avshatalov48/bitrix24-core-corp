/**
 * @module crm/timeline/item/custom-types
 */
jn.define('crm/timeline/item/custom-types', (require, exports, module) => {
	const { CallActivity } = require('crm/timeline/item/custom-types/call-activity');
	const { Modification } = require('crm/timeline/item/custom-types/modification');

	module.exports = {
		CallActivity,
		Modification,
	};
});
