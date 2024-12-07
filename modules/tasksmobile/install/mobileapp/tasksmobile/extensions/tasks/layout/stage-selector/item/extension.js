/**
 * @module tasks/stage-selector/item
 */
jn.define('tasks/stage-selector/item', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { StageItemClass } = require('layout/ui/fields/stage-selector');
	const {
		selectById,
	} = require('tasks/statemanager/redux/slices/stage-settings');

	const mapStateToProps = (state, { stageId }) => ({
		stage: selectById(state, stageId),
	});

	class TasksStageSelectorItemClass extends StageItemClass
	{
		isUnsuitable()
		{
			return (
				!this.props.isCurrent
				&& (this.props.stage.statusId === 'PERIOD1')
			);
		}
	}

	module.exports = {
		TasksStageSelectorItem: connect(mapStateToProps)(TasksStageSelectorItemClass),
	};
});
