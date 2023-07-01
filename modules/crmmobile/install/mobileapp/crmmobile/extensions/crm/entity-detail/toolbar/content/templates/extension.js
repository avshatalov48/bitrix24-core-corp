/**
 * @module crm/entity-detail/toolbar/content/templates
 */
jn.define('crm/entity-detail/toolbar/content/templates', (require, exports, module) => {
	const { ToolbarContentTemplateBase } = require('crm/entity-detail/toolbar/content/templates/base');
	const { ToolbarContentTemplateSingleAction } = require('crm/entity-detail/toolbar/content/templates/single-action');
	const { ActivityPinnedIm } = require('crm/entity-detail/toolbar/content/templates/im');
	const { AudioPlayer } = require('crm/entity-detail/toolbar/content/templates/audio-player');

	module.exports = {
		ToolbarContentTemplateBase,
		ToolbarContentTemplateSingleAction,
		ActivityPinnedIm,
		AudioPlayer,
	};
});
