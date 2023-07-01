/**
 * @module crm/storage
 */
jn.define('crm/storage', (require, exports, module) => {
	const { BaseStorage } = require('crm/storage/base');
	const { CategoryStorage } = require('crm/storage/category');

	module.exports = {
		BaseStorage,
		CategoryStorage,
	};
});
