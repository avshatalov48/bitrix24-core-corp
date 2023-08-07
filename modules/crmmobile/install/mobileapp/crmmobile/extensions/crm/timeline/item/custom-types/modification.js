/**
 * @module crm/timeline/item/custom-types/modification
 */
jn.define('crm/timeline/item/custom-types/modification', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class Modification
	 */
	class Modification extends TimelineItemBase
	{
		getBodyBottomGap()
		{
			return 10;
		}
	}

	module.exports = { Modification };
});
