/**
 * @module im/messenger/lib/utils/object
 */
jn.define('im/messenger/lib/utils/object', (require, exports, module) => {
	class ObjectUtils
	{
		/**
		 * use for objects without cyclic references
		 *
		 * @param {Object} originalObject
		 * @param {Boolean} recursively
		 *
		 * @return {Object}
		 */
		static convertKeysToCamelCase(originalObject, recursively = true)
		{
			if (typeof originalObject !== 'object' || originalObject === null)
			{
				return originalObject;
			}

			if (BX.type.isArray(originalObject))
			{
				return originalObject.map((element) => ObjectUtils.convertKeysToCamelCase(element, recursively));
			}

			return Object.fromEntries(
				Object.entries(originalObject)
					.map(([key, value]) => {
						if (recursively && BX.type.isPlainObject(originalObject[key]))
						{
							if (!key.includes('_'))
							{
								const newKey = (key.toUpperCase() === key) ? key.toLowerCase() : key;

								return [newKey, ObjectUtils.convertKeysToCamelCase(originalObject[key])];
							}

							return [
								ObjectUtils.stringToCamelCase(key.toLowerCase()),
								ObjectUtils.convertKeysToCamelCase(originalObject[key]),
							];
						}

						if (recursively && BX.type.isArray(value))
						{
							return [
								ObjectUtils.stringToCamelCase(key.toLowerCase()),
								value.map((element) => ObjectUtils.convertKeysToCamelCase(element, recursively)),
							];
						}

						if (!key.includes('_'))
						{
							const newKey = (key.toUpperCase() === key) ? key.toLowerCase() : key;

							return [newKey, value];
						}

						return [ObjectUtils.stringToCamelCase(key.toLowerCase()), value];
					}),
			);
		}

		/**
		 * @param {string} string
		 * @return {string}
		 */
		static stringToCamelCase(string)
		{
			return string.replace(/([_-][1-9a-z])/gi, (sub) => {
				return sub.toUpperCase()
					.replace('-', '')
					.replace('_', '')
				;
			});
		}

		/**
		 * @desc Returns check on white space in all string ('   ' => true, '   d   ' => false)
		 * @param {string} string
		 * @return {boolean}
		 */
		static isStringFullSpace(string) {
			return /^\s*$/.test(string);
		}
	}

	module.exports = { ObjectUtils };
});
