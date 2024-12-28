/**
 * @module type
 */
jn.define('type', (require, exports, module) => {

	function getTag(value)
	{
		return Object.prototype.toString.call(value);
	}

	const objectCtorString = Function.prototype.toString.call(Object);

	/**
	 * @class Type
	 */
	class Type
	{
		/**
		 * Checks that value is string
		 * @param value
		 * @return {boolean}
		 */
		static isString(value)
		{
			return typeof value === 'string';
		}

		/**
		 * Returns true if a value is not empty string
		 * @param value
		 * @returns {boolean}
		 */
		static isStringFilled(value)
		{
			return Type.isString(value) && value !== '';
		}

		/**
		 * Checks that value is function
		 * @param value
		 * @return {boolean}
		 */
		static isFunction(value)
		{
			return typeof value === 'function';
		}

		/**
		 * Checks that value is object
		 * @param value
		 * @return {boolean}
		 */
		static isObject(value)
		{
			return !!value && (typeof value === 'object' || typeof value === 'function');
		}

		/**
		 * Checks that value is object like
		 * @param value
		 * @return {boolean}
		 */
		static isObjectLike(value)
		{
			return !!value && typeof value === 'object';
		}

		/**
		 * Checks that value is plain object
		 * @param value
		 * @return {boolean}
		 */
		static isPlainObject(value)
		{
			if (!Type.isObjectLike(value) || getTag(value) !== '[object Object]')
			{
				return false;
			}

			const proto = Object.getPrototypeOf(value);
			if (proto === null)
			{
				return true;
			}

			const ctor = proto.hasOwnProperty('constructor') && proto.constructor;

			return (
				typeof ctor === 'function' &&
				Function.prototype.toString.call(ctor) === objectCtorString
			);
		}

		/**
		 * Checks that value is boolean
		 * @param value
		 * @return {boolean}
		 */
		static isBoolean(value)
		{
			return value === true || value === false;
		}

		/**
		 * Checks that value is number
		 * @param value
		 * @return {boolean}
		 */
		static isNumber(value)
		{
			return !Number.isNaN(value) && typeof value === 'number';
		}

		/**
		 * Checks that value is integer
		 * @param value
		 * @return {boolean}
		 */
		static isInteger(value)
		{
			return Type.isNumber(value) && (value % 1) === 0;
		}

		/**
		 * Checks that value is float
		 * @param value
		 * @return {boolean}
		 */
		static isFloat(value)
		{
			return Type.isNumber(value) && !Type.isInteger(value);
		}

		/**
		 * Checks that value is nil
		 * @param value
		 * @return {boolean}
		 */
		static isNil(value)
		{
			return value === null || value === undefined;
		}

		/**
		 * Checks that value is array
		 * @param value
		 * @return {boolean}
		 */
		static isArray(value)
		{
			return !Type.isNil(value) && Array.isArray(value);
		}

		/**
		 * Returns true if a value is an array and it has at least one element
		 * @param value
		 * @returns {boolean}
		 */
		static isArrayFilled(value)
		{
			return Type.isArray(value) && value.length > 0;
		}

		/**
		 * Checks that value is array like
		 * @param value
		 * @return {boolean}
		 */
		static isArrayLike(value)
		{
			return (
				!Type.isNil(value)
				&& !Type.isFunction(value)
				&& value.length > -1
				&& value.length <= Number.MAX_SAFE_INTEGER
			);
		}

		/**
		 * Checks that value is Date
		 * @param value
		 * @return {boolean}
		 */
		static isDate(value)
		{
			return Type.isObjectLike(value) && getTag(value) === '[object Date]';
		}

		/**
		 * Checks that value is Map
		 * @param value
		 * @return {boolean}
		 */
		static isMap(value)
		{
			return Type.isObjectLike(value) && getTag(value) === '[object Map]';
		}

		/**
		 * Checks that value is Set
		 * @param value
		 * @return {boolean}
		 */
		static isSet(value)
		{
			return Type.isObjectLike(value) && getTag(value) === '[object Set]';
		}

		/**
		 * Checks that value is WeakMap
		 * @param value
		 * @return {boolean}
		 */
		static isWeakMap(value)
		{
			return Type.isObjectLike(value) && getTag(value) === '[object WeakMap]';
		}

		/**
		 * Checks that value is WeakSet
		 * @param value
		 * @return {boolean}
		 */
		static isWeakSet(value)
		{
			return Type.isObjectLike(value) && getTag(value) === '[object WeakSet]';
		}

		/**
		 * Checks that value is prototype
		 * @param value
		 * @return {boolean}
		 */
		static isPrototype(value)
		{
			return (
				(((typeof (value && value.constructor) === 'function') && value.constructor.prototype) || Object.prototype) === value
			);
		}

		/**
		 * Checks that value is regexp
		 * @param value
		 * @return {boolean}
		 */
		static isRegExp(value)
		{
			return Type.isObjectLike(value) && getTag(value) === '[object RegExp]';
		}

		/**
		 * Checks that value is null
		 * @param value
		 * @return {boolean}
		 */
		static isNull(value)
		{
			return value === null;
		}

		/**
		 * Checks that value is undefined
		 * @param value
		 * @return {boolean}
		 */
		static isUndefined(value)
		{
			return typeof value === 'undefined';
		}

		/**
		 * Checks that value is ArrayBuffer
		 * @param value
		 * @return {boolean}
		 */
		static isArrayBuffer(value)
		{
			return Type.isObjectLike(value) && getTag(value) === '[object ArrayBuffer]';
		}

		/**
		 * Checks that value is typed array
		 * @param value
		 * @return {boolean}
		 */
		static isTypedArray(value)
		{
			const regExpTypedTag = (
				/^\[object (?:Float(?:32|64)|(?:Int|Uint)(?:8|16|32)|Uint8Clamped)]$/
			);
			return Type.isObjectLike(value) && regExpTypedTag.test(getTag(value));
		}

		/**
		 * Checks that value is Blob
		 * @param value
		 * @return {boolean}
		 */
		static isBlob(value)
		{
			return (
				Type.isObjectLike(value)
				&& Type.isNumber(value.size)
				&& Type.isString(value.type)
				&& Type.isFunction(value.slice)
			);
		}

		/**
		 * Checks that value is File
		 * @param value
		 * @return {boolean}
		 */
		static isFile(value)
		{
			return (
				Type.isBlob(value)
				&& Type.isString(value.name)
				&& (Type.isNumber(value.lastModified) || Type.isObjectLike(value.lastModifiedDate))
			);
		}
	}

	module.exports = {
		Type,
	};
});
