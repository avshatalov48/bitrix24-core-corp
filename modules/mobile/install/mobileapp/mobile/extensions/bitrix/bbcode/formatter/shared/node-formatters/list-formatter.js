/** @module bbcode/formatter/shared/node-formatters/list-formatter */
jn.define('bbcode/formatter/shared/node-formatters/list-formatter', (require, exports, module) => {
	const { NodeFormatter } = require('bbcode/formatter');
	const { DefaultBBCodeScheme } = require('bbcode/model');
	const { Loc } = require('loc');

	const scheme = new DefaultBBCodeScheme();

	class ListFormatter extends NodeFormatter
	{
		constructor(options = {})
		{
			super({
				name: options.name,
				convert({ node }) {
					if (options.renderType === 'list')
					{
						return node;
					}

					if (options.renderType === 'text')
					{
						return scheme.createFragment({
							children: node.getChildren().flatMap((child, index) => {
								child.trimStartLinebreaks();

								if (index > 0)
								{
									return [
										scheme.createNewLine(),
										...child.getChildren(),
									];
								}

								return child.getChildren();
							}),
						});
					}

					if (options.renderType === 'placeholder')
					{
						return scheme.createText(
							Loc.getMessage('BBCODE_PLAIN_TEXT_FORMATTER_LIST_PLACEHOLDER'),
						);
					}

					return null;
				},
			});
		}
	}

	module.exports = {
		ListFormatter,
	};
});
