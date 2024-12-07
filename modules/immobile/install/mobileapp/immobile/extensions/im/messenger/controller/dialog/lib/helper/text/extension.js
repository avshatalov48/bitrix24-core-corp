/**
 * @module im/messenger/controller/dialog/lib/helper/text
 */
jn.define('im/messenger/controller/dialog/lib/helper/text', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Icon } = require('assets/icons');

	const { parser } = require('im/messenger/lib/parser');
	const { Notification } = require('im/messenger/lib/ui/notification');

	/**
	 * @class DialogTextHelper
	 */
	class DialogTextHelper
	{
		/**
		 * @param {string} clipboardText
		 * @param {?object} options
		 * @param {?string} options.notificationText
		 * @param {?Icon} options.notificationIcon
		 * @param {?PageManager} options.parentWidget
		 */
		static copyToClipboard(
			clipboardText,
			{
				notificationText = null,
				notificationIcon = null,
				parentWidget = PageManager,
			},
		)
		{
			const text = Type.isStringFilled(clipboardText) ? clipboardText : '';
			Application.copyToClipboard(parser.prepareCopy({ text }));

			const title = notificationText ?? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HELPER_TEXT_MESSAGE_COPIED');
			const icon = notificationIcon instanceof Icon ? notificationIcon : Icon.COPY;
			const toastParams = {
				message: title,
				icon,
			};

			return Notification.showToastWithParams(toastParams, parentWidget);
		}
	}

	module.exports = { DialogTextHelper };
});
