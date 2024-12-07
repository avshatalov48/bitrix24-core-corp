import { Type, Event, Reflection, Loc, Tag } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { FileEvent, UploaderFile, UploaderError, getFilesFromDataTransfer, isFilePasted } from 'ui.uploader.core';

import HtmlParser from './html-parser';

import type { TileWidgetItem } from 'ui.uploader.tile-widget';
import type { VueUploaderAdapter } from 'ui.uploader.vue';
import type UserFieldControl from '../user-field-control';

export default class MainPostForm extends EventEmitter
{
	#userFieldControl: UserFieldControl = null;
	#createDocumentButton: HTMLElement = null;
	#eventObject: HTMLElement = null;
	#htmlParser: HtmlParser = null;
	#htmlEditor = null;
	#inited = false;

	constructor(userFieldControl: UserFieldControl, options: Object<string, any>)
	{
		super();
		this.setEventNamespace('BX.Disk.Uploader.Integration');

		this.#userFieldControl = userFieldControl;
		this.#eventObject = options.eventObject;

		this.#bindEventObject();

		this.subscribeFromOptions(options.events);

		this.subscribeOnce('onReady', (): void => {
			if (this.#userFieldControl.canCreateDocuments())
			{
				this.#addCreateDocumentButton();
			}
		});

		this.#userFieldControl.subscribe('onUploaderPanelToggle', this.#handleUploaderPanelToggle.bind(this));
		this.#userFieldControl.subscribe('onDocumentPanelToggle', this.#handleDocumentPanelToggle.bind(this));

		Event.ready(this.#handleDocumentReady.bind(this));
	}

	getUserFieldControl(): UserFieldControl
	{
		return this.#userFieldControl;
	}

	getParser(): HtmlParser
	{
		return this.#htmlParser;
	}

	/**
	 *
	 * @returns {BXEditor}
	 */
	getHtmlEditor()
	{
		return this.#htmlEditor;
	}

	getEventObject(): HTMLElement
	{
		return this.#eventObject;
	}

	selectFileButton(): void
	{
		const event: BaseEvent = new BaseEvent({
			data: 'show',
			// needs to determine our own event (main.post.form emits onShowControllers as well)
			compatData: ['user-field-widget'],
		});

		EventEmitter.emit(this.#eventObject, 'onShowControllers', event);
	}

	deselectFileButton(): void
	{
		const event: BaseEvent = new BaseEvent({
			data: 'hide',
			// needs to determine our own event (main.post.form emits onShowControllers as well)
			compatData: ['user-field-widget'],
		});

		EventEmitter.emit(this.#eventObject, 'onShowControllers', event);
	}

	selectCreateDocumentButton(): void
	{
		if (this.#createDocumentButton)
		{
			const container: HTMLElement = this.#createDocumentButton.closest('[data-id="disk-document"]');
			if (container)
			{
				container.setAttribute('data-bx-button-status', 'active');
			}
		}
	}

	deselectCreateDocumentButton(): void
	{
		if (this.#createDocumentButton)
		{
			const container: HTMLElement = this.#createDocumentButton.closest('[data-id="disk-document"]');
			if (container)
			{
				container.removeAttribute('data-bx-button-status');
			}
		}
	}

	#handleDocumentReady(): void
	{
		const postForm = this.#getPostForm();
		if (postForm === null)
		{
			setTimeout(() => {
				const postForm = this.#getPostForm();
				if (postForm)
				{
					this.#handlePostFormReady(postForm);
				}
				else
				{
					console.error('Disk User Field: Post Form Not Found.');
				}
			}, 100);
		}
		else
		{
			this.#handlePostFormReady(postForm);
		}
	}

	#handlePostFormReady(postForm: PostForm)
	{
		if (postForm.isReady)
		{
			this.#init(postForm);
		}
		else
		{
			EventEmitter.subscribe(postForm, 'OnEditorIsLoaded', (): void => {
				this.#init(postForm);
			});
		}
	}

	/**
	 *
	 * @param {PostForm} postForm
	 */
	#init(postForm)
	{
		this.#bindAdapterEvents();

		this.#htmlEditor = postForm.getEditor();
		this.#htmlParser = new HtmlParser(this);

		EventEmitter.subscribe(this.#htmlEditor, 'OnContentChanged', (event: BaseEvent) => {
			this.#htmlParser.syncHighlightsDebounced();
		});

		EventEmitter.subscribe(this.#htmlEditor, 'BXEditor:onBeforePasteAsync', (event: BaseEvent) => {
			return new Promise((resolve, reject): void => {
				const clipboardEvent: ClipboardEvent = event.getData().clipboardEvent;
				const clipboardData: DataTransfer = clipboardEvent.clipboardData;

				clipboardEvent.stopImmediatePropagation(); // Skip HTML Editor InitClipboardHandler
				if (!clipboardData || !isFilePasted(clipboardData))
				{
					resolve();

					return;
				}

				clipboardEvent.preventDefault(); // Prevent Browser behavior
				event.preventDefault(); // Prevent invoking HTMLEditor Paste Handler (OnPasteHandler)

				getFilesFromDataTransfer(clipboardData)
					.then((files: File[]): void => {
						files.forEach((file: File): void => {
							this.getUserFieldControl().getUploader().addFile(file, {
								events: {
									[FileEvent.LOAD_ERROR]: () => {},
									[FileEvent.UPLOAD_ERROR]: () => {},
									[FileEvent.LOAD_COMPLETE]: (event: BaseEvent): void => {
										// const file: UploaderFile = event.getTarget();
										// const item: TileWidgetItem = this.getUserFieldControl().getItem(file.getId());
										// We could try insert a file/image stub.
										// if (item)
										// {
										// 	this.getUserFieldControl().show();
										// 	this.getParser().insertFile(item);
										// }
									},
									[FileEvent.UPLOAD_COMPLETE]: (event: BaseEvent): void => {
										const uploadedFile: UploaderFile = event.getTarget();
										this.getUserFieldControl().showUploaderPanel();
										this.getParser().insertFile(uploadedFile);
									},
								},
							});
						});

						resolve();
					})
					.catch((): void => {
						resolve();
					})
				;
			});
		});

		this.emit('onReady');

		this.#inited = true;
	}

	#getPostForm()
	{
		const PostForm: Class = Reflection.getClass('BX.Main.PostForm');
		if (!PostForm)
		{
			return null;
		}

		let result = null;
		PostForm.repo.forEach((editor) => {
			if (editor.getEventObject() === this.getEventObject())
			{
				result = editor;
			}
		});

		return result;
	}

	#bindEventObject(): void
	{
		// Show / Hide files control panel
		EventEmitter.subscribe(this.getEventObject(), 'onShowControllers', (event: BaseEvent): void => {
			if (Type.isArrayFilled(event.getCompatData()) && event.getCompatData()[0] === 'user-field-widget')
			{
				// Skip our own event (main.post.form emits onShowControllers as well).
				return;
			}

			const status = Type.isArray(event.getData()) ? event.getData().shift() : event.getData();
			if (status === 'show')
			{
				this.getUserFieldControl().showUploaderPanel();
			}
			else
			{
				this.getUserFieldControl().hide();
			}
		});

		// Inline a post/comment editing
		EventEmitter.subscribe(this.getEventObject(), 'onReinitializeBeforeAsync', (event: BaseEvent): Promise => {
			return new Promise((resolve): void => {
				if (this.#inited)
				{
					this.#handleReinitializeBefore(event).then(() => resolve());
				}
				else
				{
					this.subscribeOnce('onReady', (): void => {
						this.#handleReinitializeBefore(event).then(() => resolve());
					});
				}
			});
		});

		// Some components get attachments from main.post.form via arFiles and controllers properties.
		// See main.post.form/templates/.default/src/editor.js:778
		// See timeline/src/commenteditor.js:320
		EventEmitter.subscribe(this.getEventObject(), 'onCollectControllers', (event: BaseEvent): void => {
			const data = event.getData();

			const fieldName: string = this.getUserFieldControl().getUploader().getHiddenFieldName();
			const ids = this.getUserFieldControl().getItems().map((item: TileWidgetItem): number | string => {
				return item.serverFileId;
			});

			data[fieldName] = {
				storage: 'disk',
				tag: '[DISK FILE ID=#id#]',
				values: ids,
				handler: {
					selectFile: (tab, path, selected): void => {
						Object.values(selected).forEach((item): void => {
							this.getUserFieldControl().getUploader().addFile(item);
						});
					},
					removeFiles: (files): void => {
						if (files !== undefined && Array.isArray(files))
						{
							const uploader = this.getUserFieldControl().getUploader();
							const uploadFiles = uploader.getFiles();
							let filteredFiles = files.map(item => uploadFiles.find(uploadFile => uploadFile.getServerFileId() === item).getId());

							filteredFiles.forEach(file => {
								uploader.removeFile(file);
							});
						}
					},
				},
			};
		});

		// Video records
		EventEmitter.subscribe(this.getEventObject(), 'OnVideoHasCaught', (event: BaseEvent): void => {
			event.stopImmediatePropagation();
			this.getUserFieldControl().getUploader().addFile(event.getData(), {
				events: {
					[FileEvent.UPLOAD_COMPLETE]: (event: BaseEvent): void => {
						const file: UploaderFile = event.getTarget();
						this.getUserFieldControl().showUploaderPanel();
						this.getParser().insertFile(file);
					},
				},
			});
		});

		// An old approach (see BXEditor:onBeforePasteAsync) to process images from clipboard. Just in case.
		EventEmitter.subscribe(this.getEventObject(), 'OnImageHasCaught', (event: BaseEvent): void => {
			event.stopImmediatePropagation();

			return new Promise((resolve, reject): void => {
				this.getUserFieldControl().getUploader().addFile(event.getData(), {
					events: {
						[FileEvent.LOAD_ERROR]: (event: BaseEvent): void => {
							const error: UploaderError = event.getData().error;
							reject(error);
						},
						[FileEvent.UPLOAD_ERROR]: () => (event: BaseEvent): void => {
							const error: UploaderError = event.getData().error;
							reject(error);
						},
						[FileEvent.UPLOAD_COMPLETE]: (event: BaseEvent): void => {
							const file: UploaderFile = event.getTarget();
							const item: TileWidgetItem = this.getUserFieldControl().getItem(file.getId());
							if (item)
							{
								this.getParser().syncHighlights();

								resolve({
									image: {
										src: file.getPreviewUrl(),
										width: file.getPreviewWidth(),
										height: file.getPreviewHeight(),
									},
									html: this.getParser().createItemHtml(file),
								});
							}
							else
							{
								reject(new UploaderError('WRONG_FILE_SOURCE'));
							}
						},
					},
				});
			});
		});

		// Files from Drag&Drop
		EventEmitter.subscribe(this.getEventObject(), 'onFilesHaveCaught', (event: BaseEvent): void => {
			// Skip this because an event doesn't have all Drag&Drop data
			event.stopImmediatePropagation();
		});

		EventEmitter.subscribe(this.getEventObject(), 'onFilesHaveDropped', (event: BaseEvent): void => {
			event.stopImmediatePropagation();

			const dragEvent: DragEvent = event.getData().event;

			getFilesFromDataTransfer(dragEvent.dataTransfer)
				.then((files: File[]): void => {
					this.getUserFieldControl().getUploader().addFiles(files);
				})
				.catch(() => {})
			;
		});
	}

	#bindAdapterEvents(): void
	{
		// Button counter: File -> File (1) -> File (2)
		const adapter: VueUploaderAdapter = this.getUserFieldControl().getAdapter();
		adapter.subscribe('Item:onAdd', (): void => {
			EventEmitter.emit(this.getEventObject(), 'onShowControllers:File:Increment');
		});

		adapter.subscribe('Item:onRemove', (event: BaseEvent<{ item: TileWidgetItem }>): void => {
			EventEmitter.emit(this.getEventObject(), 'onShowControllers:File:Decrement');
			const item: TileWidgetItem = event.getData().item;

			if (this.getParser())
			{
				this.getParser().removeFile(item);
			}
		});
	}

	/**
	 * This method invokes for inline entity editing
	 * @param event
	 */
	#handleReinitializeBefore(event: BaseEvent): Promise
	{
		this.getUserFieldControl().clear();

		const [, userFields] = event.getData();
		const fieldName: string = this.getUserFieldControl().getUploader().getHiddenFieldName();
		const userField = (
			userFields && userFields[fieldName] && userFields[fieldName]['USER_TYPE_ID'] === 'disk_file'
				? userFields[fieldName]
				: null
		);

		if (userField !== null)
		{
			// existing entity
			if (
				Type.isPlainObject(userField['CUSTOM_DATA'])
				&& Type.isStringFilled(userField['CUSTOM_DATA']['PHOTO_TEMPLATE'])
			)
			{
				this.getUserFieldControl().setPhotoTemplateMode('manual');
				this.getUserFieldControl().setPhotoTemplate(userField['CUSTOM_DATA']['PHOTO_TEMPLATE']);
			}
			else
			{
				this.getUserFieldControl().setPhotoTemplateMode('auto');
			}
		}
		else
		{
			// new entity
			this.getUserFieldControl().setPhotoTemplateMode('auto');
			this.getUserFieldControl().setPhotoTemplate('grid');
		}

		if (userField === null)
		{
			return Promise.resolve();
		}

		// nextTick needs to unmount a TileList component after clear().
		// Component unmounting resets an auto collapse.
		if (Type.isArray(userField['FILES']))
		{
			return this.#userFieldControl.nextTick().then(() => {
				userField['FILES'].forEach(file => {
					if (!this.getUserFieldControl().getUploader().getFile(file.serverFileId))
					{
						this.getUserFieldControl().getUploader().addFile(file);
					}
				});

				if (this.getUserFieldControl().getUploader().getFiles().length > 0)
				{
					this.#userFieldControl.enableAutoCollapse();
				}
			});
		}
		else if (Type.isArrayFilled(userField['VALUE']))
		{
			return this.#userFieldControl.nextTick().then((): Promise => {
				return new Promise((resolve): void => {
					let fileIds: number[] = userField['VALUE'];
					fileIds = fileIds.filter((id: number) => !this.getUserFieldControl().getUploader().getFile(id));

					let loaded = 0;
					let addedFiles: UploaderFile[] = [];
					const onLoad = () => {
						loaded++;
						if (loaded === addedFiles.length)
						{
							resolve();
						}
					};

					const events = {
						[FileEvent.LOAD_COMPLETE]: onLoad,
						[FileEvent.LOAD_ERROR]: onLoad,
					};

					const fileOptions = fileIds.map((id: number) => [id, { events }]);
					if (fileOptions.length > 0)
					{
						addedFiles = this.getUserFieldControl().getUploader().addFiles(fileOptions);
						if (addedFiles.length === 0)
						{
							resolve();
						}
						else
						{
							this.#userFieldControl.enableAutoCollapse();
						}
					}
					else
					{
						resolve();
					}
				});
			});
		}

		return Promise.resolve();
	}

	#addCreateDocumentButton(): void
	{
		this.#createDocumentButton = Tag.render`
			<div onclick="${this.#handleButtonClick.bind(this)}">
				<i></i>
				${Loc.getMessage('DISK_UF_WIDGET_CREATE_DOCUMENT')}
			</div>
		`;

		EventEmitter.emit(
			this.getEventObject(),
			'OnAddButton',
			[{ BODY: this.#createDocumentButton, ID: 'disk-document' }, 'file'],
		);
	}

	#handleButtonClick(): void
	{
		const container: HTMLElement = this.#createDocumentButton.closest('[data-id="disk-document"]');
		if (container && container.hasAttribute('data-bx-button-status'))
		{
			this.getUserFieldControl().hide();
		}
		else
		{
			this.getUserFieldControl().showDocumentPanel();
		}
	}

	insertIntoText(item: TileWidgetItem): void
	{
		const file: UploaderFile = this.#userFieldControl.getFile(item.id);
		this.#htmlParser.insertFile(file);
	}

	#handleUploaderPanelToggle(event: BaseEvent): void
	{
		const isOpen: boolean = event.getData().isOpen;
		if (isOpen)
		{
			this.selectFileButton();
		}
		else
		{
			this.deselectFileButton();
		}
	}

	#handleDocumentPanelToggle(event: BaseEvent): void
	{
		const isOpen: boolean = event.getData().isOpen;
		if (isOpen)
		{
			this.selectCreateDocumentButton();
		}
		else
		{
			this.deselectCreateDocumentButton();
		}
	}
}
