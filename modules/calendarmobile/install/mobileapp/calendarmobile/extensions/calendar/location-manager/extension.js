/**
 * @module calendar/location-manager
 */
jn.define('calendar/location-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { LocationModel } = require('calendar/model/location');

	/**
	 * @class LocationManager
	 */
	class LocationManager
	{
		constructor(props)
		{
			this.locations = [];
			this.locationIndex = {};

			this.setLocations(props.locationInfo);
		}

		setLocations(locationInfo)
		{
			locationInfo.forEach((locationRaw) => {
				const location = new LocationModel(locationRaw);

				this.locations.push(location);
			});

			this.locations.forEach((location, index) => {
				this.locationIndex[location.getId()] = index;
			});
		}

		getLocation(id)
		{
			return this.locations[this.locationIndex[id]] || {};
		}

		parseLocation(textLocation)
		{
			if (!Type.isString(textLocation))
			{
				// eslint-disable-next-line no-param-reassign
				textLocation = '';
			}

			const result = {
				type: false,
				locationId: false,
				eventId: false,
				name: textLocation,
			};

			if (textLocation.slice(0, 9) === 'calendar_')
			{
				result.type = 'calendar';
				const value = textLocation.split('_');

				if (value.length >= 2)
				{
					if (!Type.isNil(value[1]) && parseInt(value[1], 10) > 0)
					{
						result.locationId = parseInt(value[1], 10);
					}

					if (!Type.isNil(value[2]) && parseInt(value[2]) > 0)
					{
						result.eventId = parseInt(value[2], 10);
					}
				}
			}

			return result;
		}

		getTextLocation(textLocation)
		{
			let result = textLocation;
			const parsedLocation = this.parseLocation(textLocation);

			if (
				parsedLocation.type === 'calendar'
				&& parsedLocation.locationId
				&& Type.isArrayFilled(this.locations)
			)
			{
				const location = this.getLocation(parsedLocation.locationId);
				if (location && location.name)
				{
					result = location.getName();
				}
			}

			return result;
		}
	}

	module.exports = { LocationManager };
});