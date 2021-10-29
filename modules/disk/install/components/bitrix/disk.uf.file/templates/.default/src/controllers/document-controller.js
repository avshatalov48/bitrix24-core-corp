import DefaultController from './default-controller';
import {EventEmitter, BaseEvent} from 'main.core.events';
import Backend from '../backend';
import type {ItemSavedType, ItemUploadedType} from "../items/item-type";

export default class DocumentController extends DefaultController
{
	constructor({container, eventObject})
	{
		super({
			container: container.querySelector('[data-bx-role="document-area"]'),
			eventObject: eventObject
		});
		if (this.getContainer())
		{
			Array.from(
				this.getContainer().querySelectorAll('[data-bx-handler]')
			)
				.forEach((item) => {
					item.addEventListener('click', () => {
						this.createDocument(item.getAttribute('data-bx-handler'));
					})
				});
		}
	}

	createDocument(documentType)
	{
		if (!BX.Disk.getDocumentService() && BX.Disk.isAvailableOnlyOffice())
		{
			BX.Disk.saveDocumentService('onlyoffice');
		}
		else if (!BX.Disk.getDocumentService())
		{
			BX.Disk.saveDocumentService('l');
		}

		let insertDocumentIntoUf = function (extendedFileData) {
			var parts = extendedFileData.object.name.split('.');
			parts.pop();
			setTimeout(() => {
				EventEmitter.emit(this,
					'onFileIsCreated',
					{itemData: this.convertToItemSavedType(extendedFileData)});
			}, 200);
		}.bind(this);

		if (BX.Disk.Document.Local.Instance.isSetWorkWithLocalBDisk())
		{
			BX.Disk.Document.Local.Instance.createFile({
				type: documentType
			}).then(function (response) {
				insertDocumentIntoUf(response);
			}.bind(this));

			return;
		}

		let createProcess = new BX.Disk.Document.CreateProcess({
			typeFile: documentType,
			serviceCode: BX.Disk.getDocumentService(),
			onAfterSave: function(response, extendedFileData) {
				if (response.status !== 'success')
				{
					return;
				}

				if (!extendedFileData)
				{
					Backend
						.getMetaDataForCreatedFileInUf(response.object.id)
						.then(function({data}) {
							insertDocumentIntoUf(data);
						});
				}
				else
				{
					insertDocumentIntoUf(extendedFileData);
				}
			}
		});
		createProcess.start();
	}

	convertToItemSavedType(extendedFileData) :ItemSavedType
	{
		return {
			ID: 'n' + extendedFileData.object.id,
			IS_LOCKED: false,
			IS_MARK_DELETED: false,

			EDITABLE: false,
			FROM_EXTERNAL_SYSTEM: false,

			CAN_RESTORE: false,
			CAN_UPDATE: true,
			CAN_RENAME: true,
			CAN_MOVE:  true,

			COPY_TO_ME_URL: null,
			DELETE_URL: null,
			DOWNLOAD_URL: null,
			EDIT_URL: null,
			VIEW_URL: extendedFileData.link,
			PREVIEW_URL: null,
			BIG_PREVIEW_URL: null,

			EXTENSION: extendedFileData.object.extension,
			NAME: extendedFileData.object.name,
			SIZE: extendedFileData.object.size,
			SIZE_BYTES: extendedFileData.object.sizeInt,
			STORAGE: extendedFileData.folderName
		};
	}
}

