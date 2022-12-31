(() => {

	const { Loc } = jn.require('loc');
	const { md5 } = jn.require('utils/hash');
	const { debounce } = jn.require('utils/function');
	const { trim, number_format } = jn.require('utils/string');
	const {
		clone,
		merge,
		get,
		set,
		isObjectLike,
		isEmpty,
	} = jn.require('utils/object');

	/**
	 * @class Utils
	 * @deprecated Please use specific utils from utils/string, utils/object, etc.
	 */
	class Utils
	{
		static md5(any)
		{
			return md5(any);
		}

		static objectClone(properties)
		{
			return clone(properties);
		}

		static objectMerge(currentProperties, newProperties)
		{
			return merge(currentProperties, newProperties);
		}

		static objectDeepSet(obj, path, value)
		{
			return set(obj, path, value);
		}

		static objectDeepGet(obj, path, defaultValue)
		{
			return get(obj, path, defaultValue);
		}

		static isString(value)
		{
			return typeof value === 'string';
		}

		static isFunction(value)
		{
			return typeof value === 'function';
		}

		static isObjectLike(value)
		{
			return isObjectLike(value);
		}

		static isNotEmptyString(value)
		{
			return Utils.isString(value) && value !== '';
		}

		static isNotEmptyObject(value)
		{
			return !isEmpty(value);
		}

		static isEmptyObject(value)
		{
			return isEmpty(value);
		}

		static getRandom(length = 8)
		{
			return Random.getString(length);
		}

		static debounce(fn, timeout, ctx, immediate = false)
		{
			return debounce(fn, timeout, ctx, immediate);
		}

		static trim(s)
		{
			return trim(s);
		}

		static number_format(number, decimals, dec_point, thousands_sep)
		{
			return number_format(number, decimals, dec_point, thousands_sep);
		}

		static getPluralForm(value, languageId)
		{
			return Loc.getPluralForm(value, languageId);
		}
	}

	/**
	 * @type {Utils}
	 * @deprecated Please use specific utils from utils/string, utils/object, etc.
	 */
	window.Utils = Utils;

	/**
	 * @type {Utils}
	 * @deprecated Please use specific utils from utils/string, utils/object, etc.
	 */
	window.CommonUtils = Utils; //alias

})();
