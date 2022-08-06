/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-typeof */

/**
 * @module im/messenger/lib/core/type
 */
jn.define('im/messenger/lib/core/type', (require, exports, module) => {

	function getTag(value)
	{
		return Object.prototype.toString.call(value);
	}

	const objectCtorString = Function.prototype.toString.call(Object);

	/**
	 * @memberOf BX
	 */
	class Type
	{
		/**
		 * Checks that value is string
		 * @param value
		 * @return {boolean}
		 */
		isString(value)
		{
			return typeof value === 'string';
		}

		/**
		 * Returns true if a value is not empty string
		 * @param value
		 * @returns {boolean}
		 */
		isStringFilled(value)
		{
			return this.isString(value) && value !== '';
		}

		/**
		 * Checks that value is function
		 * @param value
		 * @return {boolean}
		 */
		isFunction(value)
		{
			return typeof value === 'function';
		}

		/**
		 * Checks that value is object
		 * @param value
		 * @return {boolean}
		 */
		isObject(value)
		{
			return !!value && (typeof value === 'object' || typeof value === 'function');
		}

		/**
		 * Checks that value is object like
		 * @param value
		 * @return {boolean}
		 */
		isObjectLike(value)
		{
			return !!value && typeof value === 'object';
		}

		/**
		 * Checks that value is plain object
		 * @param value
		 * @return {boolean}
		 */
		isPlainObject(value)
		{
			if (!this.isObjectLike(value) || getTag(value) !== '[object Object]')
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
		isBoolean(value)
		{
			return value === true || value === false;
		}

		/**
		 * Checks that value is number
		 * @param value
		 * @return {boolean}
		 */
		isNumber(value)
		{
			return !Number.isNaN(value) && typeof value === 'number';
		}

		/**
		 * Checks that value is integer
		 * @param value
		 * @return {boolean}
		 */
		isInteger(value)
		{
			return this.isNumber(value) && (value % 1) === 0;
		}

		/**
		 * Checks that value is float
		 * @param value
		 * @return {boolean}
		 */
		isFloat(value)
		{
			return this.isNumber(value) && !this.isInteger(value);
		}

		/**
		 * Checks that value is nil
		 * @param value
		 * @return {boolean}
		 */
		isNil(value)
		{
			return value === null || value === undefined;
		}

		/**
		 * Checks that value is array
		 * @param value
		 * @return {boolean}
		 */
		isArray(value)
		{
			return !this.isNil(value) && Array.isArray(value);
		}

		/**
		 * Returns true if a value is an array and it has at least one element
		 * @param value
		 * @returns {boolean}
		 */
		isArrayFilled(value)
		{
			return this.isArray(value) && value.length > 0;
		}

		/**
		 * Checks that value is array like
		 * @param value
		 * @return {boolean}
		 */
		isArrayLike(value)
		{
			return (
				!this.isNil(value)
				&& !this.isFunction(value)
				&& value.length > -1
				&& value.length <= Number.MAX_SAFE_INTEGER
			);
		}

		/**
		 * Checks that value is Date
		 * @param value
		 * @return {boolean}
		 */
		isDate(value)
		{
			return this.isObjectLike(value) && getTag(value) === '[object Date]';
		}

		/**
		 * Checks that value is Map
		 * @param value
		 * @return {boolean}
		 */
		isMap(value)
		{
			return this.isObjectLike(value) && getTag(value) === '[object Map]';
		}

		/**
		 * Checks that value is Set
		 * @param value
		 * @return {boolean}
		 */
		isSet(value)
		{
			return this.isObjectLike(value) && getTag(value) === '[object Set]';
		}

		/**
		 * Checks that value is WeakMap
		 * @param value
		 * @return {boolean}
		 */
		isWeakMap(value)
		{
			return this.isObjectLike(value) && getTag(value) === '[object WeakMap]';
		}

		/**
		 * Checks that value is WeakSet
		 * @param value
		 * @return {boolean}
		 */
		isWeakSet(value)
		{
			return this.isObjectLike(value) && getTag(value) === '[object WeakSet]';
		}

		/**
		 * Checks that value is prototype
		 * @param value
		 * @return {boolean}
		 */
		isPrototype(value)
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
		isRegExp(value)
		{
			return this.isObjectLike(value) && getTag(value) === '[object RegExp]';
		}

		/**
		 * Checks that value is null
		 * @param value
		 * @return {boolean}
		 */
		isNull(value)
		{
			return value === null;
		}

		/**
		 * Checks that value is undefined
		 * @param value
		 * @return {boolean}
		 */
		isUndefined(value)
		{
			return typeof value === 'undefined';
		}

		/**
		 * Checks that value is ArrayBuffer
		 * @param value
		 * @return {boolean}
		 */
		isArrayBuffer(value)
		{
			return this.isObjectLike(value) && getTag(value) === '[object ArrayBuffer]';
		}

		/**
		 * Checks that value is typed array
		 * @param value
		 * @return {boolean}
		 */
		isTypedArray(value)
		{
			const regExpTypedTag = (
				/^\[object (?:Float(?:32|64)|(?:Int|Uint)(?:8|16|32)|Uint8Clamped)]$/
			);
			return this.isObjectLike(value) && regExpTypedTag.test(getTag(value));
		}

		/**
		 * Checks that value is Blob
		 * @param value
		 * @return {boolean}
		 */
		isBlob(value)
		{
			return (
				this.isObjectLike(value)
				&& this.isNumber(value.size)
				&& this.isString(value.type)
				&& this.isFunction(value.slice)
			);
		}

		/**
		 * Checks that value is File
		 * @param value
		 * @return {boolean}
		 */
		isFile(value)
		{
			return (
				this.isBlob(value)
				&& this.isString(value.name)
				&& (this.isNumber(value.lastModified) || this.isObjectLike(value.lastModifiedDate))
			);
		}
	}

	module.exports = {
		Type: new Type(),
	};
});
