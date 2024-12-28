/**
 * @module disk/move
 */
jn.define('disk/move', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DirectorySelector } = require('selector/widget/entity/tree-selectors/directory-selector');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { move } = require('disk/statemanager/redux/slices/files/thunk');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const { selectById: selectStorageById } = require('disk/statemanager/redux/slices/storages');

	const { showToast } = require('toast');
	const { openFolder } = require('disk/opener/folder');
	const { fetchObjectWithRights } = require('disk/rights');

	function moveObject(objectId, order, context, parentWidget = PageManager)
	{
		const movedObject = selectById(store.getState(), objectId);

		const selector = new DirectorySelector({
			widgetParams: {
				backdrop: {
					mediumPositionPercent: 70,
					horizontalSwipeAllowed: false,
				},
				sendButtonName: Loc.getMessage('M_DISK_MOVE_SELECTOR_BUTTON'),
			},
			allowMultipleSelection: false,
			closeOnSelect: false,
			events: {
				onClose: ({ parentItem }) => {
					const targetId = parentItem.rootObjectId || parentItem.id;
					finalizeMove(objectId, targetId, { context, parentWidget });
				},
			},
			provider: {
				storageId: context.storageId || movedObject.storageId,
				options: {
					showDirectoriesOnly: false,
					canSelectFiles: false,
					order,
					context,
				},
			},
		});

		selector.getSelector().show({}, parentWidget);
	}

	async function finalizeMove(objectId, targetId, openFolderOptions)
	{
		const movedObject = selectById(store.getState(), objectId);
		const target = await fetchObjectWithRights(targetId);

		if (!target)
		{
			return;
		}

		let targetName = target.name;
		if (target.parentId === 0)
		{
			const storage = selectStorageById(store.getState(), target.storageId);
			if (storage.type === 'user')
			{
				targetName = Loc.getMessage('M_DISK_MOVE_MY_STORAGE_NAME');
			}
		}

		if (target.rights.canAdd === false)
		{
			showToast({
				message: Loc.getMessage('M_DISK_MOVE_ACCESS_DENIED_TOAST_MESSAGE', {
					'#FOLDER#': targetName,
				}),
			}, openFolderOptions.parentWidget);

			return;
		}

		dispatch(
			move({
				objectId,
				targetId: target.id,
				onFulfilledSuccess: () => {
					const toastMessagePhraseId = movedObject.typeFile
						? 'M_DISK_MOVE_FILE_TOAST_MESSAGE'
						: 'M_DISK_MOVE_FOLDER_TOAST_MESSAGE';
					showToast({
						message: Loc.getMessage(toastMessagePhraseId, {
							'#FOLDER#': targetName,
						}),
						buttonText: Loc.getMessage('M_DISK_MOVE_TOAST_BUTTON'),
						onButtonTap: () => {
							openFolder(
								target.id,
								openFolderOptions.context,
								openFolderOptions.parentWidget,
							).catch((e) => console.error(e));
						},
					}, openFolderOptions.parentWidget);
				},
			}),
		);
	}

	module.exports = { moveObject };
});
