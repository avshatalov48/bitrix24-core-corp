/**
 * @module layout/ui/stateful-list/type-generator
 */
jn.define('layout/ui/stateful-list/type-generator', (require, exports, module) => {
	/**
	 * @class TypeGenerator
	 */
	class TypeGenerator
	{
		static get generators()
		{
			return {
				byAllProperties: 'fullGenerator',
				bySelectedProperties: 'selectiveGenerator',
			};
		}

		constructor(options = {})
		{
			/**
			 * @private
			 * @type {string[]}
			 */
			this.groupsList = options.groupsList;
			/**
			 * @private
			 * @type {string[]}
			 */
			this.properties = options.properties;
			/**
			 * @private
			 * @type {object}
			 */
			this.propertiesCallbacks = options.propertiesCallbacks;
			/**
			 * @private
			 * @type {function}
			 */
			this.generator = this[TypeGenerator.generators.byAllProperties];
			/**
			 * @private
			 * @type {string}
			 */
			this.result = '';
		}

		/**
		 * @public
		 * @returns {string}
		 */
		generate(item) {
			this.result = '';

			this.createGroups(item);

			this.groups.forEach((group) => {
				this.generator(item, group);
			});

			return this.getResult();
		}

		/**
		 * @public
		 * @param {string} generator
		 */
		setGenerator(generator)
		{
			if (!this[generator])
			{
				return;
			}
			this.generator = this[generator];
		}

		/**
		 * @public
		 * @param {string[]} groupsList
		 */
		setGroupsList(groupsList)
		{
			if (!Array.isArray(groupsList))
			{
				this.groupsList = [];

				return;
			}

			this.groupsList = groupsList;
		}

		/**
		 * @public
		 * @param {object[]} groups
		 */
		setGroups(groups)
		{
			if (!Array.isArray(groups))
			{
				return;
			}

			this.groups = [];
			groups.forEach((group) => {
				if (typeof group === 'object')
				{
					this.groups.push(group);
				}
			});
		}

		/**
		 * @public
		 * @param {string[]} properties
		 */
		setProperties(properties)
		{
			this.properties = properties;
		}

		/**
		 * @public
		 * @param {object} callbacks
		 */
		setPropertiesCallbacks(callbacks)
		{
			this.propertiesCallbacks = callbacks;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getResult()
		{
			return this.getHashCode(this.result);
		}

		/**
		 * @private
		 */
		getKeyStatus(item, key, value)
		{
			let keyStatus = null;

			if (this.propertiesCallbacks && this.propertiesCallbacks[key])
			{
				keyStatus = this.propertiesCallbacks[key](value, item);
			}
			else
			{
				keyStatus = this.getDefaultKeyStatus(key, value);
			}

			return `${key}:${Number(keyStatus)}-`;
		}

		/**
		 * @private
		 */
		fullGenerator(item, group)
		{
			if (!group || !item)
			{
				return;
			}

			for (const key of Object.keys(group))
			{
				this.result += this.getKeyStatus(item, key, group[key]);
			}
		}

		/**
		 * @private
		 */
		selectiveGenerator(item, group)
		{
			if (!group || typeof group !== 'object' || !item)
			{
				return;
			}

			for (const key of this.properties)
			{
				if (!(key in group))
				{
					continue;
				}

				this.result += this.getKeyStatus(item, key, group[key]);
			}
		}

		/**
		 * @private
		 * @param {string} key
		 * @param {any} value
		 * @returns {string}
		 */
		getDefaultKeyStatus(key, value)
		{
			let keyStatus = null;
			if (Array.isArray(value))
			{
				keyStatus = value.length;
			}
			else if (BX.type.isPlainObject(value))
			{
				keyStatus = Object.keys(value).length;
			}
			else if (Number(value))
			{
				keyStatus = value;
			}
			else
			{
				keyStatus = Boolean(value);
			}

			return keyStatus;
		}

		/**
		 * @private
		 */
		createGroups(item)
		{
			if (!this.groupsList || this.groupsList.length === 0)
			{
				this.groups = [item];

				return;
			}

			if (this.groupsList.includes('this'))
			{
				this.groups = [item];
				this.groupsList.splice(this.groupsList.indexOf('this'), 1);
			}

			this.groups = this.groupsList.map((groupName) => {
				const properties = groupName.split('.');

				return properties.reduce((group, prop) => (group ? group[prop] : null), item);
			});

			this.groups = this.groups.filter(Boolean);
		}

		/**
		 * @private
		 * @param {string} str
		 * @returns {string}
		 */
		getHashCode(str)
		{
			let h = 0;
			for (let i = 0; i < str.length; i++)
			{
				h = Math.trunc(Math.imul(31, h) + str.codePointAt(i));
			}

			return String(h);
		}
	}

	module.exports = { TypeGenerator };
});
