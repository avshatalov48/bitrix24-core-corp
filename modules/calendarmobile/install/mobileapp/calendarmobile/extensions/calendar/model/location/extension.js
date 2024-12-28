/**
 * @module calendar/model/location
 */
jn.define('calendar/model/location', (require, exports, module) => {
	class LocationModel
	{
		constructor(props)
		{
			this.setFields(props);
		}

		setFields(props)
		{
			this.id = BX.prop.getNumber(props, 'ID', 0);
			this.name = BX.prop.getString(props, 'NAME', '');
			this.color = BX.prop.getString(props, 'COLOR', '');
			this.capacity = BX.prop.getNumber(props, 'CAPACITY', 0);
			this.categoryId = BX.prop.getNumber(props, 'CATEGORY_ID', 0);
		}

		getId()
		{
			return this.id;
		}

		getName()
		{
			return this.name;
		}

		getColor()
		{
			return this.color;
		}

		getCapacity()
		{
			return this.capacity;
		}

		getCategoryId()
		{
			return this.categoryId;
		}
	}

	module.exports = { LocationModel };
});
