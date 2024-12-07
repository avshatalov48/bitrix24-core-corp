/**
 * @module text-editor/svgom/svg-text-node
 */
jn.define('text-editor/svgom/svg-text-node', (require, exports, module) => {
	const { Type } = require('type');
	const { SvgNode } = require('text-editor/svgom/svg-node');

	class SvgTextNode extends SvgNode
	{
		content = '';

		constructor(options = {})
		{
			const preparedOptions = Type.isString(options) || Type.isNumber(options) ? { content: options } : options;
			super(preparedOptions);

			this.setName('#text');
			this.setContent(preparedOptions.content);
		}

		setContent(content)
		{
			if (Type.isString(content) || Type.isNumber(content))
			{
				this.content = content;
			}
		}

		getContent()
		{
			return this.content;
		}

		getLeadingSpacesCount()
		{
			const content = this.getContent();
			if (Type.isString(content))
			{
				return content.search(/\S|$/);
			}

			return 0;
		}

		toString()
		{
			return SvgNode.encodeSvgText(String(this.getContent()).trim());
		}
	}

	module.exports = {
		SvgTextNode,
	};
});
