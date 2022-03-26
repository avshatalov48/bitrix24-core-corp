(() => {

	/**
	 * @class ObjectUtils
	 */
	class ObjectUtils
	{
		/**
		 * Creates recursive clone of source object
		 * @param {object} source
		 * @returns {object|null}
		 */
		static clone(source)
		{
			let newObject = {};
			if (source === null)
			{
				return null;
			}

			if (typeof source === 'object')
			{
				if (BX.type.isArray(source))
				{
					newObject = [];
					for (let i = 0, l = source.length; i < l; i++)
					{
						if (typeof source[i] === 'object')
						{
							newObject[i] = ObjectUtils.clone(source[i]);
						}
						else
						{
							newObject[i] = source[i];
						}
					}
				}
				else if (source instanceof Map)
				{
					newObject = new Map(source);
				}
				else if (source instanceof Set)
				{
					newObject = new Set(source);
				}
				else
				{
					newObject = {};
					if (source.constructor)
					{
						if (BX.type.isDate(source))
						{
							newObject = new Date(source);
						}
						else
						{
							newObject = new source.constructor();
						}
					}

					for (let i in source)
					{
						if (!source.hasOwnProperty(i))
						{
							continue;
						}
						if (typeof source[i] === 'object')
						{
							newObject[i] = ObjectUtils.clone(source[i]);
						}
						else
						{
							newObject[i] = source[i];
						}
					}
				}
			}
			else
			{
				newObject = source;
			}

			return newObject;
		}

		/**
		 * Recursively merges sources objects into destination object.
		 * This method mutates object.
		 * @param {object} object
		 * @param {object} sources
		 * @returns {object}
		 */
		static merge(object, ...sources)
		{
			sources.map(source => {
				for (let name in source)
				{
					if (!source.hasOwnProperty(name))
					{
						continue;
					}
					if (BX.type.isPlainObject(source[name]))
					{
						if (!BX.type.isPlainObject(object[name]))
						{
							object[name] = {};
						}
						ObjectUtils.merge(object[name], source[name]);
					}
					else
					{
						object[name] = source[name];
					}
				}
			});

			return object;
		}

		/**
		 * Sets the value at path of object. If a portion of path doesn't exist, it's created.
		 * Path must be string with dots as divider.
		 * Mutates object and returns it.
		 * @param {object} object
		 * @param {string} path
		 * @param {*} value
		 * @returns {object}
		 */
		static set(object, path, value)
		{
			path = path.split('.');
			const len = path.length;
			let schema = object;

			for (let i = 0; i < len - 1; i++)
			{
				let elem = path[i];
				if (!BX.type.isPlainObject(schema[elem]))
				{
					schema[elem] = {};
				}
				schema = schema[elem];
			}

			schema[path[len - 1]] = value;
			return object;
		}

		/**
		 * Gets the value at path of object. If the resolved value is undefined, the defaultValue is returned in its place.
		 * Path must be string with dots as divider.
		 * @param {object} object
		 * @param {string} path
		 * @param {*} defaultValue
		 * @returns {*}
		 */
		static get(object, path, defaultValue)
		{
			let schema = object;

			path = path.split('.');

			for (let i = 0; i < path.length; i++)
			{
				let elem = path[i];
				if (schema[elem])
				{
					schema = schema[elem];
				}
				else
				{
					return defaultValue;
				}
			}

			return schema || defaultValue;
		}

		/**
		 * Performs a deep comparison between two values to determine if they are equivalent.
		 * @param {*} value
		 * @param {*} other
		 * @returns {boolean}
		 */
		static isEqual(value, other)
		{
			if (value === other)
			{
				return true;
			}

			const valueIsObject = ObjectUtils.isObjectLike(value);
			const otherIsObject = ObjectUtils.isObjectLike(other);

			if (value == null || other == null || (!valueIsObject && !otherIsObject))
			{
				return value !== value && other !== other;
			}

			const bothObjects = valueIsObject && otherIsObject;
			const bothArrays = ObjectUtils.isArray(value) && ObjectUtils.isArray(other);
			const oneIsArray = ObjectUtils.isArray(value) || ObjectUtils.isArray(other);

			if (bothObjects)
			{
				if (bothArrays)
				{
					return arrayEquals(value, other);
				}
				else if (oneIsArray)
				{
					return false;
				}
				else
				{
					return objectEquals(value, other);
				}
			}

			return false;
		}

		/**
		 * @param {*} value
		 * @returns {boolean}
		 */
		static isObjectLike(value)
		{
			return value != null && typeof value === 'object';
		}

		/**
		 * @param {*} value
		 * @returns {boolean}
		 */
		static isArray(value)
		{
			return Array.isArray(value);
		}

		/**
		 * Checks if value is an empty object, collection, map, or set.
		 * Objects are considered empty if they have no own enumerable string keyed properties.
		 * Arrays and strings are considered empty if they have a length of 0.
		 * Maps and Sets are considered empty if they have a size of 0.
		 * @param {object|string} value
		 * @returns {boolean}
		 */
		static isEmpty(value)
		{
			if (value == null) return true;

			if (value instanceof Map || value instanceof Set)
			{
				return !value.size;
			}

			if (ObjectUtils.isArray(value) || typeof value === 'string')
			{
				return !value.length;
			}

			return !Object.keys(value).length;
		}
	}

	/**
	 * @private
	 * @param {array} a
	 * @param {array} b
	 * @returns {boolean}
	 */
	function arrayEquals(a, b)
	{
		if (a === b) return true;
		if (a.length !== b.length) return false;

		for (let i = 0; i < a.length; i++)
		{
			if (ObjectUtils.isArray(a[i]) && ObjectUtils.isArray(b[i]))
			{
				if (!arrayEquals(a[i], b[i]))
				{
					return false;
				}
			}
			else if (ObjectUtils.isObjectLike(a[i]) && ObjectUtils.isObjectLike(b[i]))
			{
				if (!objectEquals(a[i], b[i]))
				{
					return false;
				}
			}
			else if (a[i] !== b[i])
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @private
	 * @param {object} obj1
	 * @param {object} obj2
	 * @returns {boolean}
	 */
	function objectEquals(obj1, obj2)
	{
		const props1 = Object.keys(obj1);
		const props2 = Object.keys(obj2);

		if (props1.length !== props2.length)
		{
			return false;
		}

		const objLength = props1.length;
		let index = objLength;
		let key;
		while (index--)
		{
			key = props1[index];
			if (!props2.includes(key))
			{
				return false;
			}
		}

		while (++index < objLength)
		{
			key = props1[index];
			let value1 = obj1[key];
			let value2 = obj2[key];

			if (!ObjectUtils.isEqual(value1, value2))
			{
				return false;
			}
		}

		return true;
	}

	Object.toMD5 = function (object)
	{
		let result = null;
		try
		{
			let string = JSON.stringify(object);
			result = HashUtils.md5(string);
		}
		catch (e)
		{

		}

		return result;
	};

	Object.tryJSONParse = function (object)
	{
		let result = null;
		if (typeof object == "string")
		{
			try
			{
				result = JSON.parse(object);
			}
			catch (e)
			{
				console.error("Parse JSON error", e);
			}
		}

		return result;
	};

	jnexport(ObjectUtils);

})();