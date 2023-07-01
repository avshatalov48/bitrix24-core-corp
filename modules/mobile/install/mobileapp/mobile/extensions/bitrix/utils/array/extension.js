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

	/**
	 * Creates an array of elements, sorted in ascending order by
	 * the results of running each element in a collection thru each iteratee.
	 * @param collection
	 * @param predicate
	 * @return {array}
	 */
	function sortBy(collection, predicate)
	{
		const sortBy = (key) => (a, b) => (a[key] > b[key]) ? 1 : ((b[key] > a[key]) ? -1 : 0);

		return collection.concat().sort(sortBy(predicate));
	}

	/**
	 * Merges two arrays by predicate. If value is not found in array, it could be added to the end of array.
	 * @param {array} arr
	 * @param {*} value
	 * @param predicate
	 * @param {boolean} addIfNotFound
	 * @return {*[]}
	 */
	function mergeBy(arr, value, predicate, addIfNotFound = true)
	{
		const changeArr = [...arr];
		const foundIndex = changeArr.findIndex((item) => item[predicate] === value[predicate]);
		if (foundIndex !== -1)
		{
			changeArr[foundIndex] = mergeImmutable(changeArr[foundIndex], value);
		}
		else if (addIfNotFound)
		{
			changeArr.push(value);
		}

		return changeArr;
	}

	/**
	 * Replaces value in array by predicate. If value is not found in array, it could be added to the end of array.
	 * @param {array} arr
	 * @param {*} value
	 * @param predicate
	 * @param {boolean} addIfNotFound
	 * @return {*[]}
	 */
	function replaceBy(arr, value, predicate, addIfNotFound = true)
	{
		const changeArr = [...arr];
		const foundIndex = changeArr.findIndex((item) => item[predicate] === value[predicate]);
		if (foundIndex !== -1)
		{
			changeArr[foundIndex] = value;
		}
		else if (addIfNotFound)
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
			sortBy,
			replaceBy,
		};

	});

})();