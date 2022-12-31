/**
 * @module layout/ui/product-grid/model/product-row
 */
jn.define('layout/ui/product-grid/model/product-row', (require, exports, module) => {

	const { get, set, clone } = require('utils/object');

	/**
	 * @class ProductRow
	 *
	 * Model represents single product row.
	 * @abstract
	 */
	class ProductRow
	{
		constructor(props)
		{
			this.props = props || {};
		}

		/**
		 * @abstract
		 * @returns {Number|string}
		 */
		getId()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * Returns all product row data.
		 * @returns {object}
		 */
		getRawValues()
		{
			return this.props;
		}

		/**
		 * Returns value of specific field in the row.
		 * You can retrieve nested values, using dots as path separator.
		 * @param {string} path
		 * @param {any} defaultValue
		 * @returns {any}
		 */
		getField(path, defaultValue)
		{
			return get(this.props, path, defaultValue);
		}

		/**
		 * Set value of specific field in the row.
		 * You can set nested values, using dots as path separator. Method can be chained.
		 * @param {string} path
		 * @param {any} value
		 * @returns {ProductRow}
		 */
		setField(path, value)
		{
			set(this.props, path, value);
			return this;
		}

		/**
		 * Set all product row values.
		 * @param {object} fields
		 * @returns {ProductRow}
		 */
		setFields(fields)
		{
			this.props = clone(fields);
			return this;
		}
	}

	module.exports = { ProductRow };

});