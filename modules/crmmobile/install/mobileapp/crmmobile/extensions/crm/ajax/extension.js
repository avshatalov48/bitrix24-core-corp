/**
 * @module crm/ajax
 */
jn.define('crm/ajax', (require, exports, module) => {
	const { CategoryAjax, CategoryActions } = require('crm/ajax/category');
	const { StageAjax, StageActions } = require('crm/ajax/stage');

	module.exports = {
		CategoryAjax,
		CategoryActions,

		StageAjax,
		StageActions,
	};
});
