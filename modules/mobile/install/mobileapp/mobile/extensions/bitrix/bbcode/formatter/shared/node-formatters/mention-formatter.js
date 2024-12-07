/** @module bbcode/formatter/shared/node-formatters/mention-formatter */
jn.define('bbcode/formatter/shared/node-formatters/mention-formatter', (require, exports, module) => {
	const { DefaultBBCodeScheme } = require('bbcode/model');
	const { NodeFormatter } = require('bbcode/formatter');
	const { inAppUrl } = require('in-app-url');

	const scheme = new DefaultBBCodeScheme();
	let mentionCounter = 0;
	const mentionFormatters = [];

	class MentionFormatter extends NodeFormatter
	{
		constructor(options = {})
		{
			super({
				name: options.name,
				convert({ node }) {
					if (options.renderType === 'text')
					{
						return scheme.createFragment([
							...node.getChildren(),
						]);
					}

					mentionCounter++;

					const resultNode = scheme.createElement({
						name: 'url',
						value: `#${options.name}-${mentionCounter}-${node.getValue()}`,
					});

					mentionFormatters.push({
						sourceNode: node,
						targetNode: resultNode,
					});

					return resultNode;
				},
			});

			this.paths = {
				mention: {
					user: (id) => {
						return `/company/personal/user/${id}/`;
					},
					project: (id) => {
						return `/workgroups/group/${id}/`;
					},
					department: (id) => {
						return `/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=${id}`;
					},
				},
			};
		}

		onClick({ url })
		{
			if (url.startsWith('#user-'))
			{
				const id = url.replace(/^#user-(\d+)-/, '');
				inAppUrl.open(
					this.paths.mention.user(id),
					{
						parentWidget: (this.parentWidget || PageManager),
					},
				);

				return true;
			}

			if (url.startsWith('#department-'))
			{
				const id = url.replace(/^#department-(\d+)-/, '');
				inAppUrl.open(
					this.paths.mention.department(id),
					{
						parentWidget: (this.parentWidget || PageManager),
					},
				);

				return true;
			}

			if (url.startsWith('#project-'))
			{
				const id = url.replace(/^#project-(\d+)-/, '');
				inAppUrl.open(
					this.paths.mention.project(id),
					{
						parentWidget: (this.parentWidget || PageManager),
					},
				);

				return true;
			}

			return false;
		}
	}

	module.exports = {
		MentionFormatter,
		mentionFormatters,
	};
});
