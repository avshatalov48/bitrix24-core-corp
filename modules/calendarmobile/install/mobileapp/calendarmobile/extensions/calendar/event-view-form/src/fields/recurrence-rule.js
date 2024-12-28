/**
 * @module calendar/event-view-form/fields/recurrence-rule
 */
jn.define('calendar/event-view-form/fields/recurrence-rule', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');

	const { IconWithText, Icon } = require('calendar/event-view-form/layout/icon-with-text');

	class RecurrenceRuleField extends PureComponent
	{
		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRequired()
		{
			return false;
		}

		isEmpty()
		{
			return !Type.isStringFilled(this.props.value);
		}

		render()
		{
			const recurrenceRuleDescription = this.props.value;

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				Text5({
					text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_RECURRENCE'),
					color: Color.base4,
				}),
				IconWithText(Icon.REPEAT, recurrenceRuleDescription, 'calendar-event-view-form-recurrence-rule', false),
			);
		}
	}

	module.exports = {
		RecurrenceRuleField: (props) => new RecurrenceRuleField(props),
	};
});
