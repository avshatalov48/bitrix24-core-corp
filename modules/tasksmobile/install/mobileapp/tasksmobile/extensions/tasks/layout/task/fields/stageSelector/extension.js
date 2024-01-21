/**
 * @module tasks/layout/task/fields/stageSelector
 */
jn.define('tasks/layout/task/fields/stageSelector', (require, exports, module) => {
	const { TasksStageSelector } = require('tasks/layout/stage-selector');
	const AppTheme = require('apptheme');

	/**
	 * @class StageSelector
	 */
	class StageSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				stageId: props.stageId,
				shouldShowField: true,
				projectId: props.projectId,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
			this.onAfterFetchStagesHandler = this.onAfterFetchStages.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				...this.state,
				readOnly: props.readOnly,
				stageId: props.stageId,
				projectId: props.projectId,
			};
		}

		render()
		{
			if (
				!this.state.shouldShowField
				|| !this.state.projectId
				|| this.state.stageId === 0
			)
			{
				return View(
					{
						style: {
							// styles to hide border between fields
							backgroundColor: AppTheme.colors.bgContentPrimary,
							marginTop: -1,
							height: 1,
							flex: 1,
						},
					},
				);
			}

			return View(
				{
					style: {
						style: (this.props.style || {}),
					},
				},
				TasksStageSelector({
					showTitle: false,
					readOnly: this.state.readOnly,
					value: Number(this.state.stageId),
					view: this.props.view,
					projectId: Number(this.state.projectId),
					ownerId: this.props.ownerId,
					searchParams: this.props.searchParams,
					taskId: this.props.taskId,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						parentWidget: this.props.parentWidget,
						useStageChangeMenu: true,
					},
					params: this.props.params,
					testId: 'stageSelector',
					onChange: this.handleOnChange,
					showReadonlyNotification: true,
					hasHiddenEmptyView: true,
					onAfterFetchStages: this.onAfterFetchStagesHandler,
					canSetDefaultStage: true,
					isNewProject: this.state.isNewProject,
				}),
			);
		}

		handleOnChange(stageId)
		{
			return new Promise((resolve) => {
				const { onChange } = this.props;

				if (onChange)
				{
					onChange(stageId);
				}

				this.setState({ stageId }, resolve);
			});
		}

		onAfterFetchStages(stages)
		{
			const shouldShowField = Array.isArray(stages) && stages.length > 0;
			if (!shouldShowField)
			{
				// hide stage selector if it is scrum project
				this.setState({
					...this.state,
					shouldShowField,
				});
			}
		}

		updateState(newState)
		{
			this.isNewProject = this.state.projectId !== newState.projectId;
			this.setState({
				...this.state,
				readOnly: newState.readOnly,
				stageId: newState.stageId,
				projectId: newState.projectId,
				isNewProject: this.isNewProject,
				shouldShowField: true,
			});
		}
	}

	module.exports = { StageSelector };
});
