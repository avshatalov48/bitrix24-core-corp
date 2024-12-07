/** @module bbcode/formatter/plain-text-formatter */
jn.define('bbcode/formatter/plain-text-formatter', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { Type } = require('type');
	const { Formatter, NodeFormatter } = require('bbcode/formatter');
	const {
		DiskNodeFormatter,
		MentionFormatter,
		TextFormatter,
		TableFormatter,
		CodeFormatter,
		ListFormatter,
		ListItemFormatter,
	} = require('bbcode/formatter/shared');

	class PlainTextFormatter extends Formatter
	{
		#allowedTags = [];

		/**
		 * @param options {{
		 *     allowedTags?: Array<string>,
		 *     formatters?: Array<NodeFormatter>,
		 *     mentionRenderType?: 'text' | 'link',
		 *     diskRenderType?: 'link' | 'file' | 'text' | 'placeholder' | 'none',
		 *     tableRenderType?: 'link' | 'placeholder' | 'none',
		 *     codeRenderType?: 'code' | 'text' | 'placeholder' | 'none',
		 *     listRenderType?: 'list' | 'text' | 'placeholder' | 'none',
		 * }}
		 */
		constructor(options = {})
		{
			const formatters = [
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
				new ListItemFormatter({
					name: '*',
					renderType: options.listRenderType || 'text',
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

		getDefaultUnknownNodeCallback(options)
		{
			return () => {
				return new NodeFormatter({
					name: 'unknown',
					convert: ({ node }) => {
						if (
							node.getName() === '#root'
							|| this.isAllowedTag(node.getName())
						)
						{
							return node.clone();
						}

						const fragment = node.getScheme().createFragment({
							children: [...node.getChildren()],
						});

						const firstChild = fragment.getFirstChild();
						if (firstChild && firstChild.getName() === '#linebreak')
						{
							firstChild.remove();
						}

						const lastChild = fragment.getFirstChild();
						if (lastChild && lastChild.getName() === '#linebreak')
						{
							lastChild.remove();
						}

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
