/**
 * @module utils/enums/base
 */
jn.define('utils/enums/base', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { isEqual } = require('utils/object');

	const cache = new WeakMap();
	const Cacheable = Object.freeze({
		ENUMS: 'enums',
		KEYS: 'keys',
		VALUES: 'values',
	});

	/**
	 * @class BaseEnum
	 * @template TEnumType
	 */
	class BaseEnum
	{
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
		 * @param {string} property
		 * @param {function(): any} factory
		 * @private
		 */
		static getCached(property, factory)
		{
			if (!Object.values(Cacheable).includes(property))
			{
				throw new Error(`The ${property} property is not cacheable`);
			}

			const cached = cache.get(this);
			if (cached && cached[property])
			{
				return cached[property];
			}

			const value = factory();
			const cacheEntry = cached || {};

			cacheEntry[property] = value;
			cache.set(this, cacheEntry);

			return value;
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
			return this.getCached(Cacheable.ENUMS, () => {
				const enums = [];
				// eslint-disable-next-line no-restricted-syntax
				for (const key in this)
				{
					if (this[key] instanceof BaseEnum)
					{
						enums.push(this[key]);
					}
				}

				return Object.freeze(enums);
			});
		}

		/**
		 * @public
		 * @return {String[]}
		 */
		static getKeys()
		{
			return this.getCached(Cacheable.KEYS, () => {
				return this.getEnums().map((enumType) => enumType.getName());
			});
		}

		/**
		 * @public
		 * @return {Array<any>}
		 */
		static getValues()
		{
			return this.getCached(Cacheable.VALUES, () => {
				return this.getEnums().map((enumType) => enumType.getValue());
			});
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
		 * @description Use this method while exporting enum, to make it immutable
		 * @returns {this}
		 */
		static export()
		{
			return Object.freeze(this);
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
