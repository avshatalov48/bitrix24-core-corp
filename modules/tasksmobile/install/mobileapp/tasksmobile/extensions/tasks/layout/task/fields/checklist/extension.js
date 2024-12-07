/**
 * @module tasks/layout/task/fields/checklist
 */
jn.define('tasks/layout/task/fields/checklist', (require, exports, module) => {
	const { ChecklistLegacy } = require('tasks/layout/checklist');

	/**
	 * @class FieldChecklist
	 */
	class FieldChecklist extends LayoutComponent
	{
		render()
		{
			return View(
				{
					testId: 'checklists',
					style: (this.props.style || {}),
				},
				new ChecklistLegacy({
					checkList: this.props.checkList,
					taskId: this.props.taskId,
					taskGuid: this.props.taskGuid,
					userId: this.props.userId,
					diskConfig: this.props.diskConfig,
					parentWidget: this.props.parentWidget,
					isLoading: this.props.isLoading === true,
					onFocus: (ref) => this.props.onFieldFocus(ref),
					onChange: () => (this.props.onChange && this.props.onChange()),
				}),
			);
		}
	}

	module.exports = { FieldChecklist };
});
