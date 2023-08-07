/**
 * @module tasks/layout/task/fields/checkList
 */
jn.define('tasks/layout/task/fields/checkList', (require, exports, module) => {
	class CheckList extends LayoutComponent
	{
		// eslint-disable-next-line no-useless-constructor
		constructor(props)
		{
			super(props);
		}

		render()
		{
			const { CheckList: CheckListInnerComponent } = require('tasks/layout/checklist');

			return View(
				{
					style: (this.props.style || {}),
				},
				new CheckListInnerComponent({
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

	module.exports = { CheckList };
});
