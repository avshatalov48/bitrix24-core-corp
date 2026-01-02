/**
 * @module calendar/event-view-form/fields/accessibility
 */
jn.define('calendar/event-view-form/fields/accessibility', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');

	const { EventAccessibility } = require('calendar/enums');
	const { IconWithText, Icon } = require('calendar/event-view-form/layout/icon-with-text');

	/**
	 * @class AccessibilityField
	 */
	class AccessibilityField extends PureComponent
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
			return true;
		}

		isEmpty()
		{
			return false;
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
					text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ACCESSIBILITY'),
					color: Color.base4,
				}),
				IconWithText(Icon.PLANNING, this.getText(), 'calendar-event-view-form-accessibility', false),
			);
		}

		getText()
		{
			const accessibility = Object.values(EventAccessibility).includes(this.props.value)
				? this.props.value
				: EventAccessibility.BUSY
			;

			return Loc.getMessage(`M_CALENDAR_EVENT_VIEW_FORM_ACCESSIBILITY_${accessibility.toUpperCase()}`);
		}
	}

	module.exports = {
		AccessibilityField: (props) => new AccessibilityField(props),
	};
});
