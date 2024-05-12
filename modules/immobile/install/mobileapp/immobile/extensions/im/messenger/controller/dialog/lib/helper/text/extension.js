/**
 * @module im/messenger/controller/dialog/lib/helper/text
 */
jn.define('im/messenger/controller/dialog/lib/helper/text', (require, exports, module) => {
	include('InAppNotifier');

	const { Loc } = require('loc');
	const { parser } = require('im/messenger/lib/parser');

	/**
	 * @class DialogTextHelper
	 */
	class DialogTextHelper
	{
		static copyToClipboard(modelMessage)
		{
			Application.copyToClipboard(parser.prepareCopy(modelMessage));

			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_HELPER_TEXT_MESSAGE_COPIED'),
				time: 1,
				backgroundColor: '#E6000000',
			});
		}
	}

	module.exports = { DialogTextHelper };
});
