/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flow-content-chooser
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flow-content-chooser', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { selectById } = require('tasks/statemanager/redux/slices/flows');
	const { PureComponent } = require('layout/pure-component');
	const { FlowContent } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-content');
	const { FlowPromoContent } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-promo-content');
	const { FlowDisabledContent } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-disabled-content');
	const { FlowSimilarContent } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-similar-content');
	const { FlowsInformationCard } = require('tasks/flow-list/simple-list/items/flow-redux/src/flows-information-card');
	const { ListItemType } = require('tasks/flow-list/simple-list/items/type');

	/**
     * @class FlowContentChooser
     */
	class FlowContentChooser extends PureComponent
	{
		render()
		{
			switch (this.props.type)
			{
				case ListItemType.FLOW:
					return new FlowContent(this.props);
				case ListItemType.SIMILAR_FLOW:
					return new FlowSimilarContent(this.props);
				case ListItemType.PROMO_FLOW:
					return new FlowPromoContent(this.props);
				case ListItemType.DISABLED_FLOW:
					return new FlowDisabledContent(this.props);
				case ListItemType.FLOWS_INFO:
					return new FlowsInformationCard(this.props);
				default:
					return View();
			}
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const isLast = ownProps.isLast;
		const type = ownProps.type;
		const flowId = ownProps.id;
		const flow = selectById(state, flowId);

		if (!flow)
		{
			return ownProps;
		}

		const {
			id,
			name,
			description,
			demo,
			active,
			enableFlowUrl,
			plannedCompletionTime,
			plannedCompletionTimeText,
			efficiency,
			efficiencySuccess,
			ownerId,
			creatorId,
			tasksTotal,
			myTasksCounter,
			myTasksTotal,
			averagePendingTime,
			averageAtWorkTime,
			averageCompletedTime,
			pending,
			atWork,
			completed,
			aiAdvice,
		} = flow;

		return {
			flow: {
				id,
				name,
				description,
				demo,
				active,
				enableFlowUrl,
				plannedCompletionTime,
				plannedCompletionTimeText,
				efficiency,
				efficiencySuccess,
				ownerId,
				creatorId,
				tasksTotal,
				myTasksCounter,
				myTasksTotal,
				averagePendingTime,
				averageAtWorkTime,
				averageCompletedTime,
				pending,
				atWork,
				completed,
				isLast,
				type,
				aiAdvice,
			},
		};
	};

	module.exports = {
		FlowContentChooser,
		FlowContentChooserView: connect(mapStateToProps)(FlowContentChooser),
	};
});
