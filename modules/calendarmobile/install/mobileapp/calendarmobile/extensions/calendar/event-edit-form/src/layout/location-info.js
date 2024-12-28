/**
 * @module calendar/event-edit-form/layout/location-info
 */
jn.define('calendar/event-edit-form/layout/location-info', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { Button, Icon, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');

	const { observeState } = require('calendar/event-edit-form/state');
	const { LocationInput } = require('calendar/event-edit-form/layout/location-input');

	const meetingIconEnabled = Application.getApiVersion() >= 56;

	/**
	 * @class LocationInfo
	 */
	class LocationInfo extends LayoutComponent
	{
		render()
		{
			return Button({
				testId: 'calendar-event-edit-form-location-selector',
				text: this.getLocationName(),
				design: ButtonDesign.PLAIN,
				color: Color.base4,
				size: ButtonSize.XS,
				leftIcon: meetingIconEnabled ? Icon.MEETING_POINT : Icon.LOCATION,
				rightIcon: Icon.CHEVRON_DOWN,
				style: {
					marginBottom: Indent.XL2.toNumber(),
				},
				stretched: true,
				onClick: this.openLocationInput,
			});
		}

		getLocationName()
		{
			return this.hasLocationName()
				? this.props.location
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_LOCATION')
			;
		}

		hasLocationName()
		{
			return Type.isStringFilled(this.props.location);
		}

		openLocationInput = () => {
			LocationInput.open(this.props.layout);
		};
	}

	const mapStateToProps = (state) => ({
		location: state.location,
	});

	module.exports = { LocationInfo: observeState(LocationInfo, mapStateToProps) };
});
