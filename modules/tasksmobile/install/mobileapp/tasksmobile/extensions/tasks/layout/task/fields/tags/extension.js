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
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				tags: (props.tags || []),
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				tags: (newState.tags || []),
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
					value: this.state.tags,
					multiple: true,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						selectorType: EntitySelectorFactory.Type.TASK_TAG,
						enableCreation: true,
						closeAfterCreation: false,
						entityList: this.state.tags.map(tag => ({id: tag, title: tag})),
						provider: {
							context: 'TASKS_TAG',
							options: {
								taskId: this.props.taskId,
							},
						},
						castType: 'string',
						parentWidget: this.props.parentWidget,
						reloadEntityListFromProps: true,
					},
					testId: 'tags',
					onChange: (newTags) => {
						const difference =
							newTags
								.filter(id => !this.state.tags.includes(id))
								.concat(this.state.tags.filter(id => !newTags.includes(id)))
						;
						if (difference.length)
						{
							this.setState({tags: newTags});
							this.props.onChange(newTags);
						}
					},
				}),
			);
		}
	}

	module.exports = {Tags};
});