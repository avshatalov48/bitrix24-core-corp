(() => {

	const { isRegExp } = jn.require('utils/type');

	/**
	 * @param {any} value
	 * @returns {String}
	 */
	function stringify(value)
	{
		if (typeof value === 'undefined' || value === null)
		{
			return '';
		}

		return String(value);
	}

	function capitalize(value, toLocaleCapitalize = false, locale = env.languageId)
	{
		if (toLocaleCapitalize)
		{
			return value.charAt(0).toLocaleUpperCase(locale) + value.slice(1);
		}

		return value.charAt(0).toUpperCase() + value.slice(1);
	}

	function camelize(value)
	{
		return value
			.replace(/_/g, ' ')
			.replace(/(?:^\w|[A-Z]|\b\w)/g, (word, index) => {
				return index === 0
					? word.toLowerCase()
					: word.toUpperCase();
			})
			.replace(/\s+/g, '');
	}

	function trim(value)
	{
		if (typeof value === 'string' || value instanceof String)
		{
			let r, re;

			re = /^[\s\r\n]+/g;
			r = value.replace(re, '');
			re = /[\s\r\n]+$/g;
			r = r.replace(re, '');

			return r;
		}

		return value;
	}

	function number_format(number, decimals, dec_point, thousands_sep)
	{
		let i, j, kw, kd, km, sign = '';
		decimals = Math.abs(decimals);
		if (isNaN(decimals) || decimals < 0)
		{
			decimals = 2;
		}
		dec_point = dec_point || ',';
		if (typeof thousands_sep === 'undefined')
		{
			thousands_sep = '.';
		}

		number = (+number || 0).toFixed(decimals);
		if (number < 0)
		{
			sign = '-';
			number = -number;
		}

		i = parseInt(number, 10) + '';
		j = (i.length > 3 ? i.length % 3 : 0);

		km = (j ? i.substr(0, j) + thousands_sep : '');
		kw = i.substr(j).replace(/(\d{3})(?=\d)/g, `$1${thousands_sep}`);
		kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, '0').slice(2) : '');

		return sign + km + kw + kd;
	}

	/**
	 * @param {string} str
	 * @param {string|RegExp} search
	 * @param {string} replace
	 * @returns {string}
	 * @deprecated This is polyfill implementation exported for testing purposes. Please use str.replaceAll() instead.
	 */
	function replaceAll(str, search, replace)
	{
		if (isRegExp(search))
		{
			return str.replace(search, replace);
		}

		return str.replace(new RegExp(search, 'g'), replace);
	}

	/**
	 * Splits string into an array of its words.
	 * @param string
	 * @param pattern
	 * @returns {string[]}
	 */
	function splitByWords(string, pattern = '')
	{
		const specialChars = pattern || `!"#$%&'()*+,-.\/:;<=>?@[\\]^_\`{|}`;
		const specialCharsRegExp = new RegExp(`[${specialChars}]`, 'g');

		const clearedQuery = (
			string
				.replace(specialCharsRegExp, ' ')
				.replace(/\s\s+/g, ' ')
		);

		return (
			clearedQuery
				.toLowerCase()
				.split(' ')
				.filter(word => word !== '')
		);
	}

	/**
	 * Compare words use IntlCollator
	 * @param wordFirst
	 * @param wordSecond
	 * @returns {boolean}
	 */
	const compareWords = (wordFirst, wordSecond) => {
		const collator = IntlCollator();
		if (collator)
		{
			wordSecond = wordSecond.substring(0, wordFirst.length);

			return collator.compare(wordFirst, wordSecond) === 0;
		}

		return wordSecond.indexOf(wordFirst) === 0;
	};

	const IntlCollator = () => Application.getPlatform() === 'ios' && Intl && Intl.Collator
		? new Intl.Collator(undefined, { sensitivity: 'base' })
		: null;

	if (!String.prototype.replaceAll)
	{
		String.prototype.replaceAll = function(search, replace) {
			return replaceAll(this, search, replace);
		};
	}

	/**
	 * @param {string} str
	 * @param {number} num
	 * @return {string}
	 */
	function truncate(str, num)
	{
		const trimmedStr = trim(str);
		if (trimmedStr.length <= num)
		{
			return trimmedStr;
		}

		const truncatedStr = trimmedStr.slice(0, num);

		return `${trim(truncatedStr)}...`;
	}

	/**
	 * Escapes the RegExp special characters "^", "$", "", ".", "*", "+", "?", "(", ")", "[", "]", "{", "}",
	 * and "|" in string.
	 * @param string
	 * @returns {string}
	 */
	const escapeRegExp = (string) => string.replaceAll(/[$()*+./?[\\\]^{|}-]/g, '\\$&');

	/**
	 * @class StringUtils
	 * @deprecated Please import specific utilities directly, using jn.require()
	 */
	class StringUtils
	{
		static stringify(value)
		{
			return stringify(value);
		}

		static capitalize(value)
		{
			return capitalize(value);
		}

		static camelize(value)
		{
			return camelize(value);
		}

		static trim(value)
		{
			return trim(value);
		}

		static number_format(number, decimals, dec_point, thousands_sep)
		{
			return number_format(number, decimals, dec_point, thousands_sep);
		}
	}

	jnexport(StringUtils);

	/**
	 * @module utils/string
	 */
	jn.define('utils/string', (require, exports, module) => {

		module.exports = {
			stringify,
			capitalize,
			camelize,
			trim,
			number_format,
			splitByWords,
			compareWords,
			IntlCollator,
			truncate,
			replaceAll,
			escapeRegExp,
		};
	});
})();
