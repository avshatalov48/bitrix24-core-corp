/** @module bbcode/formatter/shared/node-formatters/code-formatter */
jn.define('bbcode/formatter/shared/node-formatters/code-formatter', (require, exports, module) => {
	const { NodeFormatter } = require('bbcode/formatter');
	const { DefaultBBCodeScheme } = require('bbcode/model');
	const { Loc } = require('loc');

	const scheme = new DefaultBBCodeScheme();

	class CodeFormatter extends NodeFormatter
	{
		constructor(options = {})
		{
			super({
				name: options.name,
				convert({ node }) {
					if (options.renderType === 'text')
					{
						const fragment = scheme.createFragment({
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
					}

					if (options.renderType === 'placeholder')
					{
						return scheme.createText(
							Loc.getMessage('BBCODE_PLAIN_TEXT_FORMATTER_CODE_PLACEHOLDER'),
						);
					}

					return null;
				},
			});
		}
	}

	module.exports = {
		CodeFormatter,
	};
});
