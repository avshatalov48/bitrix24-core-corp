import {ajax, Loc, Uri} from 'main.core';

export default class Backend
{
	static urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID=' +
		Loc.getMessage('SITE_ID') + '&dialog2=Y&ACTION=SELECT&MULTI=Y';

	static getSelectedFile(fileId, fileName, dialogName)
	{
		return new Promise((resolve) => {
			ajax.get(
				Uri.addParam(this.urlSelect, {
					ACTION: 'none',
					MULTI: 'Y',
					ID: ['E', fileId].join(''),
					NAME: fileName,
					wish: 'fakemove',
					dialogName: dialogName
				}),
				resolve
			);
		});
	}

	static loadFolder(fileId, fileName, dialogName)
	{
		const targetID = fileId;
		const libLink = Uri.addParam(
			[
				BX.DiskFileDialog.obCurrentTab[dialogName].link
					.replace('/index.php', '')
					.replace('/files/lib/', '/files/'),
				'element/upload',
				targetID
			].join('/'),
			{
				use_light_view: 'Y',
				AJAX_CALL: 'Y',
				SIMPLE_UPLOAD: 'Y',
				IFRAME: 'Y',
				sessid: BX.bitrix_sessid(),
				SECTION_ID: targetID,
				CHECK_NAME: fileName
			}
		);
		return new Promise((resolve) => {
			ajax.loadJSON(libLink, {}, resolve);
		});
	}

	static getSelectedData(dialogName)
	{
		return new Promise((resolve) => {
			ajax.get(
				Uri.addParam(this.urlSelect, {
					dialogName: dialogName
				}),
				resolve
			)
		})
	}

	static getSelectedCloudData(dialogName, service)
	{
		return new Promise((resolve) => {
			ajax.get(
				Uri.addParam(this.urlSelect, {
					cloudImport: 1,
					service: service,
					dialogName: dialogName
				}),
				resolve
			)
		})
	}

	static moveFile(id, targetFolderId)
	{
		return new Promise((resolve) => {
			BX.Disk.ajax({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'moveUploadedFile'),
				data: {
					attachedId: id,
					targetFolderId: targetFolderId
				},
				onsuccess: resolve
			});
		});
	}

	static getMetaDataForCreatedFileInUf(id)
	{
		return ajax.runAction(
			'disk.api.file.getMetaDataForCreatedFileInUf',
			{
				data: {
					id: id
				}
			}
		);
	}

	static renameAction(id, newName)
	{
		return new Promise((resolve) => {
			ajax.post(
				'/bitrix/tools/disk/uf.php?action=renameFile',
				{
					newName: newName,
					attachedId: id,
					sessid: Loc.getMessage('bitrix_sessid')
				},
				resolve
			)
		})
	}

	static deleteAction(id)
	{
		return new Promise((resolve) => {
			ajax.post(
				'/bitrix/tools/disk/uf.php?action=deleteFile',
				{
					attachedId: id,
					sessid: Loc.getMessage('bitrix_sessid')
				},
				resolve
			)
		})
	}
}