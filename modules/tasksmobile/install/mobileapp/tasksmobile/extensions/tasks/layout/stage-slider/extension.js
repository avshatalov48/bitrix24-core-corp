/**
 * @module tasks/layout/stage-slider
 */
jn.define('tasks/layout/stage-slider', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { StageSlider } = require('layout/ui/stage-slider');
	const { TaskStageItem } = require('tasks/layout/stage-slider/item');
	const {
		getUniqId,
		selectStages,
		selectCanMoveStage,
	} = require('tasks/statemanager/redux/slices/kanban-settings');

	/**
	 * @class TasksStageSlider
	 */
	class TasksStageSlider extends StageSlider
	{
		/**
		 * @param {array} currentStages
		 * @param {number} activeIndex
		 * @return {array}
		 */
		renderStages(currentStages, activeIndex)
		{
			return currentStages.map((stageId, index) => TaskStageItem({
				isReversed: this.isReversed,
				stageId,
				index,
				activeIndex,
				showMenu: !this.isReadOnly && activeIndex === index,
				onStageClick: this.onStageClickHandler,
				onStageLongClick: this.onStageLongClickHandler,
				isCurrent: index === activeIndex,
			}));
		}

		isActiveStageExist()
		{
			if (this.props.activeStageId === 0)
			{
				return true;
			}

			return super.isActiveStageExist();
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const kanbanSettingsId = getUniqId(ownProps.view, ownProps.projectId, ownProps.userId);

		return {
			kanbanSettingsId,
			stageIdsBySemantics: {
				processStages: selectStages(state, kanbanSettingsId),
			},
			canMoveStage: selectCanMoveStage(state, kanbanSettingsId),
		};
	};

	module.exports = {
		TasksStageSliderClass: TasksStageSlider,
		TasksStageSlider: connect(mapStateToProps)(TasksStageSlider),
	};
});
