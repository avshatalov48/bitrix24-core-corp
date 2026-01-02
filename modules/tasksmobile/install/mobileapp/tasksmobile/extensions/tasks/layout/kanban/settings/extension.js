/**
 * @module tasks/layout/kanban/settings
 */
jn.define('tasks/layout/kanban/settings', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { NotifyManager } = require('notify-manager');
	const { Indent, Corner } = require('tokens');

	const {
		KanbanSettings,
		Stage,
		SEMANTICS,
	} = require('layout/ui/kanban/settings');
	const { TasksStageList } = require('tasks/layout/stage-list');

	const { connect } = require('statemanager/redux/connect');
	const {
		selectStatus,
		selectStages,
		getUniqId,
		updateStagesOrder,
	} = require('tasks/statemanager/redux/slices/kanban-settings');
	const { addStage } = require('tasks/statemanager/redux/slices/stage-settings');

	/**
		* @class TasksKanbanSettingsEditor
		*/
	class TasksKanbanSettingsEditor extends KanbanSettings
	{
		constructor(props)
		{
			super(props);
			this.onStageMove = this.onStageMove.bind(this);
		}

		get isDefault()
		{
			return BX.prop.getBoolean(this.props, 'isDefault', true);
		}

		getTitleForNavigation(title)
		{
			return BX.message(`TASKS_TITLE_FOR_NAVIGATION_${this.props.filterParams.view}`);
		}

		getStageListTitle()
		{
			return BX.message('TASKS_STAGES_LIST_TITLE');
		}

		renderStageList()
		{
			return new TasksStageList({
				title: this.getStageListTitle(),
				readOnly: false,
				canMoveStages: true,
				onStageMove: this.onStageMove,
				stageIdsBySemantics: this.changedFields.stageIdsBySemantics || this.props.stageIdsBySemantics,
				onOpenStageDetail: this.onOpenStageDetail,
				kanbanSettingsId: this.kanbanSettingsId,
				filterParams: this.props.filterParams,
			});
		}

		onStageMove(processStages)
		{
			this.changedFields = {
				...this.changedFields,
				stageIdsBySemantics: {
					...this.stageIdsBySemantics,
					processStages,
				},
			};
			this.setState({});
		}

		renderContent()
		{
			return View(
				{
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderStageList(),
				this.renderStageButtons(),
			);
		}

		async openStageDetail({ id })
		{
			const { TasksKanbanStageSettings } = await requireLazy('tasks:layout/kanban/stage-settings');

			await TasksKanbanStageSettings.open(
				{
					view: this.props.filterParams.view,
					projectId: this.props.filterParams.projectId,
					ownerId: this.props.filterParams.searchParams.ownerId,
					stageId: id,
					kanbanSettingsId: this.kanbanSettingsId,
				},
				this.layout,
			);
		}

		save()
		{
			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();
				if (this.props.updateStagesOrder)
				{
					this.props.updateStagesOrder(
						{
							view: this.props.filterParams.view,
							projectId: this.props.filterParams.projectId,
							ownerId: this.props.filterParams.searchParams.ownerId,
							fields: {
								id: this.kanbanSettingsId,
								...this.changedFields,
							},
						},
					)
						.then(() => {
							NotifyManager.hideLoadingIndicator(true);
							resolve();
						})
						.catch((errors) => {
							NotifyManager.showErrors(errors);
							reject(errors);
						});
				}
			});
		}

		hasChangedFields()
		{
			return this.changedFields.stageIdsBySemantics
								&& !isEqual(
									this.changedFields.stageIdsBySemantics.processStages,
									this.stageIdsBySemantics.processStages,
								);
		}

		createStage(semantics, semanticsType)
		{
			if (this.props.addStage)
			{
				const stage = new Stage({ semantics });

				const {
					name,
					color,
				} = stage;
				const preparedColor = color.replace('#', '');
				const afterId = this.stageIdsBySemantics.processStages.length > 0
					? this.stageIdsBySemantics.processStages[this.stageIdsBySemantics.processStages.length - 1]
					: null;

				return new Promise((resolve, reject) => {
					this.props.addStage(
						{
							filterParams: this.props.filterParams,
							name,
							color: preparedColor,
							afterId,
						},
					)
						.then((createResult) => {
							NotifyManager.hideLoadingIndicator(
								true,
								BX.message('CATEGORY_DETAIL_SUCCESS_CREATION'),
								1000,
							);
							setTimeout(() => resolve(createResult.payload), 1300);
						})
						.catch((response) => {
							NotifyManager.showErrors(response.errors);
							reject();
						});
				});
			}

			return Promise.reject();
		}

		renderStageButtons()
		{
			return View(
				{
					style: {
						borderRadius: Corner.L.toNumber(),
						marginBottom: Indent.M.toNumber(),
					},
				},
				this.renderButton({
					text: BX.message('CATEGORY_DETAIL_CREATE_PROCESS_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.PROCESS, 'processStages');
					},
				}),
			);
		}

		updateLayoutTitle()
		{
			this.layout.setTitle({
				text: this.getTitleForNavigation(),
				type: 'dialog',
			});
		}

		static open(params, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', this.getWidgetParams(params))
					.then((layout) => {
						layout.enableNavigationBarBorder(false);
						layout.showComponent(connect(mapStateToProps, mapDispatchToProps)(this)({ layout, ...params }));
						resolve(layout);
					})
					.catch(reject);
			});
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const stageIdsBySemantics = {
			processStages: selectStages(
				state,
				getUniqId(
					ownProps.filterParams.view,
					ownProps.filterParams.projectId,
					ownProps.filterParams.searchParams.ownerId,
				),
			),
		};

		return {
			stageIdsBySemantics,
			originalKanbanSettingsId: ownProps.kanbanSettingsId,
			status: selectStatus(state),
		};
	};

	const mapDispatchToProps = ({
		updateStagesOrder,
		addStage,
	});

	module.exports = {
		TasksKanbanSettingsEditor,
	};
});
