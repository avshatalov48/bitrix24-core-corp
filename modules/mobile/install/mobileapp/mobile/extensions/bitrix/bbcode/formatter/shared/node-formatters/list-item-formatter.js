/** @module bbcode/formatter/shared/node-formatters/list-item-formatter */
jn.define('bbcode/formatter/shared/node-formatters/list-item-formatter', (require, exports, module) => {
	const { NodeFormatter } = require('bbcode/formatter');
	const { DefaultBBCodeScheme } = require('bbcode/model');

	const scheme = new DefaultBBCodeScheme();

	class ListItemFormatter extends NodeFormatter
	{
		constructor(options = {})
		{
			super({
				name: options.name,
				convert({ node, data, formatter }) {
					if (options.renderType === 'list')
					{
						return node;
					}

					if (options.renderType === 'text')
					{
						const children = node.getChildren().map((child) => {
							return formatter.format({
								data,
								source: child,
							});
						});
						if (node.getPreviewsSibling() !== null)
						{
							children.unshift(scheme.createNewLine());
						}

						return scheme.createFragment({
							children,
						});
					}

					return null;
				},
			});
		}
	}

	module.exports = {
		ListItemFormatter,
	};
});
