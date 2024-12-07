/**
 * @module utils/url
 */
jn.define('utils/url', (require, exports, module) => {
	const { punycode } = require('utils/url/punycode');
	const { stringify } = require('utils/string');

	/**
	 * @function URL
	 * @param {string} link
	 * @return {object}
	 */
	function URL(link)
	{
		const href = prepareLink(link);

		if (!href)
		{
			return null;
		}

		const match = href.match(/^(https?:)\/\/(([^#/:?]*)(?::(\d+))?)(\/?[^#?]*)(\?[^#]*|)(#.*|)$/i);
		if (!match || !Array.isArray(match))
		{
			return {};
		}

		const hostname = punycode.toASCII(stringify(match[3]));
		const protocol = stringify(match[1]);

		return {
			href,
			origin: `${protocol}//${hostname}`,
			protocol,
			host: stringify(match[2]),
			hostname,
			port: stringify(match[4]),
			pathname: stringify(match[5]),
			search: stringify(match[6]),
			hash: stringify(match[7]),
		};
	}

	/**
	 * @function prepareLink
	 * @param {String} link
	 * @return {String}
	 */
	function prepareLink(link)
	{
		const url = stringify(link).trim();

		// Checks for if url doesn't match either of: http://example.com, https://example.com AND //example.com
		if (Boolean(url) && !/^(https?:)?\/\//i.test(url))
		{
			return `http://${url}`;
		}

		return link;
	}

	/**
	 * @function getParameterByName
	 * @param {String} name
	 * @param{String} url
	 * @return {String}
	 */
	function getParameterByName(url, name)
	{
		const preparedName = name.replaceAll(/[[\]]/g, '\\$&');
		const regex = new RegExp(`[?&]${preparedName}(=([^&#]*)|&|#|$)`);
		const results = regex.exec(url);
		if (!results)
		{
			return null;
		}

		if (!results[2])
		{
			return '';
		}

		return decodeURIComponent(results[2].replaceAll('+', ' '));
	}

	/**
	 * @function getHttpPath
	 * @param {String} url
	 * @return {String}
	 */
	function getHttpPath(url)
	{
		return URL(url)?.href || '';
	}

	/**
	 * @function isValidLink
	 * @param {String} url
	 * @return {Boolean}
	 */
	function isValidLink(url)
	{
		const href = getHttpPath(url);

		if (!href)
		{
			return false;
		}

		return Application.canOpenUrl(href);
	}

	/**
	 * Prefix {uri} with current domain, if {uri} is local path.
	 * Otherwise, keeps {uri} unchanged.
	 * @param {string} uri
	 * @return {string}
	 */
	function withCurrentDomain(uri)
	{
		if (typeof uri !== 'string')
		{
			return uri;
		}

		return uri.startsWith('/') ? currentDomain + uri : uri;
	}

	module.exports = {
		URL,
		prepareLink,
		isValidLink,
		getHttpPath,
		withCurrentDomain,
		getParameterByName,
	};
});
