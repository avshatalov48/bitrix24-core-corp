/**
 * @module tasks/layout/fields/date-plan
 */
jn.define('tasks/layout/fields/date-plan', (require, exports, module) => {
	const { DatePlanView } = require('tasks/layout/fields/date-plan/view');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { update } = require('tasks/statemanager/redux/slices/tasks');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');

	class DatePlanField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.fieldContainerRef = null;
		}

		render()
		{
			if (this.props.ThemeComponent)
			{
				return this.props.ThemeComponent(this);
			}

			return null;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getId()
		{
			return this.props.id;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		get testId()
		{
			return this.props.testId;
		}

		/**
		 * @public
		 * @returns {integer}
		 */
		get taskId()
		{
			return this.props.taskId;
		}

		get #groupId()
		{
			return this.props.groupId;
		}

		get #groupPlan()
		{
			const group = selectGroupById(store.getState(), this.#groupId);

			return { dateStart: group?.dateStart, dateFinish: group?.dateFinish };
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isEmpty()
		{
			return !this.props.startDatePlan && !this.props.endDatePlan;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isReadOnly()
		{
			return this.props.readOnly;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */

		isRequired()
		{
			return false;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isValid()
		{
			return true;
		}

		/**
		 * @public
		 * @return {(function(): void)|null}
		 */
		getContentClickHandler()
		{
			if (this.isReadOnly() && !this.props.onContentClick)
			{
				return null;
			}

			return this.openDatePlan;
		}

		openDatePlan = () => {
			if (this.isReadOnly())
			{
				return;
			}

			DatePlanView.open({
				taskId: this.taskId,
				groupPlan: this.#groupPlan,
				parentWidget: this.props.parentWidget,
				onSave: this.onSave,
				onHidden: this.props.onHidden,
				startDatePlan: this.props.startDatePlan,
				endDatePlan: this.props.endDatePlan,
			});
		};

		onSave = (startDatePlan, endDatePlan) => {
			if (this.props.onChange && this.props.mode === 'create')
			{
				this.props.onChange(startDatePlan, endDatePlan);

				return;
			}

			dispatch(
				update({
					taskId: this.taskId,
					serverFields: {
						START_DATE_PLAN: startDatePlan ? this.convertDateFromUnixToISOString(startDatePlan) : '',
						END_DATE_PLAN: endDatePlan ? this.convertDateFromUnixToISOString(endDatePlan) : '',
					},
					reduxFields: {
						startDatePlan,
						endDatePlan,
					},
				}),
			);
		};

		convertDateFromUnixToISOString(date)
		{
			return (new Date(date * 1000)).toISOString();
		}

		/**
		 * @public
		 * @param ref
		 */
		bindContainerRef(ref)
		{
			this.fieldContainerRef = ref;
		}
	}
	module.exports = { DatePlanField };
});
