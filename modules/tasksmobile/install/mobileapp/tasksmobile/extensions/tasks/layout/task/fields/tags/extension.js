/**
 * @module tasks/layout/task/fields/tags
 */
jn.define('tasks/layout/task/fields/tags', (require, exports, module) => {
	const {Loc} = require('loc');
	const {TagField} = require('layout/ui/fields/tag');

	class Tags extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				tags: (props.tags || []),
				groupId: props.groupId,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				tags: (props.tags || []),
				groupId: props.groupId,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				tags: (newState.tags || []),
				groupId: newState.groupId,
			});
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				TagField({
					readOnly: this.state.readOnly,
					showEditIcon: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TAGS'),
					value: Object.keys(this.state.tags),
					multiple: true,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						selectorType: EntitySelectorFactory.Type.TASK_TAG,
						enableCreation: true,
						closeAfterCreation: false,
						canUseRecent: false,
						entityList: Object.values(this.state.tags),
						provider: {
							context: 'TASKS_TAG',
							options: {
								taskId: this.props.taskId,
								groupId: this.state.groupId,
							},
						},
						parentWidget: this.props.parentWidget,
						reloadEntityListFromProps: true,
					},
					testId: 'tags',
					onChange: (tagsIds, tagsData) => {
						const tags = tagsData.reduce((result, tag) => {
							result[tag.id] = {
								id: tag.id,
								title: tag.title,
							};
							return result;
						}, {});
						const newTags = Object.keys(tags);
						const oldTags = Object.keys(this.state.tags);
						const difference =
							newTags
								.filter(id => !oldTags.includes(id))
								.concat(oldTags.filter(id => !newTags.includes(id)))
						;
						if (difference.length)
						{
							this.setState({tags});
							this.props.onChange(tags);
						}
					},
				}),
			);
		}
	}

	module.exports = {Tags};
});