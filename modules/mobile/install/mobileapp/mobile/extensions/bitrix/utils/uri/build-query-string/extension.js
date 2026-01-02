jn.define('utils/uri/build-query-string', (require, exports, module) => {
	const { Type } = require('type');

	function buildQueryString(params = {})
	{
		const queryString = Object.entries(params)
			.flatMap(([key, value]) => {
				if (Type.isArray(value))
				{
					return value.map((paramValue) => `${key}[]=${paramValue}`);
				}

				if (Type.isPlainObject(value))
				{
					return Object.entries(value).map(([paramIndex, paramValue]) => `${key}[${paramIndex}]=${paramValue}`);
				}

				return `${key}=${value}`;
			})
			.join('&');

		return queryString.length > 0 ? `?${queryString}` : queryString;
	}

	module.exports = { buildQueryString };
});
