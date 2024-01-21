/**
 * @module lists/process/simple-list/items/personal-process
 */
jn.define('lists/process/simple-list/items/personal-process', (require, exports, module) => {
	const { Extended } = require('layout/ui/simple-list/items/extended');
	// const { WorkflowFaces } = require('bizproc/workflow/faces');

	/**
	 * @class PersonalProcess
	 * */
	class PersonalProcess extends Extended
	{
		get processStyles()
		{
			return {
				wrapper: {
					flexDirection: 'column',
					marginRight: 56,
					marginBottom: 4,
					marginLeft: 24,
				},
			};
		}

		prepareActions(actions) {}

		renderBody()
		{
			return View(
				{ style: this.processStyles.wrapper },
				this.renderStatus(),
				// this.renderWorkflowFaces(),
			);
		}

		renderStatus()
		{
			return Text({
				text: this.props.item.data.process.workflowState || '',
			});
		}

		renderWorkflowFaces()
		{
			return new WorkflowFaces({
				layout,
				workflowStateId: this.props.item.data.process.workflowStateId || 0,
			});
		}
	}

	module.exports = { PersonalProcess };
});
