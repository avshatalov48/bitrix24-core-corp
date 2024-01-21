/**
 * @module tasks/layout/task/fields/project
 */
jn.define('tasks/layout/task/fields/project', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ProjectField } = require('layout/ui/fields/project');
	const { EntitySelectorFactory } = require('selector/widget/factory');

	class Project extends LayoutComponent
	{
		static convertGroupDataFromTaskToField(groupData)
		{
			return {
				id: groupData.id,
				title: groupData.name,
				imageUrl: groupData.image,
			};
		}

		static convertGroupDataFromFieldToTask(groupData)
		{
			return {
				id: groupData.id,
				name: groupData.title,
				image: groupData.imageUrl,
			};
		}

		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				groupId: props.groupId,
				groupData: props.groupData || {},
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				groupId: props.groupId,
				groupData: props.groupData,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				groupId: newState.groupId,
				groupData: newState.groupData,
			});
		}

		handleOnChange(groupId, groupData)
		{
			const resultGroupId = Number(groupId || 0);
			if (resultGroupId !== Number(this.state.groupId))
			{
				const resultGroupData = (
					resultGroupId > 0 ? Project.convertGroupDataFromFieldToTask(groupData[0]) : null
				);

				this.setState({
					groupId: resultGroupId,
					groupData: resultGroupData,
				});
				if (this.props.onChange)
				{
					this.props.onChange(resultGroupId, resultGroupData);
				}
			}
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				ProjectField({
					readOnly: this.state.readOnly,
					showEditIcon: true,
					hasHiddenEmptyView: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_PROJECT'),
					value: this.state.groupId,
					multiple: false,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						selectorType: EntitySelectorFactory.Type.PROJECT,
						entityList: (this.state.groupId > 0 ? [Project.convertGroupDataFromTaskToField(this.state.groupData)] : []),
						provider: {
							context: 'TASKS_PROJECTLINK',
						},
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'project',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { Project };
});
