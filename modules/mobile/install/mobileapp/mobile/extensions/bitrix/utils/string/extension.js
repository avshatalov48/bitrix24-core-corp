(() => {

	/**
	 * @class StringUtils
	 */
	class StringUtils
	{
		/**
		 * @param {any} value
		 * @returns {String}
		 */
		static stringify(value)
		{
			if (typeof value === 'undefined' || value === null)
			{
				return '';
			}
			return String(value);
		}

		static camelize(str)
		{
			return str
				.replace(/_/g, " ")
				.replace(/(?:^\w|[A-Z]|\b\w)/g, (word, index) => {
					return index === 0
						? word.toLowerCase()
						: word.toUpperCase();
				})
				.replace(/\s+/g, '');
		}

		static trim(s)
		{
			if (typeof s === 'string' || s instanceof String)
			{
				let r, re;

				re = /^[\s\r\n]+/g;
				r = s.replace(re, "");
				re = /[\s\r\n]+$/g;
				r = r.replace(re, "");
				return r;
			}
			else
				return s;
		}

		static number_format(number, decimals, dec_point, thousands_sep)
		{
			var i, j, kw, kd, km, sign = '';
			decimals = Math.abs(decimals);
			if (isNaN(decimals) || decimals < 0)
			{
				decimals = 2;
			}
			dec_point = dec_point || ',';
			if (typeof thousands_sep === 'undefined')
				thousands_sep = '.';

			number = (+number || 0).toFixed(decimals);
			if (number < 0)
			{
				sign = '-';
				number = -number;
			}

			i = parseInt(number, 10) + '';
			j = (i.length > 3 ? i.length % 3 : 0);

			km = (j ? i.substr(0, j) + thousands_sep : '');
			kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
			kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, '0').slice(2) : '');

			return sign + km + kw + kd;
		}
	}

	jnexport(StringUtils);

})();