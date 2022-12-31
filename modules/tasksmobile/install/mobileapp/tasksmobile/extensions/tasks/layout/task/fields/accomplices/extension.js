/**
 * @module tasks/layout/task/fields/accomplices
 */
jn.define('tasks/layout/task/fields/accomplices', (require, exports, module) => {
	const {Loc} = require('loc');
	const {Type} = require('type');
	const {UserField, UserFieldMode} = require('layout/ui/fields/user');

	class Accomplices extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				accomplices: props.accomplices,
			};
			props.checkList.on('accompliceAdd', (user) => {
				if (!Object.keys(this.state.accomplices).includes(user.id.toString()))
				{
					const accomplices = {
						...this.state.accomplices,
						[user.id]: {
							id: user.id,
							name: user.nameFormatted,
							icon: user.avatar,
						},
					};
					this.setState({accomplices});
					this.props.onChange(accomplices);
				}
			});
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				accomplices: props.accomplices,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				accomplices: newState.accomplices,
			});
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {})
				},
				UserField({
					readOnly: this.state.readOnly,
					showEditIcon: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_ACCOMPLICES'),
					multiple: true,
					value: Object.keys(this.state.accomplices),
					config: {
						mode: UserFieldMode.ICONS,
						deepMergeStyles: this.props.deepMergeStyles,
						provider: {
							context: 'TASKS_MEMBER_SELECTOR_EDIT_accomplice',
						},
						entityList: Object.values(this.state.accomplices).map((user) => ({
							id: user.id,
							title: user.name,
							imageUrl: (
								!Type.isString(user.icon)
								|| !Type.isStringFilled(user.icon)
								|| user.icon.indexOf('default_avatar.png') >= 0
									? null
									: user.icon
							),
							customData: {
								position: user.workPosition,
							},
						})),
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'accomplices',
					onChange: (accomplicesIds, accomplicesData) => {
						const accomplices = accomplicesData.reduce((result, user) => {
							result[user.id] = {
								id: user.id,
								name: user.title,
								icon: user.imageUrl,
								workPosition: user.customData.position,
							};
							return result;
						}, {});
						const newAccomplices = Object.keys(accomplices);
						const oldAccomplices = Object.keys(this.state.accomplices);
						const difference =
							newAccomplices
								.filter(id => !oldAccomplices.includes(id))
								.concat(oldAccomplices.filter(id => !newAccomplices.includes(id)))
						;
						if (difference.length)
						{
							this.setState({accomplices});
							this.props.onChange(accomplices);
						}
					},
				}),
			);
		}
	}

	module.exports = {Accomplices};
});