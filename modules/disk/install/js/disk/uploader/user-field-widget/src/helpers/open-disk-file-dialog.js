import { Text, Type } from 'main.core';
import { Uploader } from 'ui.uploader.core';

import { loadDiskFileDialog } from './load-disk-file-dialog';

const loadingDialogs: Set<string> = new Set();

export const openDiskFileDialog = (options): void => {

	options = Type.isPlainObject(options) ? options : {};
	const dialogId: string = Type.isStringFilled(options.dialogId) ? options.dialogId : `file-dialog-${Text.getRandom(5)}`;
	const onLoad: ?Function = Type.isFunction(options.onLoad) ? options.onLoad : null;
	const onSelect: ?Function = Type.isFunction(options.onSelect) ? options.onSelect : null;
	const onClose: ?Function = Type.isFunction(options.onClose) ? options.onClose : null;
	const uploader: ?Uploader = options.uploader instanceof Uploader ? options.uploader : null;

	if (loadingDialogs.has(dialogId))
	{
		return;
	}

	loadingDialogs.add(dialogId);

	loadDiskFileDialog(dialogId).then((): void => {
		loadingDialogs.delete(dialogId);
		if (onLoad !== null)
		{
			onLoad();
		}

		BX.DiskFileDialog.obCallback[dialogId] = {
			saveButton: (tab, path, selectedItems): void => {
				Object.values(selectedItems).forEach(item => {
					if (uploader !== null)
					{
						uploader.addFile(item.id, { name: item.name, preload: true });
					}
				});

				if (onSelect !== null)
				{
					onSelect(tab, path, selectedItems);
				}
			},
			popupDestroy: (): void => {
				loadingDialogs.delete(dialogId);
				if (onClose !== null)
				{
					onClose();
				}
			},
		};

		if (BX.DiskFileDialog.popupWindow === null)
		{
			BX.DiskFileDialog.openDialog(dialogId);
		}
	});
};