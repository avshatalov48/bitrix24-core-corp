/**
 * @module calendar/event-edit-form/layout/save-event-container
 */
jn.define('calendar/event-edit-form/layout/save-event-container', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Area } = require('ui-system/layout/area');

	const { observeState } = require('calendar/event-edit-form/state');

	const { LocationInfo } = require('calendar/event-edit-form/layout/location/info');
	const { SaveEventButton } = require('calendar/event-edit-form/button/save-event');

	/**
	 * @class SaveEventContainer
	 */
	class SaveEventContainer extends LayoutComponent
	{
		get layout()
		{
			return this.props.layout;
		}

		render()
		{
			return Area(
				{
					isFirst: true,
					style: {
						display: this.hasToShow() ? 'flex' : 'none',
						borderTopColor: Color.bgSeparatorSecondary.toHex(),
						borderTopWidth: this.hasToShow() ? 1 : 0,
						backgroundColor: Color.bgSecondary.toHex(),
					},
				},
				new LocationInfo({ layout: this.layout }),
				new SaveEventButton({ layout: this.layout }),
			);
		}

		hasToShow()
		{
			return this.props.selectedSlot !== null || this.props.isCustomSelected;
		}
	}

	const mapStateToProps = (state) => ({
		selectedSlot: state.selectedSlot,
		isCustomSelected: state.isCustomSelected,
	});

	module.exports = { SaveEventContainer: observeState(SaveEventContainer, mapStateToProps) };
});
