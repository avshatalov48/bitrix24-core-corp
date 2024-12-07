/**
 * @module crm/stage-selector/item
 */
jn.define('crm/stage-selector/item', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { StageItemClass } = require('layout/ui/fields/stage-selector');

	const {
		selectById,
	} = require('crm/statemanager/redux/slices/stage-settings');

	const mapStateToProps = (state, { stageId }) => ({
		stage: selectById(state, stageId),
	});

	module.exports = {
		CrmStageSelectorItem: connect(mapStateToProps)(StageItemClass),
	};
});
