/**
 * @module tasks/layout/task/fields/checklist
 */
jn.define('tasks/layout/task/fields/checklist', (require, exports, module) => {
	const { ChecklistController } = require('tasks/checklist');
	const { ChecklistPreview, ChecklistLegacy } = require('tasks/layout/checklist');

	/**
	 * @class FieldChecklist
	 */
	class FieldChecklist extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initChecklistField(props);
			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.initChecklistField(props);
		}

		initChecklistField(props)
		{
			this.controller = new ChecklistController({ ...props, onChange: this.handleOnChange });

			this.state = {
				checklistsIds: this.controller.getChecklistsIds(),
			};
		}

		isNewChecklist()
		{
			return Boolean(Application.storage.getObject('settings.task')?.taskNewChecklistActive);
		}

		handleOnChange(checklistsIds)
		{
			this.setState({ checklistsIds });
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
			const { style, isLoading } = this.props;

			if (this.isNewChecklist())
			{
				return View(
					{
						style: {
							width: '100%',
							...style,
						},
					},
					new ChecklistPreview({
						isLoading,
						checklistController: this.controller,
					}),
				);
			}

			return this.renderLegacyChecklist();
		}
	}

	module.exports = { FieldChecklist };
});
