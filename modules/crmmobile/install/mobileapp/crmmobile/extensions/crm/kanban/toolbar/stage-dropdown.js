/**
 * @module crm/kanban/toolbar/stage-dropdown
 */
jn.define('crm/kanban/toolbar/stage-dropdown', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const {
		selectById: selectStageById,
	} = require('crm/statemanager/redux/slices/stage-settings');
	const {
		selectById: selectCounterById,
	} = require('crm/statemanager/redux/slices/stage-counters');
	const {
		getCrmKanbanUniqId,
		selectById: selectKanbanById,
	} = require('crm/statemanager/redux/slices/kanban-settings');

	const { StageDropdownClass } = require('layout/ui/kanban/toolbar');

	const mapStateToProps = (state, ownProps) => {
		const kanbanId = getCrmKanbanUniqId(ownProps.entityTypeId, ownProps.categoryId);
		const counterId = ownProps.activeStageId || kanbanId;
		const {
			processStages = [],
			successStages = [],
			failStages = [],
		} = selectKanbanById(state, kanbanId) || {};

		const stages = [...processStages, ...successStages, ...failStages];

		let activeStageExist = true;
		if (ownProps.activeStageId && ownProps.activeStageId !== kanbanId)
		{
			activeStageExist = isActiveStageExist(ownProps.activeStageId, stages);
		}

		return {
			activeStage: selectStageById(state, ownProps.activeStageId),
			counter: selectCounterById(state, counterId),
			activeStageExist,
		};
	};

	const isActiveStageExist = (stageId, stages) => {
		return stages.includes(stageId);
	};

	module.exports = {
		StageDropdown: connect(mapStateToProps)(StageDropdownClass),
	};
});
