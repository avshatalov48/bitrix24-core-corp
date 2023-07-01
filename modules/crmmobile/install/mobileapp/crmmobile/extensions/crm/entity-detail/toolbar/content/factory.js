/**
 * @module crm/entity-detail/toolbar/content/factory
 */
jn.define('crm/entity-detail/toolbar/content/factory', (require, exports, module) => {
	const {
		ActivityPinnedIm,
		AudioPlayer,
	} = require('crm/entity-detail/toolbar/content/templates');

	const Templates = {
		AudioPlayer,
		'Activity:OpenLine': ActivityPinnedIm,
	};

	class TemplateFactory
	{
		static make(template, props)
		{
			return Object.keys(Templates).includes(template)
				? new Templates[template](props)
				: null;
		}
	}

	module.exports = { TemplateFactory };
});
