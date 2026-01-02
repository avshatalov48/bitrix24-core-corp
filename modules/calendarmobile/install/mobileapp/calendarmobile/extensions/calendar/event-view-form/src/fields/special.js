/**
 * @module calendar/event-view-form/fields/special
 */
jn.define('calendar/event-view-form/fields/special', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');

	const { EventImportance } = require('calendar/enums');
	const { IconWithText, Icon } = require('calendar/event-view-form/layout/icon-with-text');

	/**
	 * @class SpecialField
	 */
	class SpecialField extends PureComponent
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
			return !this.isImportant() && !this.isPrivate();
		}

		isHidden()
		{
			return false;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				Text5({
					text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_SPECIAL_NOTE'),
					color: Color.base4,
				}),
				this.isImportant() && IconWithText(Icon.FIRE, Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_IMPORTANCE'), 'calendar-event-view-form-importance', false),
				this.isPrivate() && IconWithText(Icon.LOCK, Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_PRIVATE'), 'calendar-event-view-form-private', false),
			);
		}

		isImportant()
		{
			return this.props.importance === EventImportance.HIGH;
		}

		isPrivate()
		{
			return this.props.privateEvent === 1;
		}
	}

	module.exports = {
		SpecialField: (props) => new SpecialField(props),
	};
});
