/**
 * @module tasks/layout/task/fields/creator
 */
jn.define('tasks/layout/task/fields/creator', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { UserField } = require('layout/ui/fields/user');
	const { AnalyticsEvent } = require('analytics');

	class Creator extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				creator: props.creator,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
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

		handleOnChange(creatorId, creatorData)
		{
			if (Number(creatorId) !== Number(this.state.creator.id))
			{
				const creator = {
					id: creatorId,
					name: creatorData[0].title,
					icon: creatorData[0].imageUrl,
					workPosition: creatorData[0].customData.position,
				};
				this.setState({ creator });
				const { onChange } = this.props;

				if (onChange)
				{
					onChange(creator);
				}
			}
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				UserField({
					analytics: new AnalyticsEvent().setSection('tasks'),
					readOnly: this.state.readOnly,
					showEditIcon: !this.state.readOnly,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_CREATOR'),
					multiple: false,
					value: this.state.creator.id,
					titlePosition: 'left',
					config: {
						enableCreation: !(env.isCollaber || env.extranet),
						deepMergeStyles: this.props.deepMergeStyles,
						useLettersForEmptyAvatar: true,
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
									|| this.state.creator.icon.includes('default_avatar.png')
										? null
										: this.state.creator.icon
								),
								customData: {
									position: this.state.creator.workPosition,
								},
							},
						],
						selectorTitle: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_CREATOR'),
						canUnselectLast: false,
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'creator',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { Creator };
});
