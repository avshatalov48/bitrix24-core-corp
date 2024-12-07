/**
 * @module text-editor/internal/scheme
 */
jn.define('text-editor/internal/scheme', (require, exports, module) => {
	const { DefaultBBCodeScheme } = require('bbcode/model');

	const scheme = new DefaultBBCodeScheme();
	const quoteScheme = scheme.getTagScheme('quote');
	if (quoteScheme)
	{
		const allowedChildren = [
			...quoteScheme.getAllowedChildren(),
			'#block',
		];

		quoteScheme.setAllowedChildren(allowedChildren);
	}

	module.exports = {
		scheme,
	};
});
