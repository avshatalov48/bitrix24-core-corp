/**
 * @module tasks/layout/task/fields/crm
 */
jn.define('tasks/layout/task/fields/crm', (require, exports, module) => {
	const {Loc} = require('loc');
	const {CrmElementField} = require('layout/ui/fields/crm-element');
	const { AnalyticsLabel } = require('analytics-label');

	class Crm extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				crm: (props.crm || {}),
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				crm: (props.crm || {}),
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				crm: (newState.crm || {}),
			});
		}

		render()
		{
			if (!CrmElementField)
			{
				return null;
			}

			const values = Object.values(this.state.crm);

			return View(
				{
					style: (this.props.style || {}),
				},
				CrmElementField({
					readOnly: this.state.readOnly,
					showEditIcon: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_CRM'),
					value: values.map(item => item.id),
					multiple: true,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						isComplex: true,
						entityList: values,
						provider: {
							context: 'TASKS_CRM',
						},
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
					},
					testId: 'crm',
					onChange: (crmIds, crmData) => {
						const crm = crmData.reduce((result, item) => ({
							...result,
							[`${item.type}_${item.id}`]: {
								id: item.id,
								title: item.title,
								subtitle: item.subtitle,
								type: item.type,
							},
						}), {});
						const newCrm = Object.keys(crm);
						const oldCrm = values.map(item => `${item.type}_${item.id}`);
						const difference =
							newCrm
								.filter(id => !oldCrm.includes(id))
								.concat(oldCrm.filter(id => !newCrm.includes(id)))
						;
						if (difference.length)
						{
							this.setState({crm: crmData});
							this.props.onChange(crm);
						}

						AnalyticsLabel.send({
							event: 'onCrmFieldChange',
							scenario: 'task_add',
						});
					},
				}),
			);
		}
	}

	module.exports = {Crm};
});