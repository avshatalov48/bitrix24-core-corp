/**
 * @module text-editor/svgom/svg-root-node
 */
jn.define('text-editor/svgom/svg-root-node', (require, exports, module) => {
	const { Type } = require('type');
	const { SvgNode } = require('text-editor/svgom/svg-node');

	class SvgRootNode extends SvgNode
	{
		constructor(options = {})
		{
			super(options);

			this.setName('svg');
			this.setAttributes({
				...options.attributes,
				xmlns: 'http://www.w3.org/2000/svg',
			});
		}
	}

	module.exports = {
		SvgRootNode,
	};
});
