/**
 * @module tasks/layout/fields/stage-selector
 */
jn.define('tasks/layout/fields/stage-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { isOnline } = require('device/connection');
	const { showOfflineToast } = require('toast');
	const { ActionId, ActionMeta } = require('tasks/layout/action-menu/actions');
	const { StageSelectorV2Field } = require('layout/ui/fields/stage-selector-v2');
	const { DeadlinePeriod } = require('tasks/enum');
	const { TasksStageSlider } = require('tasks/layout/stage-slider');

	const {
		getUniqId,
	} = require('tasks/statemanager/redux/slices/kanban-settings');

	/**
	 * @class TasksStageSelector
	 * @extends StageSelectorV2Field
	 */
	class TasksStageSelector extends StageSelectorV2Field
	{
		get view()
		{
			return BX.prop.getString(this.props, 'view', null);
		}

		get projectId()
		{
			return BX.prop.getInteger(this.props, 'projectId', null);
		}

		get userId()
		{
			return BX.prop.getInteger(this.props, 'userId', null);
		}

		get taskId()
		{
			return BX.prop.getInteger(this.props, 'taskId', null);
		}

		get initiallyHidden()
		{
			return BX.prop.getBoolean(this.getConfig(), 'initiallyHidden', false);
		}

		renderEditableContent()
		{
			return TasksStageSlider({
				forwardedRef: this.bindForwardedRef,
				activeStageId: this.getValue(),
				isReadOnly: this.isReadOnly(),

				isReversed: this.isReversed,

				onStageClick: this.onStageClick,
				onStageLongClick: this.onStageLongClick,
				showLoadingAnimation: this.state.showLoadingAnimation,

				view: this.view,
				projectId: this.projectId,
				userId: this.userId,
				taskId: this.taskId,
			});
		}

		/**
		 * @param activeStageId
		 * @param entityType
		 * @return {Promise<void>}
		 */
		async openStageList(activeStageId, entityType)
		{
			void requireLazy('tasks:layout/stage-list-view').then(({ TasksStageListView }) => {
				const props = {
					filterParams: {
						view: this.view,
						projectId: this.projectId,
						searchParams: {
							ownerId: this.userId,
						},
					},
					kanbanSettingsId: getUniqId(
						this.view,
						this.projectId,
						this.userId,
					),
					entityType,
					activeStageId,
					readOnly: true,
					canMoveStages: true,
					enableStageSelect: true,
					clickable: false,
					onStageSelect: (id, statusId) => this.changeActiveStageId(id, statusId),
					isReversed: this.isReversed,
				};

				void TasksStageListView.open(props, this.getParentWidget());
			});
		}

		onBeforeHandleChange(actionParams)
		{
			if (!isOnline())
			{
				showOfflineToast();

				return Promise.reject();
			}

			if (
				actionParams.selectedStatusId === DeadlinePeriod.PERIOD_OVERDUE
			)
			{
				Alert.confirm(
					Loc.getMessage('TASKS_STAGE_SELECTOR_UNAVAILABLE_STAGE_TITLE'),
					Loc.getMessage('TASKS_STAGE_SELECTOR_UNAVAILABLE_STAGE_TEXT'),
				);

				return Promise.reject();
			}

			if (actionParams.selectedStatusId === 'FINISH')
			{
				const completeActionMeta = ActionMeta[ActionId.COMPLETE];

				return completeActionMeta.handleAction({ taskId: this.taskId });
			}

			return Promise.resolve();
		}
	}

	module.exports = {
		TasksStageSelector: (props) => new TasksStageSelector(props),
	};
});
