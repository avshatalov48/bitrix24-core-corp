/**
 * @module crm/entity-detail/toolbar/activity/templates
 */
jn.define('crm/entity-detail/toolbar/activity/templates', (require, exports, module) => {

	const { ActivityPinnedEmail } = require('crm/entity-detail/toolbar/activity/templates/email');
	const { ActivityPinnedIm } = require('crm/entity-detail/toolbar/activity/templates/im');
	const { ActivityPinnedBase } = require('crm/entity-detail/toolbar/activity/templates/base');
	const { ActivityPinnedCall } = require('crm/entity-detail/toolbar/activity/templates/call');

	module.exports = {
		ActivityPinnedBase,
		ActivityPinnedEmail,
		ActivityPinnedIm,
		ActivityPinnedCall,
	};
});