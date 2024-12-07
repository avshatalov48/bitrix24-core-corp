/**
 * @module tasks/layout/stage-list/item
 */
jn.define('tasks/layout/stage-list/item', (require, exports, module) => {
	const { StageListItem, MIN_STAGE_HEIGHT } = require('layout/ui/stage-list/item');
	const { connect } = require('statemanager/redux/connect');
	const {
		getUniqId,
	} = require('tasks/statemanager/redux/slices/kanban-settings');
	const {
		selectById: selectStageById,
	} = require('tasks/statemanager/redux/slices/stage-settings');
	const {
		selectByStageIdAndFilterParams,
	} = require('tasks/statemanager/redux/slices/stage-counters');

	/**
	 * @class TasksStageListItem
	 */
	class TasksStageListItem extends StageListItem
	{
		isUnsuitable()
		{
			if (!this.showAllStagesItem)
			{
				return (
					!this.props.active
					&& (this.props.stage.statusId === 'PERIOD1')
				);
			}

			return super.isUnsuitable();
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const stage = ownProps.stage ?? selectStageById(state, ownProps.id);
		let counter = null;
		if (typeof stage !== 'undefined')
		{
			counter = {
				count: selectByStageIdAndFilterParams(state, {
					...ownProps.filterParams,
					stageId: stage.id === getUniqId(
						ownProps.filterParams.view,
						ownProps.filterParams.projectId,
						ownProps.filterParams.searchParams.ownerId,
					) ? 'total' : stage.id,
				}),
			};

			if (counter.count === null)
			{
				counter = null;
			}

			return {
				stage,
				counter,
			};
		}
	};

	module.exports = {
		TasksStageListItem: connect(mapStateToProps)(TasksStageListItem),
		MIN_STAGE_HEIGHT,
	};
});
