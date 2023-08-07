import { Runtime, Text, Type } from 'main.core';
import { Uploader } from 'ui.uploader.core';

import { loadDiskFileDialog } from './load-disk-file-dialog';
import CloudLoadController from './cloud-load-controller';
import CloudUploadController from './cloud-upload-controller';

const loadingDialogs: Set<string> = new Set();

export const openCloudFileDialog = (options): void => {

	options = Type.isPlainObject(options) ? options : {};
	const dialogId: string = Type.isStringFilled(options.dialogId) ? options.dialogId : `cloud-dialog-${Text.getRandom(5)}`;
	const serviceId: string = Type.isStringFilled(options.serviceId) ? options.serviceId : `gdrive`;
	const onLoad: ?Function = Type.isFunction(options.onLoad) ? options.onLoad : null;
	const onSelect: ?Function = Type.isFunction(options.onSelect) ? options.onSelect : null;
	const onClose: ?Function = Type.isFunction(options.onClose) ? options.onClose : null;
	const uploader: ?Uploader = options.uploader instanceof Uploader ? options.uploader : null;

	if (loadingDialogs.has(dialogId))
	{
		return;
	}

	loadingDialogs.add(dialogId);

	loadDiskFileDialog(dialogId, { service: serviceId, cloudImport: 1 }).then((): void => {
		loadingDialogs.delete(dialogId);
		if (onLoad !== null)
		{
			onLoad();
		}

		BX.DiskFileDialog.obCallback[dialogId] = {
			saveButton: (tab, path, selectedItems): void => {
				Runtime.loadExtension('disk.legacy.external-loader').then((): void => {
					Object.values(selectedItems).forEach(item => {
						if (item.type === 'file' && uploader !== null)
						{
							uploader.addFile({
								id: item.id,
								serverFileId: item.id,
								name: item.name,
								size: Text.toNumber(item.sizeInt),
								loadController: new CloudLoadController(
									uploader.getServer(),
									{ fileId: item.id, serviceId: item.provider }
								),
								uploadController: new CloudUploadController(
									uploader.getServer(),
									{ fileId: item.id, serviceId: item.provider }
								),
							});
						}
					});

					if (onSelect !== null)
					{
						onSelect(tab, path, selectedItems);
					}
				})

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