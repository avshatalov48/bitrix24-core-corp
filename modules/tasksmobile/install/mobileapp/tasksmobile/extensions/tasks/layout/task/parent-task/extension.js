/**
 * @module tasks/layout/task/parent-task
 */
jn.define('tasks/layout/task/parent-task', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { Entry } = require('tasks/entry');
	const { connect } = require('statemanager/redux/connect');
	const { selectByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks');

	const { IconView } = require('ui-system/blocks/icon');
	const { Text5 } = require('ui-system/typography/text');

	/**
	 * @param {number} id
	 * @param {string} name
	 * @param {string} testId
	 * @param {object} parentWidget
	 * @param {boolean} canRead
	 * @param {boolean} enableToOpenTask
	 * @return {object|null}
	 * @constructor
	 */
	const ParentTask = ({
		id,
		name,
		testId,
		parentWidget,
		canRead = false,
		enableToOpenTask = true,
	}) => {
		if (!id || !canRead)
		{
			return View();
		}

		return View(
			{
				testId: `${testId}_Container`,
				style: {
					backgroundColor: Color.bgContentPrimary.toHex(),

					// We should use bgNavigation for old color theme only:
					// backgroundColor: AppTheme.colors.bgNavigation,

					flexDirection: 'row',
					paddingTop: Number(Indent.L),
					paddingLeft: Number(Indent.XL3),
					paddingRight: Number(Indent.XL3) + 24,
				},
				onClick: enableToOpenTask
					? () => Entry.openTask({ id }, { parentWidget })
					: null,
			},
			IconView({
				icon: 'relatedTasks',
				size: 20,
				color: Color.accentMainPrimaryalt,
			}),
			Text5({
				testId: `${testId}_Value`,
				text: name || Loc.getMessage('M_TASK_DETAILS_PARENT_TASK_DEFAULT_TITLE'),
				style: {
					marginLeft: Indent.XS.toNumber(),
					color: enableToOpenTask ? Color.accentMainPrimaryalt.toHex() : Color.base1.toHex(),
					flexShrink: 2,
				},
				ellipsize: 'end',
				numberOfLines: 1,
			}),
		);
	};

	const mapStateToProps = (state, { taskId }) => {
		const task = selectByTaskIdOrGuid(state, taskId);

		return {
			id: taskId,
			name: task?.name,
			canRead: task?.canRead,
		};
	};

	module.exports = {
		ParentTask: connect(mapStateToProps)(ParentTask),
	};
});
