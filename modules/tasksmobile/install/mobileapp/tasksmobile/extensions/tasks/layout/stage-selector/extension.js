/**
 * @module tasks/layout/stage-selector
 */
jn.define('tasks/layout/stage-selector', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { connect } = require('statemanager/redux/connect');
	const { StageSelectorField } = require('layout/ui/fields/stage-selector');
	const { TasksStageSelectorItem } = require('tasks/stage-selector/item');
	const { isOnline } = require('device/connection');
	const { showOfflineToast, showSafeToast } = require('toast');
	const { Haptics } = require('haptics');
	const {
		fetchStages,
		getUniqId,
		selectStages,
		selectCanMoveStage,
	} = require('tasks/statemanager/redux/slices/kanban-settings');

	/**
	 * @class TasksStageSelector
	 */
	class TasksStageSelector extends StageSelectorField
	{
		get taskId()
		{
			return BX.prop.getInteger(this.props, 'taskId', null);
		}

		get canSetDefaultStage()
		{
			return BX.prop.getBoolean(this.props, 'canSetDefaultStage', false);
		}

		get isNewProject()
		{
			return BX.prop.getBoolean(this.props, 'isNewProject', false);
		}

		get canMoveStage()
		{
			return BX.prop.getBoolean(this.props, 'canMoveStage', false);
		}

		isReadOnly()
		{
			if (!this.canMoveStage)
			{
				return true;
			}

			return super.isReadOnly();
		}

		componentDidMount()
		{
			this.fetchStages(this.props)
				.then((stages) => {
					this.onAfterFetchStages(stages);
				})
				.catch(console.error);

			super.componentDidMount();
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);
			if (this.props.projectId !== props.projectId)
			{
				this.fetchStages(props)
					.then((stages) => {
						this.onAfterFetchStages(stages);
					})
					.catch(console.error);
			}
		}

		render()
		{
			if (this.isNewProject && !this.isActiveStageExist())
			{
				this.setDefaultStage();
			}

			return super.render();
		}

		setDefaultStage()
		{
			if (this.canSetDefaultStage)
			{
				this.prevActiveStageId = null;
				this.state.activeStageId = this.getDefaultStage();
			}
		}

		getDefaultStage()
		{
			if (Array.isArray(this.processStages) && this.processStages.length > 0)
			{
				return this.processStages[0];
			}

			return null;
		}

		fetchStages(props)
		{
			const { processStages = [] } = props.stageIdsBySemantics || {};

			if (props.view && Number.isInteger(props.projectId) && props.ownerId && processStages.length === 0)
			{
				return this.props.fetchStages({
					view: props.view,
					projectId: props.projectId,
					searchParams: {
						ownerId: props.ownerId,
						counterId: 'none',
						searchString: '',
						presetId: 'filter_tasks_in_progress',
					},
					taskId: this.taskId,
				})
					.then((response) => {
						return response.payload?.data?.stages.map(({ id }) => id) || [];
					})
					.catch((error) => console.error(error));
			}

			return Promise.resolve(processStages);
		}

		onAfterFetchStages(stages)
		{
			if (this.props.onAfterFetchStages)
			{
				this.props.onAfterFetchStages(stages);
			}
		}

		isActiveStageExist()
		{
			if (this.state.activeStageId === 0)
			{
				return true;
			}

			return super.isActiveStageExist();
		}

		onChangeStage(stage)
		{
			if (this.isReadOnly())
			{
				return;
			}

			Keyboard.dismiss();

			if (stage.id === this.state.activeStageId)
			{
				void this.openStageList(stage.id, stage.entityType);
			}
			else
			{
				this.changeActiveStageId(stage.id, stage.statusId);
			}
		}

		async openStageList(activeStageId, entityType)
		{
			void requireLazy('tasks:layout/stage-list-view').then(({ TasksStageListView }) => {
				const props = {
					filterParams: {
						view: this.props.view,
						projectId: this.props.projectId,
						searchParams: {
							ownerId: this.props.ownerId,
						},
					},
					kanbanSettingsId: getUniqId(
						this.props.view,
						this.props.projectId,
						this.props.ownerId,
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

		renderStages(currentStages, activeIndex)
		{
			return currentStages.map((stageId, index) => TasksStageSelectorItem({
				isReversed: this.isReversed,
				stageId,
				index,
				activeIndex,
				showMenu: !this.isReadOnly() && activeIndex === index,
				onStageClick: this.onStageClickHandler,
				onStageLongClick: this.onStageLongClickHandler,
				onChange: this.onChangeStageHandler,
				isCurrent: activeIndex === index,
			}));
		}

		notifyAboutReadOnlyStatus()
		{
			if (this.isReadonlyNotificationEnabled())
			{
				const { parentWidget, readonlyNotificationMessage } = this.getConfig();

				const message = readonlyNotificationMessage
					|| Loc.getMessage('TASKS_STAGE_SELECTOR_NOTIFY_READONLY_TEXT');

				Haptics.notifyWarning();
				showSafeToast({ message }, parentWidget);
			}
		}

		onBeforeHandleChange(actionParams)
		{
			if (!isOnline())
			{
				showOfflineToast();

				return Promise.reject();
			}

			if (
				actionParams.selectedStatusId === 'PERIOD1'
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
				Alert.confirm(
					Loc.getMessage('TASKS_STAGE_SELECTOR_NOTIFY_SPRINT_FINISH_TITLE'),
					Loc.getMessage('TASKS_STAGE_SELECTOR_NOTIFY_SPRINT_FINISH_TEXT'),
				);

				return Promise.reject();
			}

			return Promise.resolve();
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const kanbanSettingsId = getUniqId(ownProps.view, ownProps.projectId, ownProps.ownerId);

		return {
			kanbanSettingsId,
			stageIdsBySemantics: {
				processStages: selectStages(state, kanbanSettingsId),
			},
			canMoveStage: selectCanMoveStage(state, kanbanSettingsId),
		};
	};

	const mapDispatchToProps = ({
		fetchStages,
	});

	module.exports = {
		TasksStageSelectorType: 'task-stage',
		TasksStageSelector: connect(mapStateToProps, mapDispatchToProps)(TasksStageSelector),
	};
});
