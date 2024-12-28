/**
 * @module calendar/event-view-form/fields/location
 */
jn.define('calendar/event-view-form/fields/location', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Type } = require('type');

	const { IconWithText, Icon } = require('calendar/event-view-form/layout/icon-with-text');

	class LocationField extends PureComponent
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

		isHidden()
		{
			return this.isEmpty();
		}

		render()
		{
			return IconWithText(Icon.LOCATION, this.props.value, 'calendar-event-view-form-location');
		}
	}

	module.exports = {
		LocationField: (props) => new LocationField(props),
	};
});
