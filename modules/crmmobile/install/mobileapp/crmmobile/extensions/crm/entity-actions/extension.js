/**
 * @module crm/entity-actions
 */
jn.define('crm/entity-actions', (require, exports, module) => {
	const { getActionToChangePipeline } = require('crm/entity-actions/change-pipeline');
	const { getActionChangeCrmMode } = require('crm/entity-actions/change-crm-mode');
	const { getActionToChangeStage } = require('crm/entity-actions/change-stage');
	const { getActionToCopyEntity } = require('crm/entity-actions/copy-entity');
	const { getActionToShare } = require('crm/entity-actions/share');

	module.exports = {
		getActionToChangePipeline,
		getActionToChangeStage,
		getActionChangeCrmMode,
		getActionToCopyEntity,
		getActionToShare,
	};
});
