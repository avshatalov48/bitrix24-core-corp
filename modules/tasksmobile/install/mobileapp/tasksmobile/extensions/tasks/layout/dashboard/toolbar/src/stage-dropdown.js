/**
 * @module tasks/layout/dashboard/toolbar/src/stage-dropdown
 */
jn.define('tasks/layout/dashboard/toolbar/src/stage-dropdown', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const {
		selectById: selectStageById,
	} = require('tasks/statemanager/redux/slices/stage-settings');
	const {
		selectByStageIdAndFilterParams,
		allStagesId,
	} = require('tasks/statemanager/redux/slices/stage-counters');
	const { StageDropdown } = require('layout/ui/kanban/toolbar');

	const mapStateToProps = (state, ownProps) => {
		const count = selectByStageIdAndFilterParams(state, {
			...ownProps.filterParams,
			stageId: ownProps.activeStageId,
		});

		return {
			activeStage: ownProps.activeStageId === allStagesId
				? null
				: selectStageById(state, ownProps.activeStageId),
			counter: count === null ? count : { count },
		};
	};

	module.exports = {
		StageDropdown: connect(mapStateToProps)(StageDropdown),
	};
});
