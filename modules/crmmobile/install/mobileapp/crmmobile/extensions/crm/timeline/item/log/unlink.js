/**
 * @module crm/timeline/item/log/unlink
 */
jn.define('crm/timeline/item/log/unlink', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class Unlink
	 */
	class Unlink extends TimelineItemBase
	{}

	module.exports = { Unlink };
});
