/** @module bbcode/formatter/rich-text-formatter */
jn.define('bbcode/formatter/rich-text-formatter', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { Type } = require('type');
	const { Formatter, NodeFormatter } = require('bbcode/formatter');
	const {
		DiskNodeFormatter,
		MentionFormatter,
		TextFormatter,
		TableFormatter,
		CodeFormatter,
	} = require('bbcode/formatter/shared');

	class RichTextFormatter extends Formatter
	{
		static #defaultNotAllowedTags = ['p', 'span'];
		#notAllowedTags = [...RichTextFormatter.#defaultNotAllowedTags];

		/**
		 * @param options {{
		 *     notAllowedTags?: Array<string>,
		 *     formatters?: Array<NodeFormatter>,
		 *     mentionRenderType?: 'text' | 'link',
		 *     diskRenderType?: 'link' | 'file' | 'text' | 'placeholder' | 'none',
		 *     tableRenderType?: 'link' | 'placeholder' | 'none',
		 *     codeRenderType?: 'code' | 'text' | 'placeholder' | 'none',
		 * }}
		 */
		constructor(options = {})
		{
			const formatters = [
				...(options?.formatters || []),
				new TextFormatter({ name: '#text' }),
				new DiskNodeFormatter({ name: 'disk', renderType: options?.diskRenderType || 'file' }),
				new MentionFormatter({ name: 'user', renderType: options?.mentionRenderType || 'link' }),
				new MentionFormatter({ name: 'project', renderType: options?.mentionRenderType || 'link' }),
				new MentionFormatter({ name: 'department', renderType: options?.mentionRenderType || 'link' }),
				new TableFormatter({ name: 'table', renderType: options?.mentionRenderType || 'link' }),
				new CodeFormatter({ name: 'code', renderType: options?.codeRenderType || 'text' }),
			];

			super({
				...options,
				formatters,
			});

			this.setNotAllowedTags(options.notAllowedTags);

			formatters.forEach((formatter) => {
				formatter.setFormatter(this);
			});

			this.formatters = formatters;
		}

		setNotAllowedTags(notAllowedTags)
		{
			if (Type.isArray(notAllowedTags))
			{
				this.#notAllowedTags = [
					...RichTextFormatter.#defaultNotAllowedTags,
					...notAllowedTags,
				];
			}
		}

		isNotAllowedTag(tagName)
		{
			return this.#notAllowedTags.includes(tagName);
		}

		getDefaultUnknownNodeCallback(options)
		{
			return () => {
				return new NodeFormatter({
					name: 'unknown',
					convert: ({ node }) => {
						if (this.isNotAllowedTag(node.getName()))
						{
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
						}

						return node.clone();
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
		RichTextFormatter,
	};
});
