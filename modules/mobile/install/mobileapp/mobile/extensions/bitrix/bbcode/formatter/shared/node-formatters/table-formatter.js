/** @module bbcode/formatter/shared/node-formatters/table-formatter */
jn.define('bbcode/formatter/shared/node-formatters/table-formatter', (require, exports, module) => {
	const { NodeFormatter } = require('bbcode/formatter');
	const { DefaultBBCodeScheme } = require('bbcode/model');
	const { Loc } = require('loc');

	const scheme = new DefaultBBCodeScheme();

	class TableFormatter extends NodeFormatter
	{
		constructor(options = {})
		{
			super({
				name: options.name,
				convert({ node }) {
					if (options.renderType === 'placeholder')
					{
						return scheme.createText(
							Loc.getMessage('BBCODE_PLAIN_TEXT_FORMATTER_TABLE_PLACEHOLDER'),
						);
					}

					return null;
				},
			});
		}
	}

	module.exports = {
		TableFormatter,
	};
});
