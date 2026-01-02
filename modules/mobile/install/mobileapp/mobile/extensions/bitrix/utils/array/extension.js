/**
 * @module utils/array
 */
jn.define('utils/array', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');

	/**
	 * @template T
	 * @param {Array<T>} array
	 * @param {number} size
	 * @returns {Array<T>}
	 */
	function chunk(array, size)
	{
		if (!Array.isArray(array))
		{
			throw new TypeError('The first argument must be an array');
		}

		if (typeof size !== 'number' || size <= 0)
		{
			throw new TypeError('The second argument must be a positive number');
		}

		const result = [];
		for (let i = 0; i < array.length; i += size)
		{
			result.push(array.slice(i, i + size));
		}

		return result;
	}

	/**
	 * Gets the last element of array
	 * @template T
	 * @param {Array<T>} array
	 * @return {T | null}
	 */
	function last(array)
	{
		return Array.isArray(array) ? array[array.length - 1] : null;
	}

	/**
	 * @template T
	 * @param {Array<T>} array
	 * @return {Array<T>}
	 */
	function unique(array)
	{
		return [...new Set(array)];
	}

	/**
	 * Returns a new array with unique items by predicate
	 * @template T
	 * @param {Array<T>} arr
	 * @param {string|function} predicate
	 * @returns {Array<T>}
	 */
	function uniqBy(arr, predicate)
	{
		const callbackFunction = typeof predicate === 'function' ? predicate : (o) => o[predicate];
		const uniqueItems = new Map();

		for (const item of arr)
		{
			const key = item === null || item === undefined ? item : callbackFunction(item);
			if (!uniqueItems.has(key))
			{
				uniqueItems.set(key, item);
			}
		}

		return [...uniqueItems.values()];
	}

	/**
	 * Creates an array of elements, sorted in ascending order by
	 * the results of running each element in a collection through each iteratee.
	 * @template T
	 * @param {Iterable<T>} collection
	 * @param predicate
	 * @return {Array<T>}
	 */
	function sortBy(collection, predicate)
	{
		const createComparer = (key) => (a, b) => ((a[key] > b[key]) ? 1 : ((b[key] > a[key]) ? -1 : 0));

		return [...collection].sort(createComparer(predicate));
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
	 * @param {[string|number[]]} arrays
	 * @returns {string|number[]}
	 */
	function intersection(...arrays)
	{
		if (arrays.length === 0)
		{
			return [];
		}

		const [firstArray, ...restArrays] = arrays;
		const items = firstArray.filter((item) => {
			return restArrays.every((array) => array.includes(item));
		});

		return unique(items);
	}

	module.exports = {
		last,
		chunk,
		unique,
		uniqBy,
		mergeBy,
		sortBy,
		replaceBy,
		intersection,
	};
});
