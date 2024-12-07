/**
 * @module utils/enums/base
 */
jn.define('utils/enums/base', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { isEqual } = require('utils/object');

	/**
	 * @class BaseEnum
	 * @template TEnumType
	 */
	class BaseEnum
	{
		static enums = null;
		static enumKeys = null;
		static enumValues = null;

		/**
		 * @param {String} name
		 * @param {any} value
		 */
		constructor(name, value)
		{
			this.name = name;
			this.value = value;
		}

		/**
		 * @public
		 * @param {string} name
		 * @return {TEnumType}
		 */
		static getEnum(name)
		{
			return this[name] || null;
		}

		/**
		 * @public
		 * @return {TEnumType[]}
		 */
		static getEnums()
		{
			if (!this.enums)
			{
				this.enums = [];

				// eslint-disable-next-line no-restricted-syntax
				for (const key in this)
				{
					if (this[key] instanceof BaseEnum)
					{
						this.enums.push(this[key]);
					}
				}
			}

			return this.enums;
		}

		/**
		 * @public
		 * @return {String[]}
		 */
		static getKeys()
		{
			if (!this.enumKeys)
			{
				this.enumKeys = this.getEnums().map((enumType) => enumType.getName());
			}

			return this.enumKeys;
		}

		/**
		 * @public
		 * @return {Array<any>}
		 */
		static getValues()
		{
			if (!this.enumValues)
			{
				this.enumValues = this.getEnums().map((enumType) => enumType.getValue());
			}

			return this.enumValues;
		}

		/**
		 * @public
		 * @return {void}
		 */
		static forEach(callback)
		{
			this.getEntries().forEach(([name, value], i) => {
				callback({ name, value }, i);
			});
		}

		/**
		 * @public
		 * @return {[String, any][]}
		 */
		static getEntries()
		{
			const keys = this.getKeys();

			return keys.map((key, i) => [key, this[key].getValue()]);
		}

		static [Symbol.iterator]()
		{
			return this.getEntries()[Symbol.iterator]();
		}

		/**
		 * @public
		 * @param {any} value
		 * @return Boolean
		 */
		static isDefined(value)
		{
			return this.getValues().includes(value);
		}

		/**
		 * @public
		 * @static
		 * @param {TEnumType} enumType
		 * @param {TEnumType=} defaultEnum
		 * @return {TEnumType}
		 */
		static resolve(enumType, defaultEnum)
		{
			const type = isNil(enumType) ? defaultEnum : enumType;

			if (!this.has(type))
			{
				throw new TypeError(`Invalid or unexpected enumerable type ${enumType}`);
			}

			return type;
		}

		/**
		 * @public
		 * @param enumType
		 * @return {boolean}
		 */
		static has(enumType)
		{
			return enumType instanceof this;
		}

		/**
		 * @public
		 * @param {BaseEnum} enumType
		 * @return Boolean
		 */
		equal(enumType)
		{
			return isEqual(this.getValue(), enumType.getValue()) && this.constructor.name === enumType.constructor.name;
		}

		/**
		 * @public
		 */
		getName()
		{
			return this.name;
		}

		/**
		 * @public
		 */
		getValue()
		{
			return this.value;
		}

		/**
		 * @public
		 */
		toPrimitive()
		{
			return this.getValue();
		}

		/**
		 * @public
		 */
		toString()
		{
			return String(this.getValue());
		}

		/**
		 * @public
		 */
		toNumber()
		{
			return Number(this.getValue());
		}
	}

	module.exports = { BaseEnum };
});
