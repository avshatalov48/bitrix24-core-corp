(() => {

	/**
	 * @class Utils
	 * @deprecated Please use specific utils from utils/string, utils/object, etc.
	 */
	class Utils
	{
		static md5(any)
		{
			return HashUtils.md5(any);
		}

		static objectClone(properties)
		{
			return ObjectUtils.clone(properties);
		}

		static objectMerge(currentProperties, newProperties)
		{
			return ObjectUtils.merge(currentProperties, newProperties);
		}

		static objectDeepSet(obj, path, value)
		{
			return ObjectUtils.set(obj, path, value);
		}

		static objectDeepGet(obj, path, defaultValue)
		{
			return ObjectUtils.get(obj, path, defaultValue);
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
			return ObjectUtils.isObjectLike(value);
		}

		static isNotEmptyString(value)
		{
			return Utils.isString(value) && value !== '';
		}

		static isNotEmptyObject(value)
		{
			return !ObjectUtils.isEmpty(value);
		}

		static isEmptyObject(value)
		{
			return ObjectUtils.isEmpty(value);
		}

		static getRandom(length = 8)
		{
			return Random.getString(length);
		}

		static debounce(fn, timeout, ctx, immediate = false)
		{
			return FunctionUtils.debounce(fn, timeout, ctx, immediate);
		}

		static trim(s)
		{
			return StringUtils.trim(s);
		}

		static number_format(number, decimals, dec_point, thousands_sep)
		{
			return StringUtils.number_format(number, decimals, dec_point, thousands_sep);
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
