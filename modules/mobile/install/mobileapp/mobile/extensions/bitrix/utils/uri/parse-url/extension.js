jn.define('utils/uri/parse-url', (require, exports, module) => {
	const { Type } = require('type');

	const urlExp = /^((\w+):)?(\/\/((\w+)?(:(\w+))?@)?([^/:?]+)(:(\d+))?)?(\/?([^#/?][^#?]*)?)?(\?([^#]+))?(#((?:[\w-?!$&'()*+,./:;=@~]|%\w{2})*))?/;

	function isAllowedKey(key)
	{
		return !String(key).startsWith('__proto__');
	}

	function getKeyFormat(key)
	{
		if (/^\w+\[(\w+)]$/.test(key))
		{
			return 'index';
		}

		if (/^\w+\[]$/.test(key))
		{
			return 'bracket';
		}

		return 'default';
	}

	function getParser(format)
	{
		switch (format)
		{
			case 'index':
				return (sourceKey, value, accumulator) => {
					const result = /\[(\w*)]$/.exec(sourceKey);

					const key = sourceKey.replace(/\[\w*]$/, '');

					if (Type.isNil(result))
					{
						// eslint-disable-next-line no-param-reassign
						accumulator[key] = value;

						return;
					}

					if (Type.isUndefined(accumulator[key]))
					{
						// eslint-disable-next-line no-param-reassign
						accumulator[key] = {};
					}

					// eslint-disable-next-line no-param-reassign
					accumulator[key][result[1]] = value;
				};

			case 'bracket':
				return (sourceKey, value, accumulator) => {
					const result = /(\[])$/.exec(sourceKey);
					const key = sourceKey.replace(/\[]$/, '');

					if (Type.isNil(result))
					{
						// eslint-disable-next-line no-param-reassign
						accumulator[key] = value;

						return;
					}

					if (Type.isUndefined(accumulator[key]))
					{
						// eslint-disable-next-line no-param-reassign
						accumulator[key] = [value];

						return;
					}

					// eslint-disable-next-line no-param-reassign
					accumulator[key] = [...accumulator[key], ...value];
				};

			default:
				return (sourceKey, value, accumulator) => {
					const key = sourceKey.replace(/\[]$/, '');
					// eslint-disable-next-line no-param-reassign
					accumulator[key] = value;
				};
		}
	}

	function parseQuery(input)
	{
		if (!Type.isString(input))
		{
			return {};
		}

		const url = input.trim().replace(/^[#&?]/, '');

		if (!url)
		{
			return {};
		}

		return {
			...url.split('&')
				.reduce((acc, param) => {
					const [key, value] = param.replaceAll('+', ' ').split('=');
					if (isAllowedKey(key))
					{
						const keyFormat = getKeyFormat(key);
						const formatter = getParser(keyFormat);
						formatter(key, value, acc);
					}

					return acc;
				}, Object.create(null)),
		};
	}

	function parseUrl(url)
	{
		const result = url.match(urlExp);

		if (Type.isArray(result))
		{
			const queryParams = parseQuery(result[14]);

			return {
				useShort: /^\/\//.test(url),
				href: result[0] || '',
				schema: result[2] || '',
				host: result[8] || '',
				port: result[10] || '',
				path: result[11] || '',
				query: result[14] || '',
				queryParams,
				hash: result[16] || '',
				username: result[5] || '',
				password: result[7] || '',
				origin: result[8] || '',
			};
		}

		return {};
	}

	module.exports = { parseUrl };
});
