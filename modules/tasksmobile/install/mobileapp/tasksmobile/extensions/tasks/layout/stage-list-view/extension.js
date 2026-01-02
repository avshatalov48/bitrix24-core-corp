/**
 * @module tasks/layout/stage-list-view
 */
jn.define('tasks/layout/stage-list-view', (require, exports, module) => {
	const { StageListView, StageSelectActions } = require('layout/ui/stage-list-view');
	const { TasksStageList } = require('tasks/layout/stage-list');

	const { connect } = require('statemanager/redux/connect');
	const {
		selectStages,
		selectCanEdit,
	} = require('tasks/statemanager/redux/slices/kanban-settings');
	const {
		allStagesId,
	} = require('tasks/statemanager/redux/slices/stage-counters');

	/**
	 * @class TasksStageListView
	 */
	class TasksStageListView extends StageListView
	{
		static open(params, parentWidget)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', this.getWidgetParams(params))
					.then((layout) => {
						layout.enableNavigationBarBorder(false);
						layout.showComponent(connect(mapStateToProps)(this)({ layout, ...params }));
						resolve(layout);
					})
					.catch((error) => {
						console.error(error);
					});
			});
		}

		componentWillReceiveProps(props)
		{
			if (this.props.editable !== props.editable)
			{
				this.setEditable(props.editable);
			}
		}

		setEditable(enable)
		{
			if (enable)
			{
				this.layout.setRightButtons([
					{
						type: 'edit',
						callback: () => this.handlerCategoryEditOpen(),
					},
				]);
			}
			else
			{
				this.layout.setRightButtons([]);
			}
		}

		getTitleForNavigation(title)
		{
			if (this.props.entityType === 'A')
			{
				return BX.message('TASKS_STAGE_LIST_VIEW_TITLE_FOR_NAVIGATION_SPRINT');
			}

			return BX.message(`TASKS_STAGE_LIST_VIEW_TITLE_FOR_NAVIGATION_${this.props.filterParams.view}`);
		}

		renderStageList()
		{
			const {
				stageParams,
				canMoveStages,
				filterParams,
				stageIdsBySemantics,
			} = this.props;

			let activeStageId = this.props.activeStageId;
			const activeStageExists = stageIdsBySemantics.processStages.includes(activeStageId)
				|| activeStageId === allStagesId;
			if (!activeStageExists)
			{
				activeStageId = allStagesId;
			}

			return new TasksStageList({
				title: this.getStageListTitle(),
				readOnly: this.getStageReadOnly(),
				stageIdsBySemantics: this.stageIdsBySemantics,
				canMoveStages,
				stageParams,
				activeStageId,
				onSelectedStage: this.onSelectedStageHandler,
				onOpenStageDetail: this.onOpenStageDetailHandler,
				enableStageSelect: this.enableStageSelect,
				kanbanSettingsId: this.kanbanSettingsId,
				filterParams,
				isReversed: this.isReversed,
				shouldShowStageListTitle: false,
			});
		}

		getStageReadOnly()
		{
			return this.props.readOnly;
		}

		async handlerCategoryEditOpen()
		{
			const { TasksKanbanSettingsEditor } = await requireLazy('tasks:layout/kanban/settings');

			TasksKanbanSettingsEditor.open(
				{
					filterParams: this.props.filterParams,
					kanbanSettingsId: this.kanbanSettingsId,
				},
				this.layout,
			);
		}

		onSelectedStage(stage)
		{
			if (this.selectAction === StageSelectActions.ChangeEntityStage)
			{
				this.onChangeEntityStage(stage.id, stage.statusId);
			}
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const canEdit = selectCanEdit(state, ownProps.kanbanSettingsId) || false;

		return {
			editable: canEdit,
			stageIdsBySemantics: {
				processStages: selectStages(state, ownProps.kanbanSettingsId),
			},
		};
	};

	module.exports = {
		TasksStageListView,
	};
});
