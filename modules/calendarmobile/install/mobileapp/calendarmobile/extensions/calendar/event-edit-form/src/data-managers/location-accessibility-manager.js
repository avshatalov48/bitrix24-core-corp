/**
 * @module calendar/event-edit-form/data-managers/location-accessibility-manager
 */
jn.define('calendar/event-edit-form/data-managers/location-accessibility-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('calendar/date-helper');
	const { AccessibilityAjax } = require('calendar/ajax');
	const { LocationManager } = require('calendar/data-managers/location-manager');

	/**
	 * @class LocationAccessibilityManager
	 */
	class LocationAccessibilityManager
	{
		constructor()
		{
			this.init();
		}

		init()
		{
			this.accessibility = {};
			this.accessibilityPromises = {};
			this.reservedLocations = [];
			this.reservedLocationsKey = null;
		}

		calculateReservedLocations({ date, fromTs, toTs, skipEventId })
		{
			if (!this.hasAccessibility({ date }))
			{
				return {};
			}

			const { datesKey } = this.prepareTimestamps(date);
			const locationIds = LocationManager.getLocationIds();

			const reservedLocationsKey = `${datesKey}.${fromTs.toString()}.${toTs.toString()}.${skipEventId}`;
			if (reservedLocationsKey === this.reservedLocationsKey)
			{
				return this.reservedLocations;
			}

			const accessibility = this.accessibility[datesKey];

			locationIds.forEach((locationId) => {
				this.reservedLocations[locationId] = false;
			});

			for (const locationId of locationIds)
			{
				if (
					Type.isUndefined(accessibility[locationId])
					|| !Type.isArrayFilled(accessibility[locationId])
				)
				{
					continue;
				}

				for (const event of accessibility[locationId])
				{
					if (event.parentId === skipEventId)
					{
						continue;
					}

					if (event.from < toTs && event.to > fromTs)
					{
						this.reservedLocations[locationId] = true;

						break;
					}
				}
			}

			return this.reservedLocations;
		}

		hasAccessibility({ date })
		{
			const { datesKey } = this.prepareTimestamps(date);

			return Boolean(this.accessibility[datesKey]);
		}

		deleteLoadedAccessibility({ date })
		{
			const { datesKey } = this.prepareTimestamps(date);

			delete this.accessibility[datesKey];
			delete this.accessibilityPromises[datesKey];
		}

		async loadAccessibility({ date })
		{
			const { datesKey, timestampFrom, timestampTo } = this.prepareTimestamps(date);

			this.accessibilityPromises[datesKey] ??= this.requestAccessibility({ timestampFrom, timestampTo });

			const { data } = await this.accessibilityPromises[datesKey];

			const fullDayOffset = DateHelper.timezoneOffset;
			const accessibility = Object.keys(data).reduce((acc, entityId) => ({
				[entityId]: data[entityId].map((it) => ({
					...it,
					from: it.from * 1000 + (it.isFullDay ? fullDayOffset : 0),
					to: it.to * 1000 + (it.isFullDay ? fullDayOffset : 0),
				})),
				...acc,
			}), {});

			this.accessibility[datesKey] ??= {};
			Object.assign(this.accessibility[datesKey], accessibility);
		}

		/**
		 * @private
		 */
		prepareTimestamps(date)
		{
			const timestampFrom = new Date(date.getFullYear(), date.getMonth(), date.getDate() - 1).getTime();
			const timestampTo = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1).getTime();
			const datesKey = `${timestampFrom}-${timestampTo}`;

			return { datesKey, timestampFrom, timestampTo };
		}

		/**
		 * @private
		 */
		requestAccessibility({ timestampFrom, timestampTo })
		{
			const locationIds = LocationManager.getLocationIds();

			return AccessibilityAjax.getLocation({ locationIds, timestampFrom, timestampTo });
		}
	}

	module.exports = { LocationAccessibilityManager: new LocationAccessibilityManager() };
});
