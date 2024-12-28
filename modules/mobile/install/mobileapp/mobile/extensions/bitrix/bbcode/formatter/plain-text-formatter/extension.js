/** @module bbcode/formatter/plain-text-formatter */
jn.define('bbcode/formatter/plain-text-formatter', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { Type } = require('type');
	const { Formatter, NodeFormatter } = require('bbcode/formatter');
	const { BBCodeNode } = require('bbcode/model');
	const {
		DiskNodeFormatter,
		MentionFormatter,
		TextFormatter,
		TableFormatter,
		CodeFormatter,
		ListFormatter,
		LinebreaksWrapper,
	} = require('bbcode/formatter/shared');

	const defaultWrapInLinebreaks = ['disk', 'table', 'code', 'list'];

	class PlainTextFormatter extends Formatter
	{
		#allowedTags = [];
		#wrapInLinebreaks = defaultWrapInLinebreaks;

		/**
		 * @param options {{
		 *     allowedTags?: Array<string>,
		 *     formatters?: Array<NodeFormatter>,
		 *     mentionRenderType?: 'text' | 'link',
		 *     diskRenderType?: 'link' | 'file' | 'text' | 'placeholder' | 'none',
		 *     tableRenderType?: 'link' | 'placeholder' | 'none',
		 *     codeRenderType?: 'code' | 'text' | 'placeholder' | 'none',
		 *     listRenderType?: 'list' | 'text' | 'placeholder' | 'none',
		 *     wrapInLinebreaks?: Array<string>,
		 * }}
		 */
		constructor(options = {})
		{
			const formatters = [
				new NodeFormatter({
					name: '#root',
					convert({ node }) {
						return node.clone();
					},
					after({ element }) {
						element.trimLinebreaks();

						return element;
					},
				}),
				new TextFormatter({
					name: '#text',
				}),
				new TextFormatter({
					name: '#linebreak',
				}),
				new TextFormatter({
					name: '#tab',
				}),
				new DiskNodeFormatter({
					name: 'disk',
					renderType: options.diskRenderType || 'text',
				}),
				new TableFormatter({
					name: 'table',
					renderType: options.tableRenderType || 'placeholder',
				}),
				new CodeFormatter({
					name: 'code',
					renderType: options.codeRenderType || 'text',
				}),
				new ListFormatter({
					name: 'list',
					renderType: options.listRenderType || 'text',
				}),
				new MentionFormatter({
					name: 'user',
					renderType: options.mentionRenderType || 'text',
				}),
				new MentionFormatter({
					name: 'project',
					renderType: options.mentionRenderType || 'text',
				}),
				new MentionFormatter({
					name: 'department',
					renderType: options.mentionRenderType || 'text',
				}),
			];

			super({
				...options,
				formatters,
			});

			formatters.forEach((formatter) => {
				formatter.setFormatter(this);
			});

			this.formatters = formatters;

			this.setAllowedTags(options?.allowedTags);

			if (Type.isArrayFilled(options?.wrapInLinebreaks))
			{
				this.setWrapInLinebreaks(options.wrapInLinebreaks);
			}
		}

		setAllowedTags(allowedTags)
		{
			if (Type.isArray(allowedTags))
			{
				this.#allowedTags = [...allowedTags];
			}
		}

		isAllowedTag(tagName)
		{
			return this.#allowedTags.includes(tagName);
		}

		setWrapInLinebreaks(tags)
		{
			this.#wrapInLinebreaks = tags;
		}

		isAllowWrapInLinebreaks(tagName)
		{
			return (
				Type.isArray(this.#wrapInLinebreaks)
				&& this.#wrapInLinebreaks.includes(tagName)
			);
		}

		format({ source, data })
		{
			const preparedSource = Formatter.prepareSourceNode(source).clone({ deep: true });

			BBCodeNode
				.flattenAst(preparedSource)
				.forEach((node) => {
					if (this.isAllowWrapInLinebreaks(node.getName()))
					{
						const sourceNode = node;
						const targetNode = sourceNode.clone({ deep: true });
						targetNode.trimLinebreaks();

						node.replace(
							LinebreaksWrapper.wrapNode({
								sourceNode,
								targetNode,
							}),
						);
					}
				});

			return super.format({
				source: preparedSource,
				data,
			});
		}

		getDefaultUnknownNodeCallback(options)
		{
			return () => {
				return new NodeFormatter({
					name: 'unknown',
					convert: ({ node }) => {
						const fragment = node.getScheme().createFragment({
							children: [...node.getChildren()],
						});

						fragment.trimLinebreaks();

						return fragment;
					},
				});
			};
		}

		getNodeFormatters()
		{
			return this.formatters;
		}

		getOnClickHandler()
		{
			return ({ url }) => {
				const isClickHandled = this.getNodeFormatters().some((formatter) => {
					if (Type.isFunction(formatter.onClick))
					{
						return formatter.onClick({ url });
					}

					return false;
				});

				if (!isClickHandled)
				{
					inAppUrl.open(
						url,
						{
							parentWidget: (this.parentWidget || PageManager),
						},
					);
				}
			};
		}
	}

	module.exports = {
		PlainTextFormatter,
	};
});
