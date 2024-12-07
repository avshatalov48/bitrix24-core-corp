/** @module bbcode/formatter/shared/node-formatters/strip-tag-formatter */
jn.define('bbcode/formatter/shared/node-formatters/strip-tag-formatter', (require, exports, module) => {
	const { NodeFormatter } = require('bbcode/formatter');

	class StripTagFormatter extends NodeFormatter
	{
		constructor(options = {})
		{
			super({
				name: options.name,
				convert({ node }) {
					const fragment = node.getScheme().createFragment({
						children: [
							...node.getChildren(),
						],
					});

					const firstChild = fragment.getFirstChild();
					if (firstChild && firstChild.getName() === '#linebreak')
					{
						firstChild.remove();
					}

					const lastChild = fragment.getLastChild();
					if (lastChild && lastChild.getName() === '#linebreak')
					{
						lastChild.remove();
					}

					return fragment;
				},
			});
		}
	}

	module.exports = {
		StripTagFormatter,
	};
});
