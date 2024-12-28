/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flow-similar-content
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flow-similar-content', (require, exports, module) => {
	const { FlowContent } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-content');

	class FlowSimilarContent extends FlowContent
	{
		get shouldShowAiAdviceFooter()
		{
			return false;
		}

		get testId()
		{
			return `flow-similar-content-${this.props.id}`;
		}

		openFlowTasksListButtonClickHandler = () => {};

		cardClickHandler = () => {};
	}

	module.exports = {
		FlowSimilarContent,
	};
});
