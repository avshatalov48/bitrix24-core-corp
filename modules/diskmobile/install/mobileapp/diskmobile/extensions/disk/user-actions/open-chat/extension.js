/**
 * @module disk/user-actions/open-chat
 */
jn.define('disk/user-actions/open-chat', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');

	const store = require('statemanager/redux/store');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');

	let DialogSelector = null;
	let DialogOpener = null;

	const openChat = async (fileId, layoutWidget) => {
		try
		{
			await loadResources();
			const dialogId = await selectDialog(layoutWidget);
			await sendMessage(fileId, dialogId);
			DialogOpener.open({ dialogId });
		}
		catch (err)
		{
			console.error(err);
			Alert.alert(
				Loc.getMessage('M_DISK_USER_ACTIONS_CHOOSE_CHAT_ERROR_TITLE'),
				Loc.getMessage('M_DISK_USER_ACTIONS_CHOOSE_CHAT_ERROR_TEXT'),
				() => {},
				Loc.getMessage('M_DISK_USER_ACTIONS_CHOOSE_CHAT_ERROR_OK'),
			);
		}
	};

	const loadResources = async () => {
		const resources = await Promise.all([
			requireLazy('im:messenger/api/dialog-selector'),
			requireLazy('im:messenger/api/dialog-opener', false),
		]);

		DialogSelector = resources[0]?.DialogSelector;
		DialogOpener = resources[1]?.DialogOpener;
	};

	const selectDialog = async (layoutWidget) => {
		const selector = new DialogSelector();
		const options = {
			title: Loc.getMessage('M_DISK_USER_ACTIONS_CHOOSE_CHAT'),
			layout: layoutWidget,
		};

		const { dialogId } = await selector.show(options);

		return dialogId;
	};

	const sendMessage = async (fileId, dialogId) => {
		const data = {
			dialogId,
			fields: {
				message: prepareLink(fileId),
			},
		};

		return BX.ajax.runAction('im.v2.Chat.Message.send', { data });
	};

	const prepareLink = (fileId) => {
		const file = selectById(store.getState(), fileId);
		if (file?.isFolder)
		{
			throw new Error(`Object ${fileId} is directory`);
		}

		if (!file?.links?.showInGrid)
		{
			throw new Error(`File ${fileId} not found in redux store`);
		}

		return `${currentDomain}${file.links.showInGrid}&cmd=show`;
	};

	module.exports = { openChat };
});
