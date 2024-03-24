/**
 * @module tasks/layout/task/fields/checklist
 */
jn.define('tasks/layout/task/fields/checklist', (require, exports, module) => {
	const { ChecklistPreview, ChecklistLegacy } = require('tasks/layout/checklist');

	/**
	 * @class FieldChecklist
	 */
	class FieldChecklist extends LayoutComponent
	{
		isNewChecklist()
		{
			return Boolean(jnExtensionData.get('tasks:layout/task/fields/checklist')?.taskNewChecklistActive);
		}

		renderLegacyChecklist()
		{
			return View(
				{
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

		render()
		{
			const { style, ...restProps } = this.props;

			if (this.isNewChecklist())
			{
				return View(
					{
						style,
					},
					new ChecklistPreview(restProps),
				);
			}

			return this.renderLegacyChecklist();
		}
	}

	module.exports = { FieldChecklist };
});
