/**
 * @module crm/timeline/stream/utils
 */
jn.define('crm/timeline/stream/utils', (require, exports, module) => {
	const { ItemPositionCalculator } = require('crm/timeline/stream/utils/item-position-calculator');
	const { Patch } = require('crm/timeline/stream/utils/patch');

	module.exports = {
		ItemPositionCalculator,
		Patch,
	};
});
