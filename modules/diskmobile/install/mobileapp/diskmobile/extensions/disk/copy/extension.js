/**
 * @module disk/copy
 */
jn.define('disk/copy', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DirectorySelector } = require('selector/widget/entity/tree-selectors/directory-selector');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { copy } = require('disk/statemanager/redux/slices/files/thunk');
	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const { selectById: selectStorageById } = require('disk/statemanager/redux/slices/storages');

	const { showToast } = require('toast');
	const { openFolder } = require('disk/opener/folder');
	const { fetchObjectWithRights } = require('disk/rights');

	function copyObject(objectId, order, context, parentWidget = PageManager)
	{
		const copiedObject = selectById(store.getState(), objectId);

		const selector = new DirectorySelector({
			widgetParams: {
				backdrop: {
					mediumPositionPercent: 70,
					horizontalSwipeAllowed: false,
				},
				sendButtonName: Loc.getMessage('M_DISK_COPY_SELECTOR_BUTTON'),
			},
			allowMultipleSelection: false,
			closeOnSelect: false,
			events: {
				onClose: ({ parentItem }) => {
					const targetId = parentItem.rootObjectId || parentItem.id;
					finalizeCopy(objectId, targetId, { context, parentWidget });
				},
			},
			provider: {
				storageId: context.storageId || copiedObject.storageId,
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

	async function finalizeCopy(objectId, targetId, openFolderOptions)
	{
		const copiedObject = selectById(store.getState(), objectId);
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
				targetName = Loc.getMessage('M_DISK_COPY_MY_STORAGE_NAME');
			}
		}

		if (target.rights.canAdd === false)
		{
			showToast({
				message: Loc.getMessage('M_DISK_COPY_ACCESS_DENIED_TOAST_MESSAGE', {
					'#FOLDER#': targetName,
				}),
			}, openFolderOptions.parentWidget);

			return;
		}
		dispatch(
			copy({
				objectId,
				targetId: target.id,
				onFulfilledSuccess: () => {
					const toastMessagePhraseId =	copiedObject.typeFile
						? 'M_DISK_COPY_FILE_TOAST_MESSAGE'
						: 'M_DISK_COPY_FOLDER_TOAST_MESSAGE';
					showToast({
						message: Loc.getMessage(toastMessagePhraseId, {
							'#FOLDER#': targetName,
						}),
						buttonText: Loc.getMessage('M_DISK_COPY_TOAST_BUTTON'),
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

	module.exports = { copyObject };
});
