/**
 * @module im/messenger/controller/dialog/lib/header/buttons/buttons
 */
jn.define('im/messenger/controller/dialog/lib/header/buttons/buttons', (require, exports, module) => {
	const { HeaderButtons } = require('im/messenger/controller/dialog/lib/header/buttons/buttons/buttons');
	const {
		CallAudioButton,
		CallVideoButton,
		UnsubscribedFromCommentsButton,
		SubscribedToCommentsButton,
		AddUsersButton,
		CancelMultipleSelectButton,
	} = require('im/messenger/controller/dialog/lib/header/buttons/buttons/button-configuration');

	module.exports = {
		HeaderButtons,
		CallAudioButton,
		CallVideoButton,
		UnsubscribedFromCommentsButton,
		SubscribedToCommentsButton,
		AddUsersButton,
		CancelMultipleSelectButton,
	};
});