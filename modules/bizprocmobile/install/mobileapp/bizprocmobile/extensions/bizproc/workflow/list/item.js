/**
 * @module bizproc/workflow/list/item
 */
jn.define('bizproc/workflow/list/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { showToast, Position: ToastPosition } = require('toast');

	const { WorkflowSimpleListItem } = require('bizproc/workflow/list/simple-list/item');

	/**
	 * @class WorkflowItem
	 */
	class WorkflowItem extends WorkflowSimpleListItem
	{
		onCheckboxClicked()
		{
			super.onCheckboxClicked();

			if (!this.task)
			{
				showToast(
					{
						message: Loc.getMessage('BPMOBILE_WORKFLOW_LIST_TASK_UNSELECTABLE'),
						position: ToastPosition.TOP,
						time: 2,
					},
					this.layout,
				);
			}
		}
	}

	module.exports = { WorkflowItem };
});
