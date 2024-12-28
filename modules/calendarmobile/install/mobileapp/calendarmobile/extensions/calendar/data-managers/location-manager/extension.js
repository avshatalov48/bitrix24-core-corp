/**
 * @module calendar/data-managers/location-manager
 */
jn.define('calendar/data-managers/location-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { LocationModel } = require('calendar/model/location');
	const { CategoryModel } = require('calendar/model/category');

	const DEFAULT_CATEGORY_ID = 0;

	/**
	 * @class LocationManager
	 */
	class LocationManager
	{
		constructor()
		{
			this.locations = [];
			this.categories = [];
			this.locationFeatureEnabled = true;
		}

		setLocations(locationInfo)
		{
			locationInfo.forEach((locationRaw) => {
				const location = new LocationModel(locationRaw);
				this.locations[location.getId()] = location;
			});
		}

		getLocations()
		{
			return this.locations.filter((location) => location);
		}

		getLocationIds()
		{
			return Object.keys(this.locations);
		}

		getLocation(id)
		{
			return this.locations[id] || {};
		}

		setCategories(categoriesInfo)
		{
			categoriesInfo.forEach((categoriesRaw) => {
				const category = new CategoryModel(categoriesRaw);
				this.categories[category.getId()] = category;
			});
		}

		getCategories()
		{
			return this.categories.filter((category) => category);
		}

		getDefaultCategory(defaultCategoryName)
		{
			return new CategoryModel({ ID: DEFAULT_CATEGORY_ID, NAME: defaultCategoryName });
		}

		getSortedLocations()
		{
			return this.getLocations().sort((first, second) => this.compareByName(first, second));
		}

		getSortedCategories(defaultCategoryName)
		{
			const categories = this.categories
				.filter((category) => this.hasLocationsInCategory(category.getId()))
				.sort((first, second) => this.compareByName(first, second))
			;

			if (!Type.isArrayFilled(categories))
			{
				return categories;
			}

			if (this.hasLocationsInCategory(DEFAULT_CATEGORY_ID))
			{
				categories.push(this.getDefaultCategory(defaultCategoryName));
			}

			return categories;
		}

		compareByName(first, second)
		{
			const firstName = first.getName().toLowerCase();
			const secondName = second.getName().toLowerCase();

			if (firstName > secondName)
			{
				return 1;
			}

			return firstName < secondName ? -1 : 0;
		}

		hasLocationsInCategory(categoryId)
		{
			return this.getLocations().find((location) => location.getCategoryId() === categoryId);
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

					if (!Type.isNil(value[2]) && parseInt(value[2], 10) > 0)
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

		getLocationIdByName(textLocation)
		{
			const result = this.getLocations().find((location) => location.getName() === textLocation);

			return result?.getId();
		}

		prepareTextLocation(textLocation)
		{
			const locationId = this.getLocationIdByName(textLocation);

			if (locationId)
			{
				return `calendar_${locationId}`;
			}

			return textLocation;
		}
	}

	module.exports = { LocationManager: new LocationManager() };
});
