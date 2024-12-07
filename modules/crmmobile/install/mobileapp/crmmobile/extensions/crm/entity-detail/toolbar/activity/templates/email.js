/**
 * @module crm/entity-detail/toolbar/activity/templates/email
 */
jn.define('crm/entity-detail/toolbar/activity/templates/email', (require, exports, module) => {

	const { ActivityPinnedBase } = require('crm/entity-detail/toolbar/activity/templates/base');
	const assets = require('crm/assets/common');

	/**
	 * @class ActivityEmail
	 */
	class ActivityPinnedEmail extends ActivityPinnedBase
	{
		getTitle()
		{
			return Loc.getMessage('M_CRM_E_D_TOOLBAR_TITLE_EMAIL');
		}

		getIcon()
		{
			const { type } = this.props;
			return {
				content: assets[type]('#FFFFFF'),
			};
		}
	}

	module.exports = { ActivityPinnedEmail };
});