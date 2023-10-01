/**
 * @module layout/ui/fields/address/value-converter
 */
jn.define('layout/ui/fields/address/value-converter', (require, exports, module) => {

	/**
	 * AddressValueConverter
	 */
	class AddressValueConverter
	{
		/**
		 * @public
		 * @param {?Number} id
		 * @param {?String} json
		 * @param {?String} text
		 * @param {?Array} coords
		 * @returns {Array}
		 */
		static convertToValue({
			id = null,
			json = null,
			text = null,
			coords = []
		}
		)
		{
			return [
				text, coords, id, json
			];
		}

		/**
		 * @public
		 * @param {any} value
		 * @returns {Object}
		 */
		static convertFromValue(value)
		{
			let id, json, text = null;
			let coords = [];

			if (Array.isArray(value))
			{
				[text, coords, id, json] = value;
			}

			return {
				id,
				json,
				text,
				coords,
			};
		}
	}

	module.exports = {
		AddressValueConverter,
	};

});
