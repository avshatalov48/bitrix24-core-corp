/**
 * @module tasks/layout/stage-slider/item
 */
jn.define('tasks/layout/stage-slider/item', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { StageItemClass } = require('layout/ui/stage-slider/item');
	const { selectById } = require('tasks/statemanager/redux/slices/stage-settings');

	class TaskStageItemClass extends StageItemClass
	{
		isUnsuitable()
		{
			const { isCurrent, stage } = this.props;

			return (!isCurrent && stage?.statusId === 'PERIOD1');
		}
	}

	const mapStateToProps = (state, { stageId }) => ({
		stage: selectById(state, stageId),
	});

	module.exports = {
		TaskStageItem: connect(mapStateToProps)(TaskStageItemClass),
	};
});
