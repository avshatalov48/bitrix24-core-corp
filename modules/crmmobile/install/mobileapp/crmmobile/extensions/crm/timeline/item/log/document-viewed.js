/**
 * @module crm/timeline/item/log/document-viewed
 */
jn.define('crm/timeline/item/log/document-viewed', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class DocumentViewed
	 */
	class DocumentViewed extends TimelineItemBase
	{}

	module.exports = { DocumentViewed };
});
