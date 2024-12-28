/**
 * @module calendar/event-edit-form/layout/save-event-container
 */
jn.define('calendar/event-edit-form/layout/save-event-container', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Area } = require('ui-system/layout/area');

	const { observeState } = require('calendar/event-edit-form/state');

	const { LocationInfo } = require('calendar/event-edit-form/layout/location-info');
	const { SaveEventButton } = require('calendar/event-edit-form/layout/save-event-button');

	/**
	 * @class SaveEventContainer
	 */
	class SaveEventContainer extends LayoutComponent
	{
		render()
		{
			return Area(
				{
					isFirst: true,
					style: {
						display: this.props.selectedSlot === null ? 'none' : 'flex',
						borderTopColor: Color.bgSeparatorSecondary.toHex(),
						borderTopWidth: this.props.selectedSlot === null ? 0 : 1,
						backgroundColor: Color.bgSecondary.toHex(),
					},
				},
				new LocationInfo({ layout: this.props.layout }),
				new SaveEventButton({ layout: this.props.layout }),
			);
		}
	}

	const mapStateToProps = (state) => ({
		selectedSlot: state.selectedSlot,
	});

	module.exports = { SaveEventContainer: observeState(SaveEventContainer, mapStateToProps) };
});
