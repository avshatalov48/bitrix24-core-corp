/**
 * @module tasks/layout/task/fields/auditors
 */
jn.define('tasks/layout/task/fields/auditors', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { UserField, UserFieldMode } = require('layout/ui/fields/user');
	const { AnalyticsEvent } = require('analytics');

	class Auditors extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				auditors: props.auditors,
			};
			props.checkList?.on('auditorAdd', (user) => {
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

			this.handleOnChange = this.handleOnChange.bind(this);
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

		handleOnChange(auditorsIds, auditorsData)
		{
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
				const { onChange } = this.props;

				if (onChange)
				{
					onChange(auditors);
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
					showEditIcon: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_AUDITORS'),
					multiple: true,
					value: Object.keys(this.state.auditors),
					config: {
						enableCreation: !(env.isCollaber || env.extranet),
						canOpenUserList: true,
						mode: UserFieldMode.ICONS,
						useLettersForEmptyAvatar: true,
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
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { Auditors };
});
