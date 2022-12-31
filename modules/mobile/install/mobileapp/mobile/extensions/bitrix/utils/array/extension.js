(() => {
	const { mergeImmutable } = jn.require('utils/object');

	/**
	 * Gets the last element of array
	 * @param array
	 */
	function last(array)
	{
		return Array.isArray(array) ? array[array.length - 1] : null;
	}

	function unique(array)
	{
		return [...new Set(array)];
	}

	function uniqBy(arr, predicate)
	{
		const cb = typeof predicate === 'function' ? predicate : (o) => o[predicate];

		return [...arr.reduce((map, item) => {
			const key = (item === null || item === undefined) ? item : cb(item);

			if (!map.has(key))
			{
				map.set(key, item);
			}

			return map;

		}, new Map()).values()];
	}

	function mergeBy(arr, value, predicate)
	{
		const changeArr = [...arr];
		const foundIndex = changeArr.findIndex((item) => item[predicate] === value[predicate]);
		if (foundIndex !== -1)
		{
			changeArr[foundIndex] = mergeImmutable(changeArr[foundIndex], value);
		}
		else
		{
			changeArr.push(value);
		}

		return changeArr;
	}

	/**
	 * @class ArrayUtils
	 * @deprecated Please import specific utilities directly, using jn.require()
	 */
	class ArrayUtils
	{
		static last(array)
		{
			return last(array);
		}

		static uniqBy(arr, predicate)
		{
			return uniqBy(arr, predicate);
		}
	}

	jnexport(ArrayUtils);

	/**
	 * @module utils/array
	 */
	jn.define('utils/array', (require, exports, module) => {

		module.exports = {
			last,
			unique,
			uniqBy,
			mergeBy,
		};

	});

})();