/**
 * @module crm/timeline/controllers/clipboard
 */
jn.define('crm/timeline/controllers/clipboard', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { copyToClipboard } = require('utils/copy');
	const { Loc } = require('loc');
	const { stringify } = require('utils/string');
	const { Haptics } = require('haptics');

	const SupportedActions = {
		COPY: 'Clipboard:Copy',
	};

	const ContentTypes = {
		TEXT: 'text',
		LINK: 'link',
	};

	/**
	 * @class TimelineClipboardController
	 */
	class TimelineClipboardController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 */
		onItemAction({ action, actionParams = {} })
		{
			if (action === SupportedActions.COPY)
			{
				const content = stringify(actionParams.content);

				if (content.length === 0)
				{
					return;
				}

				const confirmMessage = actionParams.type === ContentTypes.LINK
					? Loc.getMessage('M_CRM_TIMELINE_CONTROLLER_CLIPBOARD_LINK_COPIED')
					: Loc.getMessage('M_CRM_TIMELINE_CONTROLLER_CLIPBOARD_TEXT_COPIED');

				copyToClipboard(content, confirmMessage);
				Haptics.impactLight();
			}
		}
	}

	module.exports = { TimelineClipboardController };
});
