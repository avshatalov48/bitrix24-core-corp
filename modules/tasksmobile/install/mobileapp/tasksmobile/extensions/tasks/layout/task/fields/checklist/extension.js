/**
 * @module tasks/layout/task/fields/checkList
 */
jn.define('tasks/layout/task/fields/checkList', (require, exports, module) => {
	class CheckList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		render()
		{
			const {CheckList} = require('tasks/layout/checklist');

			return View(
				{
					style: (this.props.style || {}),
				},
				new CheckList({
					checkList: this.props.checkList,
					taskId: this.props.taskId,
					taskGuid: this.props.taskGuid,
					userId: this.props.userId,
					diskConfig: this.props.diskConfig,
					parentWidget: this.props.parentWidget,
					isLoading: this.props.isLoading === true,
					onFocus: ref => this.props.onFieldFocus(ref),
					onChange: () => (this.props.onChange && this.props.onChange()),
				}),
			);
		}
	}

	module.exports = {CheckList};
});