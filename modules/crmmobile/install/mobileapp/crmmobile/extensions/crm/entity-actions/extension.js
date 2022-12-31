/**
 * @module crm/entity-actions
 */
jn.define('crm/entity-actions', (require, exports, module) => {

	const { getActionToChangePipeline } = require('crm/entity-actions/change-pipeline');

	module.exports = {
		getActionToChangePipeline,
	};
});