/**
 * @module tasks/layout/task/fields/task
 */
jn.define('tasks/layout/task/fields/task', (require, exports, module) => {
	const { Color } = require('tokens');
	const { UIMenu } = require('layout/ui/menu');
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { Icon } = require('assets/icons');
	const { Entry } = require('tasks/entry');

	class TaskField extends EntitySelectorFieldClass
	{
		constructor(props)
		{
			super(props);
			this.state.showAll = false;
			this.menu = new UIMenu(this.getUIMenuItems());
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorType: (config.selectorType === '' ? this.getDefaultSelectorType() : config.selectorType),
				canOpenEntity: BX.prop.getBoolean(config, 'canOpenEntity', true),
			};
		}

		getDefaultSelectorType()
		{
			return 'task';
		}

		getCurrentTaskId()
		{
			return BX.prop.getInteger(this.getConfig(), 'currentTaskId', null);
		}

		getUserid()
		{
			return BX.prop.getInteger(this.getConfig(), 'userId', null);
		}

		getGroupId()
		{
			return BX.prop.getInteger(this.getConfig(), 'groupId', null);
		}

		getGroup()
		{
			return BX.prop.getObject(this.getConfig(), 'group', null);
		}

		getAnalyticsLabel()
		{
			return this.props.analyticsLabel ?? {};
		}

		getPreparedGroupData()
		{
			const group = this.getGroup();
			if (group)
			{
				return {
					id: group.id,
					name: group.title,
					image: group.imageUrl,
					additionalData: {},
				};
			}

			return null;
		}

		renderEntity(task = {}, showPadding = false)
		{
			return View(
				{
					style: {
						paddingBottom: (showPadding ? 8 : undefined),
					},
					onClick: (this.isReadOnly() && this.canOpenEntity() && this.openEntity.bind(
						this,
						task.id,
						task.title,
					)),
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
			Entry.openTask({ taskId }, { parentWidget: this.getConfig().parentWidget });
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
					color: this.canOpenEntity()
						? Color.accentMainLinks.toHex()
						: Color.base1.toHex(),
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

		getDisplayedValue()
		{
			if (this.isEmpty() || (this.isMultiple() && this.state.entityList.length > 1))
			{
				return this.getTitleText();
			}

			return this.state.entityList[0].title;
		}

		getDefaultLeftIcon()
		{
			return Icon.TASK;
		}

		getUIMenuItems()
		{
			return [
				{
					id: 'selectSubtask',
					testId: 'selectSubtask',
					title: BX.message('TASKS_FIELDS_TASK_MENU_SELECT_BUTTON_TEXT'),
					iconName: Icon.TASK_LIST,
					onItemSelected: () => {
						super.openSelector();
					},
				},
				{
					id: 'createSubtask',
					testId: 'createSubtask',
					title: BX.message('TASKS_FIELDS_TASK_MENU_CREATE_BUTTON_TEXT'),
					iconName: Icon.PLUS,
					onItemSelected: () => {
						this.openTaskCreateForm();
					},
				},
			];
		}

		openSelector(forceSelectorType = false, target = null)
		{
			this.removeFocus()
				.then(() => {
					this.menu.show({ target: this.getMenuTarget(target) });
				})
				.catch((error) => {
					this.logger.error('Error on remove focus', error);
				});
		}

		/**
		 * @private
		 * @param {object} target
		 * @return {object}
		 */
		getMenuTarget(target)
		{
			return target || this.fieldContainerRef;
		}

		openTaskCreateForm()
		{
			throw new Error('Method not implemented yet');
		}
	}

	module.exports = {
		TaskFieldClass: TaskField,
		TaskField: (props) => new TaskField(props),
	};
});
