/**
 * @module tasks/layout/task/fields/tasks
 */
jn.define('tasks/layout/task/fields/tasks', (require, exports, module) => {
	const {EntitySelectorFieldClass} = require('layout/ui/fields/entity-selector');

	class TaskField extends EntitySelectorFieldClass
	{
		constructor(props)
		{
			super(props);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorType: (config.selectorType === '' ? 'task' : config.selectorType),
				canOpenEntity: BX.prop.getBoolean(config, 'canOpenEntity', true),
			};
		}

		renderEntity(task = {}, showPadding = false)
		{
			return View(
				{
					style: {
						paddingBottom: (showPadding ? 8 : undefined),
					},
					onClick: (this.isReadOnly() && this.canOpenEntity() && this.openEntity.bind(this, task.id, task.title)),
				},
				Text({
					style: this.styles.taskText,
					text: task.title,
				}),
			);
		}

		canOpenEntity()
		{
			return this.getConfig().canOpenEntity;
		}

		openEntity(taskId, taskTitle)
		{
			const task = new Task({id: env.userId});
			task.updateData({
				id: taskId,
				title: taskTitle,
			});
			task.canSendMyselfOnOpen = false;
			task.open(this.getConfig().parentWidget);
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', false);
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				entityContent: {
					...styles.entityContent,
					flexDirection: 'column',
				},
				taskText: {
					color: (this.canOpenEntity() ? '#0b66c3' : '#333333'),
					fontSize: 16,
				},
				emptyEntity: {
					...styles.emptyValue,
				},
				wrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
				readOnlyWrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
			};
		}
	}

	module.exports = {
		TaskField: props => new TaskField(props),
	};
});