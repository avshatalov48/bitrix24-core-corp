/**
 * @module tasks/layout/task/fields/creator
 */
jn.define('tasks/layout/task/fields/creator', (require, exports, module) => {
	const {Loc} = require('loc');
	const {Type} = require('type');
	const {UserField} = require('layout/ui/fields/user');

	class Creator extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				creator: props.creator,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				creator: props.creator,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				creator: newState.creator,
			});
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				UserField({
					readOnly: this.state.readOnly,
					showEditIcon: !this.state.readOnly,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_CREATOR'),
					multiple: false,
					value: this.state.creator.id,
					titlePosition: 'left',
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						provider: {
							context: 'TASKS_MEMBER_SELECTOR_EDIT_originator',
						},
						entityList: [
							{
								id: this.state.creator.id,
								title: this.state.creator.name,
								imageUrl: (
									!Type.isString(this.state.creator.icon)
									|| !Type.isStringFilled(this.state.creator.icon)
									|| this.state.creator.icon.indexOf('default_avatar.png') >= 0
										? null
										: this.state.creator.icon
								),
								customData: {
									position: this.state.creator.workPosition,
								},
							},
						],
						canUnselectLast: false,
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'creator',
					onChange: (creatorId, creatorData) => {
						if (Number(creatorId) !== Number(this.state.creator.id))
						{
							const creator = {
								id: creatorId,
								name: creatorData[0].title,
								icon: creatorData[0].imageUrl,
								workPosition: creatorData[0].customData.position,
							};
							this.setState({creator});
							this.props.onChange(creator);
						}
					},
				}),
			);
		}
	}

	module.exports = {Creator};
});