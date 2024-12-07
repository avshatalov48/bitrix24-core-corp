/**
 * @module text-editor/adapters/code-adapter
 */
jn.define('text-editor/adapters/code-adapter', (require, exports, module) => {
	const { media } = require('native/media');
	const { Type } = require('type');
	const { BaseAdapter } = require('text-editor/adapters/base-adapter');
	const { scheme } = require('text-editor/internal/scheme');
	const { SvgNode } = require('text-editor/svgom/svg-node');
	const { SvgRootNode } = require('text-editor/svgom/svg-root-node');
	const { SvgTextNode } = require('text-editor/svgom/svg-text-node');
	const { Color } = require('tokens');

	const FONT_SIZE = 18;
	const LINE_HEIGHT = 27;
	const SPACE_WIDTH = 4;
	const PADDING_BOTTOM_TOP = 5;
	const START_OFFSET_Y = FONT_SIZE + PADDING_BOTTOM_TOP;

	class CodeAdapter extends BaseAdapter
	{
		sourceCodeLines = null;
		codeLineNodes = null;
		lineNumberNodes = null;

		static getLeadingSpacesCount(text)
		{
			if (Type.isStringFilled(text))
			{
				return text.search(/\S|$/);
			}

			return 0;
		}

		static encodeSvgText(text)
		{
			if (Type.isStringFilled(text))
			{
				return text
					.replaceAll('<', '&#60;')
					.replaceAll('>', '&#62;');
			}

			return text;
		}

		getSourceCodeLines()
		{
			if (Type.isNull(this.sourceCodeLines))
			{
				this.sourceCodeLines = this.getSource().getContent().trim().split('\n');
			}

			return this.sourceCodeLines;
		}

		getLinesCount()
		{
			return this.getSourceCodeLines().length;
		}

		getNumbersBarWidth()
		{
			const linesCount = this.getLinesCount();
			const charsCount = Math.max(String(linesCount).length, 2);

			return charsCount * FONT_SIZE;
		}

		getPreviewWidth()
		{
			const maxLineLength = Math.max(
				...this.getSourceCodeLines().map((line) => {
					return String(line).length;
				}),
			);

			const numbersBarWidth = this.getNumbersBarWidth();

			return numbersBarWidth + (maxLineLength * (FONT_SIZE / 2));
		}

		getPreviewHeight()
		{
			return (this.getLinesCount() * LINE_HEIGHT) + PADDING_BOTTOM_TOP;
		}

		getCodeLineNodes()
		{
			const textLines = this.getSourceCodeLines();

			if (Type.isNull(this.codeLineNodes))
			{
				this.codeLineNodes = textLines.map((textLine, index) => {
					const textNode = new SvgTextNode(textLine);
					const offsetY = (LINE_HEIGHT * index) + START_OFFSET_Y;
					const offsetX = this.getNumbersBarWidth() + (textNode.getLeadingSpacesCount() * SPACE_WIDTH);

					return new SvgNode({
						name: 'text',
						attributes: {
							x: offsetX,
							y: index === 0 ? START_OFFSET_Y : offsetY,
							style: `font-size: ${FONT_SIZE}`,
							fill: Color.base2.toHex(),
						},
						children: [
							textNode,
						],
					});
				});
			}

			return this.codeLineNodes;
		}

		getLineNumberNodes()
		{
			if (Type.isNull(this.lineNumberNodes))
			{
				const sourceCodeNodes = this.getCodeLineNodes();

				this.lineNumberNodes = sourceCodeNodes.map((textNode, index) => {
					const number = index + 1;
					const offsetY = (LINE_HEIGHT * index) + START_OFFSET_Y;
					const numbersBarWidth = this.getNumbersBarWidth();

					return new SvgNode({
						name: 'text',
						attributes: {
							x: numbersBarWidth - FONT_SIZE,
							y: index === 0 ? START_OFFSET_Y : offsetY,
							fill: Color.base4.toHex(),
							style: `font-size: ${FONT_SIZE}`,
							'text-anchor': 'end',
						},
						children: [
							new SvgTextNode(number),
						],
					});
				});
			}

			return this.lineNumberNodes;
		}

		getSvgPreviewContent()
		{
			const rootNode = new SvgRootNode({
				children: [
					...this.getCodeLineNodes(),
					...this.getLineNumberNodes(),
				],
			});

			rootNode.setAttribute('width', this.getPreviewWidth());
			rootNode.setAttribute('height', this.getPreviewHeight());

			return {
				content: rootNode.toString(),
				width: rootNode.getAttribute('width'),
				height: rootNode.getAttribute('height'),
			};
		}

		async getPreview()
		{
			if (!this.previewAsync)
			{
				const { content, width = 0, height = 0 } = this.getSvgPreviewContent();
				const imageSrc = await media.converter.convertSvg({ content });

				this.previewSync = scheme.createElement({
					name: 'img',
					attributes: {
						width,
						height,
					},
					children: [
						scheme.createText({
							content: imageSrc,
						}),
					],
				});
			}

			return this.previewSync;
		}
	}

	module.exports = {
		CodeAdapter,
	};
});
