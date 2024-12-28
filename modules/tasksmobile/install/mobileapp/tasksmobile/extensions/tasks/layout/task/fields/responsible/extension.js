/**
 * @module tasks/layout/task/fields/responsible
 */
jn.define('tasks/layout/task/fields/responsible', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { UserField } = require('layout/ui/fields/user');
	const { AnalyticsEvent } = require('analytics');

	class Responsible extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				responsible: props.responsible,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				responsible: props.responsible,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				responsible: newState.responsible,
			});
		}

		handleOnChange(responsibleId, responsibleData)
		{
			if (Number(responsibleId) !== Number(this.state.responsible.id))
			{
				const responsible = {
					id: responsibleId,
					name: responsibleData[0].title,
					icon: responsibleData[0].imageUrl,
					workPosition: responsibleData[0].customData.position,
				};
				this.setState({ responsible });
				const { onChange } = this.props;

				if (onChange)
				{
					onChange(responsible);
				}
			}
		}

		getUsersList()
		{
			const { responsible = {} } = this.state;

			return [
				{
					id: responsible.id,
					title: responsible.name,
					imageUrl: (
						!Type.isString(responsible.icon)
						|| !Type.isStringFilled(responsible.icon)
						|| responsible.icon.includes('default_avatar.png')
							? null
							: responsible.icon
					),
					customData: {
						position: responsible.workPosition,
					},
				},
			];
		}

		render()
		{
			const { deepMergeStyles, style, parentWidget } = this.props;
			const { readOnly, responsible = {} } = this.state;

			return View(
				{
					style: (style || {}),
				},
				UserField({
					analytics: new AnalyticsEvent().setSection('tasks'),
					readOnly,
					showEditIcon: !readOnly,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_RESPONSIBLE_MSGVER_1'),
					multiple: false,
					value: responsible.id,
					titlePosition: 'left',
					config: {
						enableCreation: !(env.isCollaber || env.extranet),
						deepMergeStyles,
						useLettersForEmptyAvatar: true,
						provider: {
							context: 'TASKS_MEMBER_SELECTOR_EDIT_responsible',
						},
						entityList: this.getUsersList(),
						parentWidget,
						selectorTitle: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_RESPONSIBLE_MSGVER_1'),
						canUnselectLast: false,
						reloadEntityListFromProps: true,
					},
					testId: 'responsible',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { Responsible };
});
