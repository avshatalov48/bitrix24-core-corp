/**
 * @module calendar/model/category
 */
jn.define('calendar/model/category', (require, exports, module) => {
	/**
	 * @class CategoryModel
	 */
	class CategoryModel
	{
		constructor(props)
		{
			this.setFields(props);
		}

		setFields(props)
		{
			this.id = BX.prop.getNumber(props, 'ID', 0);
			this.name = BX.prop.getString(props, 'NAME', '');
		}

		getId()
		{
			return this.id;
		}

		getName()
		{
			return this.name;
		}
	}

	module.exports = { CategoryModel };
});
