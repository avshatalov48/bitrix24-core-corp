/**
 * @module im/messenger/controller/dialog/lib/helper/text
 */
jn.define('im/messenger/controller/dialog/lib/helper/text', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { Icon } = require('assets/icons');
	const { copyToClipboard } = require('utils/copy');

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
		 * @param {?number} options.toastOffset
		 * @param {boolean} forceCopy
		 */
		static copyToClipboard(
			clipboardText,
			{
				notificationText = null,
				notificationIcon = null,
				parentWidget = PageManager,
				toastOffset = null,
			},
			forceCopy = false,
		)
		{
			const text = Type.isStringFilled(clipboardText) ? clipboardText : '';

			copyToClipboard(
				parser.prepareCopy({ text }),
				undefined,
				false,
				forceCopy,
			)
				.then(() => {
					const title = notificationText ?? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HELPER_TEXT_MESSAGE_COPIED');
					const icon = notificationIcon instanceof Icon ? notificationIcon : Icon.COPY;
					const toastParams = {
						icon,
						message: title,
						offset: toastOffset,
					};

					Notification.showToastWithParams(toastParams, parentWidget);
				})
				.catch(console.error)
			;
		}
	}

	module.exports = { DialogTextHelper };
});
