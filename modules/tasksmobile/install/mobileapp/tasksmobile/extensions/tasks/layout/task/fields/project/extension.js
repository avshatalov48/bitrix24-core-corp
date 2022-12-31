/**
 * @module tasks/layout/task/fields/project
 */
jn.define('tasks/layout/task/fields/project', (require, exports, module) => {
	const {Loc} = require('loc');
	const {ProjectField} = require('layout/ui/fields/project');

	class Project extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				groupId: props.groupId,
				groupData: props.groupData,
			};
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

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				ProjectField({
					readOnly: this.state.readOnly,
					showEditIcon: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_PROJECT'),
					value: this.state.groupId,
					multiple: false,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						selectorType: EntitySelectorFactory.Type.PROJECT,
						entityList: (this.state.groupId > 0 ? [this.convertGroupDataFromTaskToField(this.state.groupData)] : []),
						provider: {
							context: 'TASKS_PROJECTLINK',
						},
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'project',
					onChange: (groupId, groupData) => {
						groupId = (groupId || 0);
						if (Number(groupId) !== Number(this.state.groupId))
						{
							groupData = (groupId > 0 ? this.convertGroupDataFromFieldToTask(groupData[0]) : null);
							this.setState({groupId, groupData});
							this.props.onChange(groupId, groupData);
						}
					},
				}),
			);
		}

		convertGroupDataFromTaskToField(groupData)
		{
			return {
				id: groupData.id,
				title: groupData.name,
				imageUrl: groupData.image,
			};
		}

		convertGroupDataFromFieldToTask(groupData)
		{
			return {
				id: groupData.id,
				name: groupData.title,
				image: groupData.imageUrl,
			};
		}
	}

	module.exports = {Project};
});