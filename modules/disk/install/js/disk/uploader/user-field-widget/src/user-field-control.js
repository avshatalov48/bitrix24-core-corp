import { Type, Dom, Tag, Extension, Text } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { UploaderFile, Uploader } from 'ui.uploader.core';

import MainPostForm from './integration/main-post-form';

import type { TileWidgetItem } from 'ui.uploader.tile-widget';
import type { VueUploaderAdapter } from 'ui.uploader.vue';
import type { UserFieldWidgetOptions } from './user-field-widget-options';

const instances: Map<string, UserFieldControl> = new Map();

export default class UserFieldControl extends EventEmitter
{
	#id: ?string = null;
	#adapter: VueUploaderAdapter = null;
	#mainPostForm: MainPostForm = null;

	#allowDocumentFieldName: string = null;

	#photoTemplateFieldName: string = null;
	#photoTemplateInput: HTMLInputElement = null;
	#photoTemplateMode: 'auto' | 'manual' = 'auto';

	#widgetComponent = null;

	constructor(widgetComponent)
	{
		super();
		this.setEventNamespace('BX.Disk.Uploader.Integration');

		this.#widgetComponent = widgetComponent;
		this.#adapter = widgetComponent.adapter;

		const options: UserFieldWidgetOptions = (
			Type.isPlainObject(widgetComponent.widgetOptions) ? widgetComponent.widgetOptions : {}
		);

		this.#photoTemplateFieldName = (
			Type.isStringFilled(options.photoTemplateFieldName) ? options.photoTemplateFieldName : null
		);

		this.#allowDocumentFieldName = (
			Type.isStringFilled(options.allowDocumentFieldName) ? options.allowDocumentFieldName : null
		);

		this.#adapter.subscribe('Item:onAdd', (event: BaseEvent<{ item: TileWidgetItem }>): void => {
			const item: TileWidgetItem = event.getData().item;

			this.emit('Item:onAdd', { item });
		});

		this.#adapter.subscribe('Item:onComplete', (event: BaseEvent<{ item: TileWidgetItem }>): void => {
			const item: TileWidgetItem = event.getData().item;
			this.setDocumentEdit(item);

			this.emit('Item:onComplete', { item });
		});

		this.#adapter.subscribe('Item:onRemove', (event: BaseEvent<{ item: TileWidgetItem }>): void => {
			const item: TileWidgetItem = event.getData().item;
			this.removeAllowDocumentEditInput(item);

			this.emit('Item:onRemove', { item });
		});

		if (options.disableLocalEdit)
		{
			// it would be better to load disk.document on demand
			BX.Disk.Document.Local.Instance.disable();
		}

		if (this.#photoTemplateFieldName !== null && this.getUploader().getHiddenFieldsContainer() !== null)
		{
			this.#photoTemplateInput = Tag.render`
				<input 
					name="${this.#photoTemplateFieldName}" 
					value="${Type.isStringFilled(options.photoTemplate) ? options.photoTemplate : 'grid'}"
					type="hidden" 
				/>
			`;

			this.setPhotoTemplateMode(options.photoTemplateMode);

			Dom.append(this.#photoTemplateInput, this.getUploader().getHiddenFieldsContainer());
		}

		if (
			this.getUploader().getHiddenFieldsContainer() === null
			&& (this.#photoTemplateFieldName !== null || this.#allowDocumentFieldName !== null)
		)
		{
			// eslint-disable-next-line no-console
			console.warn(
				'DiskUserField: to use "photoTemplateFieldName" or "allowDocumentFieldName" options '
				+ 'you have to set "hiddenFieldsContainer" in the uploader options.',
			);
		}

		this.subscribeFromOptions(options.events);

		const eventObject: ?HTMLElement = Type.isElementNode(options.eventObject) ? options.eventObject : null;
		if (eventObject)
		{
			this.#mainPostForm = new MainPostForm(
				this,
				{
					eventObject,
					events: {
						onReady: (): void => {
							this.getUploader().addFiles(options.files);
							if (this.getUploader().getFiles().length > 0)
							{
								this.showUploaderPanel();
								this.enableAutoCollapse();
							}
						},
					},
				},
			);
		}
		else
		{
			this.getUploader().addFiles(options.files);
		}

		this.#id = (
			Type.isStringFilled(options.mainPostFormId)
				? options.mainPostFormId
				: `user-field-control-${Text.getRandom().toLowerCase()}`
		);

		instances.set(this.#id, this);
	}

	static getById(id: string): ?UserFieldControl
	{
		return instances.get(id) || null;
	}

	static getInstances(): UserFieldControl[]
	{
		return [...instances.values()];
	}

	canCreateDocuments(): boolean
	{
		const settings = Extension.getSettings('disk.uploader.user-field-widget');
		const canCreateDocuments = settings.get('canCreateDocuments', false);

		return canCreateDocuments && this.#widgetComponent.widgetOptions.canCreateDocuments !== false;
	}

	getAdapter(): VueUploaderAdapter
	{
		return this.#adapter;
	}

	getMainPostForm(): ?MainPostForm
	{
		return this.#mainPostForm;
	}

	getItems(): TileWidgetItem[]
	{
		return this.#adapter.getItems();
	}

	getItem(id): ?TileWidgetItem
	{
		return this.#adapter.getItem(id);
	}

	getFiles(): UploaderFile[]
	{
		return this.#adapter.getUploader().getFiles();
	}

	getFile(id: string): ?UploaderFile
	{
		return this.#adapter.getUploader().getFile(id);
	}

	getUploader(): Uploader
	{
		return this.#adapter.getUploader();
	}

	nextTick(): Promise
	{
		return this.#widgetComponent.$nextTick();
	}

	hide(): void
	{
		this.#widgetComponent.priorityVisibility = 'hidden';
		this.emit('onUploaderPanelToggle', { isOpen: false });
		this.emit('onDocumentPanelToggle', { isOpen: false });
	}

	showUploaderPanel(): void
	{
		this.#widgetComponent.priorityVisibility = 'uploader';
		this.emit('onUploaderPanelToggle', { isOpen: true });
		this.emit('onDocumentPanelToggle', { isOpen: false });
	}

	showDocumentPanel(): void
	{
		if (!this.canCreateDocuments())
		{
			return;
		}

		this.#widgetComponent.priorityVisibility = 'documents';
		this.emit('onUploaderPanelToggle', { isOpen: false });
		this.emit('onDocumentPanelToggle', { isOpen: true });
	}

	clear(): void
	{
		this.getUploader().removeFiles({ removeFromServer: false });
	}

	enableAutoCollapse(): void
	{
		this.#widgetComponent.enableAutoCollapse();
	}

	canAllowDocumentEdit(): boolean
	{
		return this.#allowDocumentFieldName !== null && this.getUploader().getHiddenFieldsContainer() !== null;
	}

	canItemAllowEdit(item: TileWidgetItem): boolean
	{
		return (
			this.canAllowDocumentEdit()
			&& item.customData.isEditable === true
			&& item.customData.canUpdate === true
		);
	}

	getAllowDocumentEditInput(item: TileWidgetItem): ?HTMLInputElement
	{
		const selector = `input[name='${this.#allowDocumentFieldName}[${item.serverFileId}]']`;

		if (this.getUploader().getHiddenFieldsContainer() !== null)
		{
			return this.getUploader().getHiddenFieldsContainer().querySelector(selector);
		}

		return null;
	}

	removeAllowDocumentEditInput(item: TileWidgetItem): void
	{
		const input: HTMLInputElement = this.getAllowDocumentEditInput(item);
		if (input !== null)
		{
			Dom.remove(input);
		}
	}

	setDocumentEdit(item: TileWidgetItem, allowEdit: ?boolean = null): void
	{
		if (!this.canItemAllowEdit(item))
		{
			return;
		}

		let input: HTMLInputElement = this.getAllowDocumentEditInput(item);
		if (input === null)
		{
			input = Tag.render`<input name="${this.#allowDocumentFieldName}[${item.serverFileId}]" type="hidden" />`;
			Dom.append(input, this.getUploader().getHiddenFieldsContainer());
		}

		allowEdit = allowEdit === null ? item.customData.allowEdit === true : allowEdit;
		input.value = allowEdit ? 1 : 0;

		const file: UploaderFile = this.getFile(item.id);
		file.setCustomData('allowEdit', allowEdit);
	}

	canChangePhotoTemplate(): boolean
	{
		return this.#photoTemplateInput !== null;
	}

	setPhotoTemplate(name: string): void
	{
		if (Type.isStringFilled(name) && this.#photoTemplateInput !== null)
		{
			this.#photoTemplateInput.value = name;
		}
	}

	getPhotoTemplate(): string
	{
		return this.#photoTemplateInput !== null ? this.#photoTemplateInput.value : '';
	}

	setPhotoTemplateMode(mode: 'auto' | 'manual'): void
	{
		if (mode === 'auto' || mode === 'manual')
		{
			this.#photoTemplateMode = mode;
		}
	}

	getPhotoTemplateMode(): 'auto' | 'manual'
	{
		return this.#photoTemplateMode;
	}

	getDocumentServices(): Object<string, Array<{ name: string, code: string }>>
	{
		const settings = Extension.getSettings('disk.uploader.user-field-widget');
		const documentHandlers = settings.get('documentHandlers', {});
		if (Type.isPlainObject(documentHandlers))
		{
			return documentHandlers;
		}

		return {};
	}

	getCurrentDocumentService(): { name: string, code: string } | null
	{
		let currentServiceCode = BX.Disk.getDocumentService();
		if (!currentServiceCode && BX.Disk.isAvailableOnlyOffice())
		{
			currentServiceCode = 'onlyoffice';
		}
		else if (!currentServiceCode)
		{
			currentServiceCode = 'l';
		}

		return this.getDocumentServices()[currentServiceCode] || null;
	}

	getImportServices(): Object<string, Array<{ name: string, code: string }>>
	{
		const settings = Extension.getSettings('disk.uploader.user-field-widget');
		const importHandlers = settings.get('importHandlers', {});
		if (Type.isPlainObject(importHandlers))
		{
			return importHandlers;
		}

		return {};
	}

	canUseImportService(): boolean
	{
		const settings = Extension.getSettings('disk.uploader.user-field-widget');

		return settings.get('canUseImport', true);
	}

	getImportFeatureId(): string
	{
		const settings = Extension.getSettings('disk.uploader.user-field-widget');

		return settings.get('importFeatureId', '');
	}

	isBoardsEnabled(): boolean
	{
		const settings = Extension.getSettings('disk.uploader.user-field-widget');

		return settings.get('isBoardsEnabled', '');
	}
}
