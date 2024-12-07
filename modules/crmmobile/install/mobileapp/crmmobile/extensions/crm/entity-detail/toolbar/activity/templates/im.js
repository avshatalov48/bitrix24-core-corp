/**
 * @module crm/entity-detail/toolbar/activity/templates/im
 */
jn.define('crm/entity-detail/toolbar/activity/templates/im', (require, exports, module) => {

	const { ActivityPinnedBase } = require('crm/entity-detail/toolbar/activity/templates/base');
	const { CommunicationEvents } = require('communication/events');

	/**
	 * @class ActivityPinnedIm
	 */
	class ActivityPinnedIm extends ActivityPinnedBase
	{

		getIcon()
		{
			return {
				content: super.getIcon(),
			};
		}

		handleOnActionClick()
		{
			const { actionParams } = this.props;

			if (!actionParams)
			{
				return;
			}

			CommunicationEvents.execute(actionParams);
		}

	}

	module.exports = { ActivityPinnedIm };
});