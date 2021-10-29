import {Loc, Type} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import FileUploader from './file-uploader';
import FileController from './controllers/file-controller';
import DefaultController from './controllers/default-controller';
import SettingsController from './controllers/settings-controller';
import DocumentController from './controllers/document-controller';
import PanelController from './controllers/panel-controller';
import FileSelector from "./controllers/file-selector";
import FileSelectorCloud from "./controllers/file-selector-cloud";
import FileMover from "./controllers/file-mover";
import {Ears} from 'ui.ears';
import FormBrief from './form-brief'

export default class Form extends DefaultController {
	id: string;
	filesController: FileController;
	fileUploader: FileUploader;
	fileSelector: FileSelector;
	fileSelectorCloud: FileSelectorCloud;
	fieldName: string;

	constructor({id, fieldName, container, eventObject, input, parserParams}, values: ?Array)
	{
		super({container, eventObject});
		this.id = id;
		this.fieldName = fieldName;
		this.input = input;

		if (parserParams)
		{
			this.getFileController()
				.getParser()
				.setParams(parserParams);
		}

		this.init();

		if (values.length > 0)
		{
			EventEmitter.emit(this.getEventObject(), 'onShowControllers', 'show');
			this.show(values);
		}
	}

	init()
	{
		if (this.input)
		{
			this.getFilesUploader();
		}

		this.initDocumentController();
		this.settingsController = new SettingsController(this, {});
		this.panelController = new PanelController(this);
		this.fileSelector = new FileSelector(this);
		EventEmitter.subscribe(this.fileSelector, 'onUploadDone', ({data: {itemData}}) => {
			this.getFileController().add(itemData);
		});
		this.fileSelectorCloud = new FileSelectorCloud(this);
		EventEmitter.subscribe(this.fileSelectorCloud, 'onUploadDone', ({data: {itemData, itemContainer}}) => {
			this.getFileController().add(itemData, itemContainer);
		});

		const switcher = (event: BaseEvent) => {
			let status = Type.isArray(event.getData()) ? event.getData().shift() : event.getData();
			if (status === 'show')
			{
				this.show();
			}
			else
			{
				this.hide();
			}
		};
		//region compatibility
		EventEmitter.subscribe(this.getEventObject(), 'onCollectControllers', (event) => {
			event.data[this.fieldName] = {
				storage: 'disk',
				tag: this.getFileController().isPluggedIn() ? this.getFileController().getParser().tag : null,
				values: [],
				handler: {
					selectFile: (tab, path, selected) => {
						this.fileSelector.selectFile(tab, path, selected);
					}
				}
			};
			Array.from(
				this.getContainer()
					.querySelectorAll(`input[type="hidden"][name="${this.fieldName}"]`)
			)
				.forEach((nodeItem) => {
					event.data[this.fieldName].values.push(nodeItem.value);
				});
		});
		//endregion
		EventEmitter.subscribe(this.getEventObject(), 'onShowControllers', switcher);
		EventEmitter.subscribe(this.getEventObject(), 'DiskLoadFormController', switcher);

		// (new Ears({
		// 	container: this.getContainer().querySelector('[data-bx-role="control-panel-main-actions"]'),
		// 	noScrollbar: false,
		// 	className: 'disk-documents-ears'
		// })).init();

		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'disk.uf.file:create:thumb:upload', ({data}) => {
			this.show();
			this.getFilesUploader().addTestThumb(data);
		});
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'disk.uf.file:create:thumb:error', () => {
			this.show();
			this.getFilesUploader().addTestErrorThumb();
		});
	}

	initDocumentController()
	{
		this.documentController = new DocumentController({container: this.getContainer(), eventObject: this.getEventObject()});
		EventEmitter.subscribe(this.documentController, 'onFileIsCreated', ({data: {itemData}}) => {
			EventEmitter.emit(this.getEventObject(), 'onShowControllers', 'show');
			this.getFileController().add(itemData);
		});

		if (!this.documentController.isRelevant())
		{
			return;
		}
		else if (!this.isPluggedIn())
		{
			this.documentController.show();
			return;
		}
		if (this.eventObject.dataset.bxDiskDocumentButton !== 'added')
		{
			this.eventObject.dataset.bxDiskDocumentButton = 'added';

			const node = document.createElement('DIV');
			node.addEventListener('click', () => {
				const container = node.closest('[data-id="disk-document"]');
				if (container && container.hasAttribute('data-bx-button-status'))
				{
					EventEmitter.emit(this.getEventObject(), 'onHideDocuments');
				}
				else
				{
					EventEmitter.emit(this.getEventObject(), 'onShowControllers', 'hide');
					EventEmitter.emit(this.getEventObject(), 'onShowDocuments', 'show')
				}
			});
			node.innerHTML = '<i></i>' + Loc.getMessage('WDUF_CREATE_DOCUMENT');

			EventEmitter.emit(this.eventObject, 'OnAddButton', [{BODY: node, ID: 'disk-document'}, 'file']);
			EventEmitter.subscribe(this.getEventObject(), 'onShowDocuments', () => {
				const container = node.closest('[data-id="disk-document"]');
				if (container)
				{
					container.setAttribute('data-bx-button-status', 'active');
				}
			});
			EventEmitter.subscribe(this.getEventObject(), 'onHideDocuments', () => {
				const container = node.closest('[data-id="disk-document"]');
				if (container)
				{
					container.removeAttribute('data-bx-button-status');
				}
			});
		}
		EventEmitter.subscribe(this.getEventObject(), 'onShowDocuments', () => {
			super.show();
			this.documentController.show();
		});

		EventEmitter.subscribe(this.getEventObject(), 'onHideDocuments', () => {
			super.hide();
			this.documentController.hide();
		});
		const switcher = ({data}) => {
			if (data === 'show')
			{
				EventEmitter.emit(this.getEventObject(), 'onHideDocuments');
			}
		};
		EventEmitter.subscribe(this.getEventObject(), 'DiskLoadFormController', switcher);
		EventEmitter.subscribe(this.getEventObject(), 'onShowControllers', switcher);
	}

	getFileController(): FileController
	{
		if (!this.filesController)
		{
			this.filesController = new FileController({
				id: this.id,
				fieldName: this.fieldName,
				container: this.container,
				eventObject: this.eventObject
			});
		}
		return this.filesController
	}

	getFilesUploader()
	{
		if (!this.filesUploader)
		{
			this.filesUploader = new FileUploader({
				id: this.id,
				container: this.container.querySelector('[data-bx-role="placeholder"]'),
				dropZone: this.container,
				input: this.input,
			});
			//Video
			EventEmitter.subscribe(this.getEventObject(), 'OnVideoHasCaught', (event: BaseEvent) => {
				const fileToUpload = event.getData();
				const onSuccess = ({data: {itemData, itemContainer, blob}}) => {
					if (fileToUpload === blob)
					{
						EventEmitter.unsubscribe(this.filesUploader, 'onUploadDone', onSuccess);
						this.getFileController().getParser().insertFile(itemData.ID);
					}
				}
				EventEmitter.subscribe(this.filesUploader, 'onUploadDone', onSuccess);
				this.filesUploader.upload([fileToUpload]);
				event.stopImmediatePropagation();
			});
			//Image
			EventEmitter.subscribe(this.getEventObject(), 'OnImageHasCaught', (event: BaseEvent) => {
				const fileToUpload = event.getData();
				event.stopImmediatePropagation();
				return new Promise((resolve, reject) => {
					const onSuccess = ({data: {itemData, itemContainer, blob}}) => {
						if (fileToUpload === blob)
						{
							EventEmitter.unsubscribe(this.filesUploader, 'onUploadDone', onSuccess);
							EventEmitter.unsubscribe(this.filesUploader, 'onUploadDone', onFailed);
							resolve({image: {src: itemData.PREVIEW_URL}, html: this.getFileController().getParser().getItemHTML(itemData.ID)});
						}
					}
					EventEmitter.subscribe(this.filesUploader, 'onUploadDone', onSuccess);
					const onFailed = ({data: {blob}}) => {
						if (fileToUpload === blob)
						{
							EventEmitter.unsubscribe(this.filesUploader, 'onUploadDone', onSuccess);
							EventEmitter.unsubscribe(this.filesUploader, 'onUploadDone', onFailed);
							reject();
						}
					};
					EventEmitter.subscribe(this.filesUploader, 'onUploadDone', onFailed);
					this.filesUploader.upload([fileToUpload]);
				});
			});
			EventEmitter.subscribe(this.getEventObject(), 'onFilesHaveCaught', (event: BaseEvent) => {
				event.stopImmediatePropagation();
				this.filesUploader.upload([...event.getData()]);
			});

			EventEmitter.subscribe(this.filesUploader, 'onUploadDone', ({data: {itemData, itemContainer}}) => {
				this.getFileController().add(itemData, itemContainer);
			});
			EventEmitter.subscribe(this.filesUploader, 'onUploadIsStart', () => {
				EventEmitter.emit(this.getEventObject(), 'onBusy', this);
			});
			EventEmitter.subscribe(this.filesUploader, 'onUploadIsDone', () => {
				EventEmitter.emit(this.getEventObject(), 'onReady', this);
			});

		}
		return this.filesUploader;
	}

	getPanelController(): PanelController
	{
		return this.panelController;
	}

	show(values: ?Array)
	{
		const switcher = this.getContainer().parentNode.querySelector('#' + this.getContainer().id + '-switcher');
		if (switcher)
		{
			switcher.parentNode.removeChild(switcher);
		}
		if (this.getFileController().isPluggedIn())
		{
			this.getContainer().setAttribute('data-bx-plugged-in', 'Y');
		}
		super.show();
		this.showInitialLoader();
		this.getFileController().show();
		this.getPanelController().show();

		if (values)
		{
			this.getFileController()
				.set(values)
				.then(() => {
					this.hideInitialLoader();
				});
		}
		else
		{
			this.hideInitialLoader();
		}
	}

	hide()
	{
		super.hide();
		this.getFileController().hide();
		this.getPanelController().hide();
	}

	showInitialLoader()
	{
	}

	hideInitialLoader()
	{
	}

	static repo = {};

	static getInstance(data, values)
	{
		if (!this.repo[data['id']])
		{
			this.repo[data['id']] = new Form(data, values);
		}
		return this.repo[data['id']];
	}

	static getBriefInstance(data)
	{
		if (!this.repo[data['id']])
		{
			this.repo[data['id']] = new FormBrief(data, []);
		}
		return this.repo[data['id']];
	}
}

setTimeout(() => {
	if (BX.DiskFileDialog)
	{
		FileMover.subscribe();
	}
}, 1000);
