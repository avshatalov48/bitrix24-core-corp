/**
 * @module tasks/layout/fields/date-plan/theme/air/redux-content
 */
jn.define('tasks/layout/fields/date-plan/theme/air/redux-content', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const { getFormattedDateTime } = require('tasks/layout/fields/date-plan/formatter');

	const { selectDatePlan } = require('tasks/statemanager/redux/slices/tasks/selector');
	const { connect } = require('statemanager/redux/connect');
	const { PureComponent } = require('layout/pure-component');

	class ReduxContent extends PureComponent
	{
		/**
		 * @returns {DatePlanField}
		 */
		get #field()
		{
			return this.props.field;
		}

		/**
		 * @returns {string}
		 */
		get #testId()
		{
			return this.#field.testId;
		}

		render()
		{
			return View(
				{
					ref: this.#field.bindContainerRef,
				},
				View(
					{
						style: {},
						onClick: this.#field.getContentClickHandler(),
						testId: this.#testId,
					},
					this.renderTitle(),
					this.renderDatePlan(),
				),
			);
		}

		renderTitle()
		{
			return Text5({
				text: Loc.getMessage('M_TASKS_DATE_PLAN_AIR_FIELD_TITLE'),
				color: Color.base4,
			});
		}

		renderDatePlan()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
						marginTop: Indent.XL.toNumber(),
					},
				},
				this.renderStartDatePlan(),
				this.renderArrow(),
				this.renderEndDatePlan(),
			);
		}

		renderArrow()
		{
			return IconView(
				{
					icon: Icon.ARROW_TO_THE_RIGHT,
					color: Color.base6,
					iconSize: {
						width: 20,
						height: 20,
					},
					style: {
						marginHorizontal: Indent.S.toNumber(),
					},
				},
			);
		}

		renderStartDatePlan()
		{
			return Text5(
				{
					testId: `${this.#testId}-start-date-plan`,
					text: getFormattedDateTime(this.props.startDatePlan) || Loc.getMessage('M_TASKS_DATE_PLAN_AIR_FIELD_NO_DATE_SPECIFIED'),
				},
			);
		}

		renderEndDatePlan()
		{
			return Text5(
				{
					testId: `${this.#testId}-end-date-plan`,
					text: getFormattedDateTime(this.props.endDatePlan) || Loc.getMessage('M_TASKS_DATE_PLAN_AIR_FIELD_NO_DATE_SPECIFIED'),
				},
			);
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const taskId = ownProps.field.taskId;

		const {
			startDatePlan,
			endDatePlan,
		} = selectDatePlan(state, taskId);

		return {
			id: taskId,
			startDatePlan,
			endDatePlan,
		};
	};

	module.exports = {
		DatePlanAirReduxContent: connect(mapStateToProps)(ReduxContent),
	};
});
