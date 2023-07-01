(() => {

	const { md5 } = jn.require('utils/hash');
	const { useCallback } = jn.require('utils/function');

	/**
	 * Creates recursive clone of source object
	 * @template T
	 * @param {T} source
	 * @returns {T}
	 */
	function clone(source)
	{
		let newObject = {};
		if (source === null)
		{
			return null;
		}

		if (typeof source === 'object')
		{
			if (isArray(source))
			{
				newObject = [];
				for (let i = 0, l = source.length; i < l; i++)
				{
					if (typeof source[i] === 'object')
					{
						newObject[i] = clone(source[i]);
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

				for (const i in source)
				{
					if (!source.hasOwnProperty(i))
					{
						continue;
					}
					if (typeof source[i] === 'object')
					{
						newObject[i] = clone(source[i]);
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
	function merge(object, ...sources)
	{
		sources.map(source => {
			for (const name in source)
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
					merge(object[name], source[name]);
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
	 * Recursively merges sources objects into new object and returns it.
	 * This method keeps sources unchanged and always return brand new object.
	 * @param {object} sources
	 * @returns {object}
	 */
	function mergeImmutable(...sources)
	{
		return merge({}, ...sources);
	}

	/**
	 * Sets the value at path of object. If a portion of path doesn't exist, it's created.
	 * Path must be string with dots as divider.
	 * Mutates object and returns it.
	 * @param {object} object
	 * @param {string | string[]} path
	 * @param {*} value
	 * @returns {object}
	 */
	function set(object, path, value)
	{
		path = Array.isArray(path) ? path : path.split('.');
		const len = path.length;
		let schema = object;

		for (let i = 0; i < len - 1; i++)
		{
			const elem = path[i];
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
	 * Checks if an object has a given property at path of object.
	 * Path must be an array or a string with dots as divider.
	 * @param {object} object
	 * @param {string | string[]} path
	 * @returns {boolean}
	 */
	function has(object, path)
	{
		let schema = object;

		path = Array.isArray(path) ? path : path.split('.');

		for (let i = 0; i < path.length; i++)
		{
			const elem = path[i];
			if (schema && typeof schema === 'object' && elem in schema)
			{
				schema = schema[elem];
			}
			else
			{
				return false;
			}
		}

		return typeof schema !== 'undefined';
	}

	/**
	 * Gets the value at path of object. If the resolved value is undefined, the defaultValue is returned in its place.
	 * Path must be string with dots as divider.
	 * @param {object} object
	 * @param {string | string[]} path
	 * @param {*} defaultValue
	 * @returns {*}
	 */
	function get(object, path, defaultValue)
	{
		let schema = object;

		path = Array.isArray(path) ? path : path.split('.');

		for (let i = 0; i < path.length; i++)
		{
			const elem = path[i];
			if (schema && typeof schema === 'object' && elem in schema)
			{
				schema = schema[elem];
			}
			else
			{
				return defaultValue;
			}
		}

		return typeof schema === 'undefined' ? defaultValue : schema;
	}

	/**
	 * Performs a deep comparison between two values to determine if they are equivalent.
	 * @param {*} value
	 * @param {*} other
	 * @returns {boolean}
	 */
	function isEqual(value, other)
	{
		if (Object.is(value, other))
		{
			return true;
		}

		const bothFunctions = isFunction(value) && isFunction(other);
		if (bothFunctions)
		{
			return functionEquals(value, other);
		}

		const valueIsObject = isObjectLike(value);
		const otherIsObject = isObjectLike(other);

		if (value == null || other == null || (!valueIsObject && !otherIsObject))
		{
			return value !== value && other !== other;
		}

		const bothObjects = valueIsObject && otherIsObject;
		if (bothObjects)
		{
			const bothArrays = isArray(value) && isArray(other);
			if (bothArrays)
			{
				return arrayEquals(value, other);
			}

			const bothMaps = isMap(value) && isMap(other);
			if (bothMaps)
			{
				return mapsEquals(value, other);
			}

			const bothSets = isSet(value) && isSet(other);
			if (bothSets)
			{
				return setsEquals(value, other);
			}

			if (value instanceof Date && other instanceof Date)
			{
				return value.getTime() === other.getTime();
			}

			const oneIsArray = isArray(value) || isArray(other);
			const oneIsMap = isMap(value) || isMap(other);
			const oneIsSet = isSet(value) || isSet(other);
			const oneIsDate = value instanceof Date || other instanceof Date;
			if (oneIsArray || oneIsMap || oneIsSet || oneIsDate)
			{
				return false;
			}

			return objectEquals(value, other);
		}

		return false;
	}

	/**
	 * @param {*} value
	 * @returns {boolean}
	 */
	function isObjectLike(value)
	{
		return value != null && typeof value === 'object';
	}

	/**
	 * @deprecated Just use Array.isArray() instead of adding unnecessary dependency.
	 * @param {*} value
	 * @returns {boolean}
	 */
	function isArray(value)
	{
		return Array.isArray(value);
	}

	/**
	 * @param {*} value
	 * @returns {boolean}
	 */
	function isMap(value)
	{
		return (value instanceof Map);
	}

	/**
	 * @param {*} value
	 * @returns {boolean}
	 */
	function isSet(value)
	{
		return (value instanceof Set);
	}

	/**
	 * @param {*} value
	 * @returns {boolean}
	 */
	function isFunction(value)
	{
		return (value instanceof Function);
	}

	/**
	 * Checks if value is an empty object, collection, map, or set.
	 * Objects are considered empty if they have no own enumerable string keyed properties.
	 * Arrays and strings are considered empty if they have a length of 0.
	 * Maps and Sets are considered empty if they have a size of 0.
	 * @param {object|string} value
	 * @returns {boolean}
	 */
	function isEmpty(value)
	{
		if (value == null)
		{
			return true;
		}

		if (value instanceof Map || value instanceof Set)
		{
			return !value.size;
		}

		if (isArray(value) || typeof value === 'string')
		{
			return !value.length;
		}

		return !Object.keys(value).length;
	}

	/**
	 * @private
	 * @param {array} a
	 * @param {array} b
	 * @returns {boolean}
	 */
	function arrayEquals(a, b)
	{
		if (a === b)
		{
			return true;
		}
		if (a.length !== b.length)
		{
			return false;
		}

		for (let i = 0; i < a.length; i++)
		{
			if (isArray(a[i]) && isArray(b[i]))
			{
				if (!arrayEquals(a[i], b[i]))
				{
					return false;
				}
			}
			else if (isObjectLike(a[i]) && isObjectLike(b[i]))
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
			const value1 = obj1[key];
			const value2 = obj2[key];

			if (!isEqual(value1, value2))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @private
	 * @param {Map} map1
	 * @param {Map} map2
	 * @returns {boolean}
	 */
	function mapsEquals(map1, map2)
	{
		if (map1.size !== map2.size)
		{
			return false;
		}

		for (const [key, value] of map1)
		{
			if (!map2.has(key))
			{
				return false;
			}

			const testVal = map2.get(key);
			if (!isEqual(testVal, value))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @private
	 * @param {Set} set1
	 * @param {Set} set2
	 * @returns {boolean}
	 */
	function setsEquals(set1, set2)
	{
		if (set1.size !== set2.size)
		{
			return false;
		}

		for (const value of set1)
		{
			if (!set2.has(value))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @private
	 * @param {Function} function1
	 * @param {Function} function2
	 * @returns {boolean}
	 */
	function functionEquals(function1, function2)
	{
		const { hashIdSymbol } = useCallback;

		if (function1.hasOwnProperty(hashIdSymbol) && function2.hasOwnProperty(hashIdSymbol))
		{
			return function1[hashIdSymbol] === function2[hashIdSymbol];
		}

		return Object.is(function1, function2);
	}

	/**
	 * @class ObjectUtils
	 * @deprecated Please import specific utilities directly, using jn.require()
	 */
	class ObjectUtils
	{
		static clone(source)
		{
			return clone(source);
		}

		static merge(object, ...sources)
		{
			return merge(object, ...sources);
		}

		static mergeImmutable(...sources)
		{
			return merge({}, ...sources);
		}

		static set(object, path, value)
		{
			return set(object, path, value);
		}

		static has(object, path)
		{
			return has(object, path);
		}

		static get(object, path, defaultValue)
		{
			return get(object, path, defaultValue);
		}

		static isEqual(value, other)
		{
			return isEqual(value, other);
		}

		static isObjectLike(value)
		{
			return isObjectLike(value);
		}

		static isArray(value)
		{
			return isArray(value);
		}

		static isMap(value)
		{
			return isMap(value);
		}

		static isSet(value)
		{
			return isSet(value);
		}

		static isEmpty(value)
		{
			return isEmpty(value);
		}
	}

	Object.toMD5 = function(object) {
		let result = null;
		try
		{
			const string = JSON.stringify(object);
			result = md5(string);
		}
		catch (e)
		{

		}

		return result;
	};

	Object.tryJSONParse = function(object) {
		let result = null;
		if (typeof object === 'string')
		{
			try
			{
				result = JSON.parse(object);
			}
			catch (e)
			{
				console.error('Parse JSON error', e);
			}
		}

		return result;
	};

	jnexport(ObjectUtils);

	/**
	 * @module utils/object
	 */
	jn.define('utils/object', (require, exports, module) => {

		module.exports = {
			clone,
			merge,
			mergeImmutable,
			set,
			has,
			get,
			isEqual,
			isObjectLike,
			isArray,
			isMap,
			isSet,
			isEmpty,
			isFunction
		};

	});

})();
