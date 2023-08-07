import {Tag, Type} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import FileUploader from './file-uploader';
import DefaultController from './controllers/default-controller';
import Options from "./options";
import FileSelector from "./controllers/file-selector";
import FileSelectorCloud from "./controllers/file-selector-cloud";
import type {ItemSavedType} from './items/item-type'

let justCounter = 0;
function _camelToSNAKE(obj)
{
	var o = {}, i, k;
	for (i in obj)
	{
		k = i.replace(/(.)([A-Z])/g, "$1_$2").toUpperCase();
		o[k] = obj[i];
		o[i] = obj[i];
	}

	return o;
}

export default class FormBrief extends DefaultController {
	id: string;
	fileUploader: FileUploader;
	fieldName: string;
	fileSelector: FileSelector;
	fileSelectorCloud: FileSelectorCloud;

	constructor({id, fieldName, container, eventObject, input})
	{
		super({container, eventObject});
		this.id = id;
		this.fieldName = fieldName;
		this.input = input;

		if (!this.input)
		{
			return;
		}
		this.agent = BX.Uploader.getInstance({
			id: id,
			streams: 1,
			allowUpload: 'A',
			uploadFormData: 'N',
			uploadMethod: 'immediate',
			uploadFileUrl: Options.urlUpload,
			showImage: false,
			sortItems: false,
			dropZone: null,
			input: this.input,
			pasteFileHashInForm: false,
		});

		this.onUploadDone = this.onUploadDone.bind(this);
		this.onUploadError = this.onUploadError.bind(this);

		EventEmitter.subscribe(this.agent, "onFileIsUploaded", ({compatData: [itemId, item, params]}) => {
			this.onUploadDone(item, params);
		});
		EventEmitter.subscribe(this.agent, "onFileIsUploadedWithError", ({compatData: [itemId, item, params]}) => {
			this.onUploadError(item, params);
		});

		this.fileSelector = new FileSelector(this);
		EventEmitter.subscribe(this.fileSelector, 'onUploadDone', ({data: {itemData}}) => {
			this.onSelectionIsDone(itemData);
		});
		this.fileSelectorCloud = new FileSelectorCloud(this);
		EventEmitter.subscribe(this.fileSelectorCloud, 'onUploadDone', ({data: {itemData}}) => {
			this.onSelectionIsDone(itemData);
		});
	}

	onSelectionIsDone(item: ItemSavedType)
	{
		const attrs = {
			id: 'disk-edit-attach' + item['ID'],
			'bx-agentFileId': item['ID']
		};
		if (item["FILE_ID"])
			attrs["bx-attach-file-id"] = 'n' + item["FILE_ID"];
		const node = Tag.render`<input type="hidden" name="${this.fieldName}" value="${item['ID']}">`;
		for (let ii in attrs)
		{
			if (attrs.hasOwnProperty(ii))
			{
				node.setAttribute(ii, attrs[ii]);
			}
		}
		this.getContainer().appendChild(node);

		let res = {
			element_id: item['ID'],
			element_name: item['NAME'],
			element_url: item['PREVIEW_URL'],
			storage: item['STORAGE']
		};
		EventEmitter.emit(this.getEventObject(),
			'OnFileUploadSuccess',
			new BaseEvent({compatData: [res, {}, null, {
				id: justCounter++,
				name: item['NAME'],
				size: item['SIZE'],
				sizeInt: item['SIZE_INT'],
			}]})
		);

	}

	onUploadDone(item, result)
	{
		if (result["file"] && result["file"]["attachId"] !== result["file"]["id"])
		{
			result["file"]["id"] = result["file"]["attachId"];
			delete result["file"]["attachId"];
		}

		const file = _camelToSNAKE(result["file"]);
		const attrs = {
			id: 'disk-edit-attach' + file.id,
			'bx-agentFileId': item.id
		};
		if (file["XML_ID"])
			attrs["bx-attach-xml-id"] = file["XML_ID"];
		if (file["FILE_ID"])
			attrs["bx-attach-file-id"] = 'n' + file["FILE_ID"];
		if (file['FILE_TYPE'])
			attrs["bx-attach-file-type"] = file["FILE_TYPE"];
		file.element_id = file.id;
		const node = Tag.render`<input type="hidden" name="${this.fieldName}" value="${file.id}">`;
		for (let ii in attrs)
		{
			if (attrs.hasOwnProperty(ii))
			{
				node.setAttribute(ii, attrs[ii]);
			}
		}
		this.getContainer().appendChild(node);
		this.onFileIs(item, file);
	}
	
	onFileIs(item, file)
	{
		let res = {
			element_id : file.element_id,
			element_name : (file.element_name || item.name),
			element_url: (file.element_name || file.previewUrl || file.preview_url),
			storage: 'disk'
		};
		EventEmitter.emit(this.getEventObject(),
			'OnFileUploadSuccess',
			new BaseEvent({compatData: [res, this, item.file, item]}));
	}

	onUploadError(item, params)
	{
		BX.onCustomEvent(this.getEventObject(), 'OnFileUploadFailed', [this, item.file, item]);
	}
}