/**
 * @module text-editor/adapters/quote-adapter
 */
jn.define('text-editor/adapters/quote-adapter', (require, exports, module) => {
	const { AstProcessor } = require('bbcode/ast-processor');
	const { scheme } = require('text-editor/internal/scheme');
	const { BaseAdapter } = require('text-editor/adapters/base-adapter');
	const { SvgNode } = require('text-editor/svgom/svg-node');
	const { SvgRootNode } = require('text-editor/svgom/svg-root-node');
	const { SvgTextNode } = require('text-editor/svgom/svg-text-node');
	const { Type } = require('type');

	const FONT_SIZE = 18;
	const LINE_HEIGHT = 27;
	const START_OFFSET_Y = FONT_SIZE + 10;

	const LINE_OFFSET_X = 12;
	const TEXT_OFFSET_X = 30;

	const DEVICE_SCREEN_WIDTH = device.screen.width;
	const EDITOR_X_PADDINGS = 40;
	const EDITOR_WIDTH = DEVICE_SCREEN_WIDTH - EDITOR_X_PADDINGS;

	const MAX_LINE_LENGTH = Math.round((EDITOR_WIDTH - (TEXT_OFFSET_X * 2)) / (FONT_SIZE / 2));

	const bbcodeAstLines = Symbol('@@bbcodeAstLines');
	const svgAst = Symbol('@@svgAst');

	class QuoteAdapter extends BaseAdapter
	{
		constructor(options)
		{
			super(options);

			this[bbcodeAstLines] = null;
			this[svgAst] = null;
		}

		static makeAstLines(sourceNode, maxLineLength = MAX_LINE_LENGTH)
		{
			const childQuotes = AstProcessor.findElements(sourceNode, 'BBCodeElementNode[name="quote"]');
			childQuotes.reverse().forEach((node) => {
				node.remove();
			});

			let [leftTree, rightTree] = [null, sourceNode];
			let textLength = sourceNode.getPlainTextLength();

			const astLines = [];
			while (textLength >= 0)
			{
				const splitOffset = Math.min(maxLineLength, textLength);
				[leftTree, rightTree] = rightTree.split({ offset: splitOffset, byWord: true });
				astLines.push(leftTree);

				textLength -= maxLineLength;
			}

			if (Type.isArrayFilled(childQuotes))
			{
				astLines.push(
					...childQuotes.map((quote) => {
						return QuoteAdapter.makeAstLines(quote);
					}),
				);
			}

			return astLines;
		}

		static bbcodeNodeToSvgNode(node)
		{
			if (node.getName() === 'b')
			{
				return new SvgNode({
					name: 'tspan',
					attributes: {
						style: 'font-weight: bold',
					},
					children: node.getChildren().map((child) => {
						return QuoteAdapter.bbcodeNodeToSvgNode(child);
					}),
				});
			}

			if (node.getName() === 'i')
			{
				return new SvgNode({
					name: 'tspan',
					attributes: {
						style: 'font-style: italic',
					},
					children: node.getChildren().map((child) => {
						return QuoteAdapter.bbcodeNodeToSvgNode(child);
					}),
				});
			}

			if (node.getName() === 'u')
			{
				return new SvgNode({
					name: 'tspan',
					attributes: {
						style: 'text-decoration: underline',
					},
					children: node.getChildren().map((child) => {
						return QuoteAdapter.bbcodeNodeToSvgNode(child);
					}),
				});
			}

			if (node.getName() === 's')
			{
				return new SvgNode({
					name: 'tspan',
					attributes: {
						style: `font-size: ${FONT_SIZE}; text-decoration: line-through`,
					},
					children: node.getChildren().map((child) => {
						return QuoteAdapter.bbcodeNodeToSvgNode(child);
					}),
				});
			}

			return new SvgTextNode(node.toString());
		}

		getBBCodeAstLines()
		{
			if (this[bbcodeAstLines] === null)
			{
				this[bbcodeAstLines] = QuoteAdapter.makeAstLines(
					this.getSource().clone({ deep: true }),
				);
			}

			return this[bbcodeAstLines];
		}

		getPreviewWidth()
		{
			return DEVICE_SCREEN_WIDTH - EDITOR_X_PADDINGS;
		}

		getPreviewHeight()
		{
			const astLinesCount = this.getBBCodeAstLines().flat().length;

			return (astLinesCount * LINE_HEIGHT) + (START_OFFSET_Y / 2);
		}

		getSvgAst()
		{
			if (this[svgAst] === null)
			{
				const bbCodeAstLines = this.getBBCodeAstLines();

				this[svgAst] = bbCodeAstLines.map((line) => {
					return line.getChildren().map((child) => {
						return QuoteAdapter.bbcodeNodeToSvgNode(child);
					});
				});
			}

			return this[svgAst];
		}
	}

	module.exports = {
		QuoteAdapter,
	};
});

// jn.define('text-editor/adapters/quote-adapter', (require, exports, module) => {
// 	const { media } = require('native/media');
// 	const { BaseAdapter } = require('text-editor/adapters/base-adapter');
// 	const { scheme } = require('text-editor/internal/scheme');
// 	const { SvgNode } = require('text-editor/svgom/svg-node');
// 	const { SvgRootNode } = require('text-editor/svgom/svg-root-node');
// 	const { SvgTextNode } = require('text-editor/svgom/svg-text-node');
// 	const { AstProcessor } = require('bbcode/ast-processor');
//
// 	const FONT_SIZE = 18;
// 	const LINE_HEIGHT = 27;
// 	const START_OFFSET_Y = FONT_SIZE + 10;
//
// 	const LINE_OFFSET_X = 12;
// 	const TEXT_OFFSET_X = 30;
//
// 	const DEVICE_SCREEN_WIDTH = device.screen.width;
// 	const EDITOR_X_PADDINGS = 40;
// 	const EDITOR_WIDTH = DEVICE_SCREEN_WIDTH - EDITOR_X_PADDINGS;
//
// 	const MAX_LINE_LENGTH = Math.round((EDITOR_WIDTH - (TEXT_OFFSET_X * 2)) / (FONT_SIZE / 2));
//
// 	class QuoteAdapter extends BaseAdapter
// 	{
// 		getSourceNode()
// 		{
// 			return this.getOptions().node;
// 		}
//
// 		getTextLines(maxLineLength = MAX_LINE_LENGTH)
// 		{
// 			const sourceNode = this.getSourceNode();
//
// 			const quotes = AstProcessor.findElements(sourceNode, 'BBCodeElementNode[name="quote"]');
// 			quotes.reverse().forEach((node) => {
// 				node.remove();
// 			});
//
// 			const childQuotes = quotes.map((node) => {
// 				const adapter = new QuoteAdapter({ node });
//
// 				return adapter.getSvgPreviewContent().content;
// 			}).flat(2);
//
// 			const astLines = [];
//
// 			let [leftTree, rightTree] = [null, sourceNode];
// 			let textLength = sourceNode.getPlainTextLength();
//
// 			while (textLength >= 0)
// 			{
// 				const splitOffset = Math.min(maxLineLength, textLength);
// 				[leftTree, rightTree] = rightTree.split({ offset: splitOffset, byWord: true });
// 				astLines.push(leftTree);
//
// 				textLength -= maxLineLength;
// 			}
//
// 			const bbcodeNodeToSvgNode = (node) => {
// 				if (node.getName() === 'b')
// 				{
// 					return new SvgNode({
// 						name: 'tspan',
// 						attributes: {
// 							style: 'font-weight: bold',
// 						},
// 						children: node.getChildren().map((child) => {
// 							return bbcodeNodeToSvgNode(child);
// 						}),
// 					});
// 				}
//
// 				if (node.getName() === 'i')
// 				{
// 					return new SvgNode({
// 						name: 'tspan',
// 						attributes: {
// 							style: 'font-style: italic',
// 						},
// 						children: node.getChildren().map((child) => {
// 							return bbcodeNodeToSvgNode(child);
// 						}),
// 					});
// 				}
//
// 				if (node.getName() === 'u')
// 				{
// 					return new SvgNode({
// 						name: 'tspan',
// 						attributes: {
// 							style: 'text-decoration: underline',
// 						},
// 						children: node.getChildren().map((child) => {
// 							return bbcodeNodeToSvgNode(child);
// 						}),
// 					});
// 				}
//
// 				if (node.getName() === 's')
// 				{
// 					return new SvgNode({
// 						name: 'tspan',
// 						attributes: {
// 							style: `font-size: ${FONT_SIZE}; text-decoration: line-through`,
// 						},
// 						children: node.getChildren().map((child) => {
// 							return bbcodeNodeToSvgNode(child);
// 						}),
// 					});
// 				}
//
// 				return new SvgTextNode(node.toString());
// 			};
//
// 			return [
// 				...astLines.map((line) => {
// 					return line.getChildren().map((child) => {
// 						return bbcodeNodeToSvgNode(child);
// 					});
// 				}),
// 				...childQuotes,
// 			];
// 		}
//
// 		getPreviewWidth()
// 		{
// 			return DEVICE_SCREEN_WIDTH - EDITOR_X_PADDINGS;
// 		}
//
// 		getPreviewHeight()
// 		{
// 			const textLinesCount = this.getTextLines().length;
//
// 			return (textLinesCount * LINE_HEIGHT) + (START_OFFSET_Y / 2);
// 		}
//
// 		getSvgPreviewContent()
// 		{
// 			console.log(this.getTextLines());
//
// 			const rootNode = new SvgRootNode({
// 				attributes: {
// 					width: this.getPreviewWidth(),
// 					height: this.getPreviewHeight(),
// 				},
// 				children: [
// 					new SvgNode({
// 						name: 'line',
// 						attributes: {
// 							x1: LINE_OFFSET_X,
// 							x2: LINE_OFFSET_X,
// 							y1: FONT_SIZE * 0.7,
// 							y2: this.getPreviewHeight() - (FONT_SIZE * 0.3),
// 							stroke: '#d9dce2',
// 						},
// 					}),
// 					...this.getTextLines().map((line, index) => {
// 						return new SvgNode({
// 							name: 'text',
// 							attributes: {
// 								x: TEXT_OFFSET_X,
// 								y: (index * LINE_HEIGHT) + START_OFFSET_Y,
// 								fill: '#555555',
// 								style: `font-size: ${FONT_SIZE}`,
// 							},
// 							children: line,
// 						});
// 					}),
// 				],
// 			});
//
// 			return {
// 				width: rootNode.getAttribute('width'),
// 				height: rootNode.getAttribute('height'),
// 				content: rootNode.toString(),
// 			};
// 		}
//
// 		async getPreview()
// 		{
// 			const { content, width = 0, height = 0 } = this.getSvgPreviewContent();
// 			const imageSrc = await media.converter.convertSvg({ content });
//
// 			return scheme.createElement({
// 				name: 'img',
// 				attributes: {
// 					width,
// 					height,
// 					resize: 'false',
// 				},
// 				children: [
// 					scheme.createText({
// 						content: imageSrc,
// 					}),
// 				],
// 			});
// 		}
// 	}
//
// 	module.exports = {
// 		QuoteAdapter,
// 	};
// });
