/**
 * @module crm/storage
 */
jn.define('crm/storage', (require, exports, module) => {
	const { CategoryStorage } = require('crm/storage/category');

	module.exports = {
		CategoryStorage,
	};
});
