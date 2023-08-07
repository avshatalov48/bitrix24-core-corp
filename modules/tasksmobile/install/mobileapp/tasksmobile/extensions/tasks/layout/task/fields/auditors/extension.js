/**
 * @module tasks/layout/task/fields/auditors
 */
jn.define('tasks/layout/task/fields/auditors', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { UserField, UserFieldMode } = require('layout/ui/fields/user');

	class Auditors extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				auditors: props.auditors,
			};
			props.checkList.on('auditorAdd', (user) => {
				if (!Object.keys(this.state.auditors).includes(user.id.toString()))
				{
					const auditors = {
						...this.state.auditors,
						[user.id]: {
							id: user.id,
							name: user.nameFormatted,
							icon: user.avatar,
						},
					};
					this.setState({ auditors });
					this.props.onChange(auditors);
				}
			});
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				auditors: props.auditors,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				auditors: newState.auditors,
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
					showEditIcon: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_AUDITORS'),
					multiple: true,
					value: Object.keys(this.state.auditors),
					config: {
						mode: UserFieldMode.ICONS,
						deepMergeStyles: this.props.deepMergeStyles,
						provider: {
							context: 'TASKS_MEMBER_SELECTOR_EDIT_auditor',
						},
						entityList: Object.values(this.state.auditors).map((user) => ({
							id: user.id,
							title: user.name,
							imageUrl: (
								!Type.isString(user.icon)
								|| !Type.isStringFilled(user.icon)
								|| user.icon.includes('default_avatar.png')
									? null
									: user.icon
							),
							customData: {
								position: user.workPosition,
							},
						})),
						selectorTitle: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_AUDITORS'),
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'auditors',
					onChange: (auditorsIds, auditorsData) => {
						const auditors = auditorsData.reduce((accumulator, user) => {
							const result = accumulator;
							result[user.id] = {
								id: user.id,
								name: user.title,
								icon: user.imageUrl,
								workPosition: user.customData.position,
							};

							return result;
						}, {});
						const newAuditors = Object.keys(auditors);
						const oldAuditors = Object.keys(this.state.auditors);
						const difference = [
							...newAuditors.filter((id) => !oldAuditors.includes(id)),
							...oldAuditors.filter((id) => !newAuditors.includes(id)),
						];
						if (difference.length > 0)
						{
							this.setState({ auditors });
							this.props.onChange(auditors);
						}
					},
				}),
			);
		}
	}

	module.exports = { Auditors };
});
