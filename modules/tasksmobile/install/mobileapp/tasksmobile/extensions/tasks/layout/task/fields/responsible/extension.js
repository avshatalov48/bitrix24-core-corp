/**
 * @module tasks/layout/task/fields/responsible
 */
jn.define('tasks/layout/task/fields/responsible', (require, exports, module) => {
	const {Loc} = require('loc');
	const {Type} = require('type');
	const {UserField} = require('layout/ui/fields/user');

	class Responsible extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				responsible: props.responsible,
			};
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

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				UserField({
					readOnly: this.state.readOnly,
					showEditIcon: !this.state.readOnly,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_RESPONSIBLE'),
					multiple: false,
					value: this.state.responsible.id,
					titlePosition: 'left',
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						provider: {
							context: 'TASKS_MEMBER_SELECTOR_EDIT_responsible',
						},
						entityList: [
							{
								id: this.state.responsible.id,
								title: this.state.responsible.name,
								imageUrl: (
									!Type.isString(this.state.responsible.icon)
									|| !Type.isStringFilled(this.state.responsible.icon)
									|| this.state.responsible.icon.indexOf('default_avatar.png') >= 0
										? null
										: this.state.responsible.icon
								),
								customData: {
									position: this.state.responsible.workPosition,
								},
							},
						],
						canUnselectLast: false,
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'responsible',
					onChange: (responsibleId, responsibleData) => {
						if (Number(responsibleId) !== Number(this.state.responsible.id))
						{
							const responsible = {
								id: responsibleId,
								name: responsibleData[0].title,
								icon: responsibleData[0].imageUrl,
								workPosition: responsibleData[0].customData.position,
							};
							this.setState({responsible});
							this.props.onChange(responsible);
						}
					},
				}),
			);
		}
	}

	module.exports = {Responsible};
});