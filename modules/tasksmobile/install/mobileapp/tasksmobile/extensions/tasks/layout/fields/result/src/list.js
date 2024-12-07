/**
 * @module tasks/layout/fields/result/list
 */
jn.define('tasks/layout/fields/result/list', (require, exports, module) => {
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { TaskResultListItem } = require('tasks/layout/fields/result/list-item');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Icon } = require('ui-system/blocks/icon');
	const { Indent } = require('tokens');
	const { Loc } = require('loc');

	const { connect } = require('statemanager/redux/connect');
	const { selectIdsByTaskId } = require('tasks/statemanager/redux/slices/tasks-results');

	class TaskResultListContent extends LayoutComponent
	{
		render()
		{
			const { results, onResultClick, onCreateClick } = this.props;

			return View(
				{
					safeArea: {
						bottom: true,
					},
				},
				UIScrollView({
					style: {
						flex: 1,
					},
					children: results.map((id, index) => {
						return TaskResultListItem({
							id,
							onResultClick,
							showBottomBorder: (index !== results.length - 1),
						});
					}),
					testId: 'TASK_RESULT_LIST',
				}),
				Button({
					style: {
						paddingVertical: Indent.XL.toNumber(),
						paddingHorizontal: Indent.XL4.toNumber(),
					},
					text: Loc.getMessage('TASKS_FIELDS_RESULT_AIR_ADD_RESULT'),
					size: ButtonSize.L,
					design: ButtonDesign.FILLED,
					leftIcon: Icon.PLUS,
					stretched: true,
					testId: 'TASK_RESULT_LIST_CREATE_RESULT',
					onClick: onCreateClick,
				}),
			);
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const { taskId, parentWidget } = ownProps;
		const results = selectIdsByTaskId(state, taskId);

		if (results.length === 0)
		{
			parentWidget?.close();
		}

		return { results };
	};

	module.exports = {
		TaskResultList: connect(mapStateToProps)(TaskResultListContent),
	};
});
