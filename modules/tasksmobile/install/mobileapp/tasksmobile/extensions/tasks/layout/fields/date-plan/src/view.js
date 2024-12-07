/**
 * @module tasks/layout/fields/date-plan/view
 */
jn.define('tasks/layout/fields/date-plan/view', (require, exports, module) => {
	const { DatePlanViewContent } = require('tasks/layout/fields/date-plan/view-redux-content');
	const { Box } = require('ui-system/layout/box');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');

	class DatePlanView extends LayoutComponent
	{
		/**
		 * @typedef {Object} GroupPlan
		 * @property {number} [dateStart = null]
		 * @property {number} [dateFinish = null]
		 */

		/**
		 * @public
		 * @param {Object} [data={}]
		 * @param {Object} [data.parentWidget = PageManager]
		 * @param {number} [data.taskId]
		 * @param {number} data.startDatePlan
		 * @param {number} data.endDatePlan
		 * @param {Function} [data.onSave]
		 * @param {GroupPlan} data.groupPlan
		 * @param {Function} data.onHidden
		 */
		static open(data = {})
		{
			const parentWidget = data.parentWidget || PageManager;

			const datePlanView = new DatePlanView({
				taskId: data.taskId,
				groupPlan: data.groupPlan,
				onSave: data.onSave,
				startDatePlan: data.startDatePlan,
				endDatePlan: data.endDatePlan,
				onHidden: data.onHidden,
			});

			void new BottomSheet({
				titleParams: {
					text: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_TITLE'),
					type: 'dialog',
					useLargeTitleMode: true,
				},
				component: (widget) => {
					datePlanView.parentWidget = widget;

					return datePlanView;
				},
			}).setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setMediumPositionHeight(450)
				.disableOnlyMediumPosition()
				.open()
			;
		}

		render()
		{
			return Box(
				{
					safeArea: {
						bottom: true,
					},
					resizableByKeyboard: true,
				},
				DatePlanViewContent({
					taskId: this.props.taskId,
					groupPlan: this.props.groupPlan,
					parentWidget: this.parentWidget,
					onSave: this.props.onSave,
					startDatePlan: this.props.startDatePlan,
					endDatePlan: this.props.endDatePlan,
					isMatchWorkTime: this.props.isMatchWorkTime,
					onHidden: this.props.onHidden,
				}),
			);
		}
	}

	module.exports = { DatePlanView };
});
