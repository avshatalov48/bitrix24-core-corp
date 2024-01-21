/**
 * @module tasks/layout/dashboard/toolbar
 */
jn.define('tasks/layout/dashboard/toolbar', (require, exports, module) => {
	const { KanbanToolbar } = require('layout/ui/kanban/toolbar');
	const { StageDropdown } = require('tasks/layout/dashboard/toolbar/src/stage-dropdown');
	const { connect } = require('statemanager/redux/connect');
	const {
		getUniqId,
		selectStages,
	} = require('tasks/statemanager/redux/slices/kanban-settings');
	const { allStagesId } = require('tasks/statemanager/redux/slices/stage-counters');

	class TasksToolbar extends KanbanToolbar
	{
		constructor(props)
		{
			super(props);

			this.state = {
				...this.state,
				loading: false,
				activeStageId: this.state.activeStageId ?? this.props.activeStageId,
			};

			this.onToolbarClick = this.onToolbarClick.bind(this);
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);
			const activeStageExists = props.stages.includes(this.state.activeStageId)
				|| this.state.activeStageId === allStagesId;
			if (!activeStageExists)
			{
				this.state.activeStageId = allStagesId;
				this.onChangeStage(this.state.activeStageId);
			}
		}

		getCurrentView()
		{
			return this.props.filterParams.view;
		}

		openStageSelector(roleInProject)
		{
			void requireLazy('tasks:layout/stage-list-view').then(({ TasksStageListView }) => {
				const props = {
					filterParams: this.props.filterParams,
					kanbanSettingsId: getUniqId(
						this.props.filterParams.view,
						this.props.filterParams.projectId,
						this.props.filterParams.searchParams.ownerId,
					),
					activeStageId: this.getActiveStageId(),
					editable: false,
					readOnly: true,
					canMoveStages: true,
					enableStageSelect: true,
					clickable: false,
					onStageSelect: this.setActiveStage.bind(this),
					isReversed: this.getCurrentView() === 'DEADLINE',
					stageParams: {
						showTotal: false,
						showCount: true,
						showCounters: false,
						showTunnels: false,
						showAllStagesItem: true,
					},
				};

				void TasksStageListView.open(props, this.layout);
			});
		}

		onToolbarClick()
		{
			this.openStageSelector();
		}

		renderStageSelector()
		{
			const styles = this.getStyles();

			return View(
				{
					style: styles.stageSelectorWrapper,
				},
				StageDropdown({
					onClick: this.onToolbarClick,
					activeStageId: this.state.activeStageId,
					filterParams: this.props.filterParams,
					title: this.props.title,
					loading: this.isLoading(),
				}),
			);
		}

		getTestId()
		{
			return 'tasksStageToolbar';
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const id = getUniqId(
			ownProps.filterParams.view,
			ownProps.filterParams.projectId,
			ownProps.filterParams.searchParams.ownerId,
		);
		const stages = selectStages(state, id);

		return {
			id,
			stages,
		};
	};

	module.exports = {
		TasksToolbar: connect(mapStateToProps)(TasksToolbar),
	};
});
