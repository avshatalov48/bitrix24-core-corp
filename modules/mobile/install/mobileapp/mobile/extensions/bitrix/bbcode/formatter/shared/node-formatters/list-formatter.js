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
				before({ node }) {
					if (options.renderType === 'text')
					{
						const prevSibling = node.getPreviewsSibling();
						const nextSibling = node.getNextSibling();
						const hasPrevNewLine = prevSibling && prevSibling.getName() === '#linebreak';
						const hasNextNewLine = nextSibling && nextSibling.getName() === '#linebreak';

						const children = [...node.getChildren()];
						if (!hasPrevNewLine)
						{
							children.unshift(scheme.createNewLine());
						}

						if (!hasNextNewLine)
						{
							children.push(scheme.createNewLine());
						}

						return scheme.createFragment({
							children,
						});
					}

					return node;
				},
				convert({ node }) {
					if (options.renderType === 'list')
					{
						return node;
					}

					if (options.renderType === 'text')
					{
						return scheme.createFragment({
							children: [],
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
