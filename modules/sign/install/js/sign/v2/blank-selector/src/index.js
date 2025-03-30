import { Tag, Loc, Dom, Event, Type, Reflection, Cache } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { Loader } from 'main.loader';
import { Popup } from 'main.popup';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { isTemplateMode } from 'sign.v2.sign-settings';
import { Layout } from 'ui.sidepanel.layout';
import { TileWidget } from 'ui.uploader.tile-widget';
import { UploaderEvent, type UploaderFile } from 'ui.uploader.core';
import { Api } from 'sign.v2.api';
import { ListItem } from './list-item';
import { Blank } from './blank';
import { BlankField } from './blank-field';
import type { BlankSelectorConfig, BlankData, ListItemProps, BlankProps } from './types/type';
import './style.css';

type RemoveOptions = {
	removeFromServer: boolean;
};

const uploaderOptions = {
	controller: 'sign.upload.blankUploadController',
	acceptedFileTypes: [
		'.jpg', '.jpeg',
		'.png', '.pdf',
		'.doc', '.docx',
		'.rtf', '.odt',
	],
	multiple: true,
	autoUpload: false,
	maxFileSize: 50 * 1024 * 1024,
	maxFileCount: 100,
	imageMaxFileSize: 10 * 1024 * 1024,
	maxTotalFileSize: 50 * 1024 * 1024,
};
const errorPopupOptions = {
	id: 'qwerty',
	padding: 20,
	offsetLeft: 40,
	offsetTop: -12,
	angle: true,
	darkMode: true,
	width: 300,
	autoHide: true,
	cacheable: false,
	bindOptions: {
		position: 'bottom',
	},
};

export { BlankField, ListItem };
export type { BlankSelectorConfig };

export class BlankSelector extends EventEmitter
{
	#cache = new Cache.MemoryCache();
	selectedBlankId: number;
	#blanks: Map<string, ListItem>;
	#tileWidget: TileWidget;
	#tileWidgetContainer: HTMLElement;
	#uploadButtonsContainer: HTMLElement;
	#relatedTarget: ?HTMLElement;
	#blanksContainer: HTMLElement;
	#page: number;
	#loadMoreButton: HTMLElement;
	#api: Api;
	#config: BlankSelectorConfig;

	constructor(config: BlankSelectorConfig)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.BlankSelector');
		this.subscribeFromOptions(config?.events ?? {});
		this.#config = config;
		this.selectedBlankId = 0;
		this.#blanks = new Map();
		this.#page = 0;
		const uploadButtons = this.#createUploadButtons();
		const dragArea = Tag.render`
			<label class="sign-blank-selector__list_drag-area-label">
				${Loc.getMessage('SIGN_BLANK_SELECTOR_DRAG_AREA')}
			</label>
		`;
		const widgetOptions = {
			slots: {
				afterDropArea: {
					computed: {
						title: () => Loc.getMessage('SIGN_BLANK_SELECTOR_CLEAR_ALL'),
					},
					methods: {
						clear: () => {
							this.clearFiles({ removeFromServer: false });
						},
					},
					template: `
						<span
							class="sign-blank-selector__tile-widget_clear-btn"
							:title="title"
							@click="clear()"
						>
						</span>
					`,
				},
			},
		};
		this.#uploadButtonsContainer = Tag.render`
			<div class="sign-blank-selector__list --with-buttons">
				${uploadButtons}
				${dragArea}
			</div>
		`;
		this.#tileWidget = new TileWidget({
			...uploaderOptions,
			...config.uploaderOptions,
			dropElement: this.#uploadButtonsContainer,
			browseElement: [...uploadButtons, dragArea],
			events: {
				[UploaderEvent.BEFORE_FILES_ADD]: (event) => this.#onFileBeforeAdd(event),
				[UploaderEvent.FILE_ADD]: (event) => this.#onFileAdd(event),
				[UploaderEvent.FILE_REMOVE]: (event) => this.#onFileRemove(event),
				[UploaderEvent.UPLOAD_START]: (event) => this.#onUploadStart(event),
			},
		}, widgetOptions);
		this.#relatedTarget = null;
		Event.bind(document, 'mousedown', (event) => {
			this.#relatedTarget = event.target;
		});
		this.#blanksContainer = Tag.render`
			<div
				class="sign-blank-selector__list"
				onfocusin="${({ target }) => {
					this.selectBlank(Number(target.dataset.id));
				}}"
				onclick="${({ target, ctrlKey, metaKey }) => {
					if (ctrlKey || metaKey)
					{
						this.resetSelectedBlank(Number(target.dataset.id), this.#relatedTarget);
					}
				}}"
			></div>
		`;
		this.#tileWidgetContainer = Tag.render`
			<div class="sign-blank-selector__tile-widget"></div>
		`;
		this.#loadMoreButton = Tag.render`
			<div class="sign-blank-selector__load-more --hidden">
				<span onclick="${() => this.#loadBlanks(this.#page + 1)}">
					${Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE')}
				</span>
			</div>
		`;
		this.#api = new Api();
	}

	#checkForFilesValid(addedFiles: UploaderFile[]): boolean
	{
		const isImage = (file) => file.getType().includes('image/');
		const allAddedImages = addedFiles.every((file) => isImage(file));
		const validExtension = addedFiles.every((file) => {
			// TODO merge with this.#config.uploaderOptions.acceptedFileTypes
			return uploaderOptions.acceptedFileTypes.includes(
				`.${file.getExtension()}`,
			);
		});
		if (!validExtension || (addedFiles.length > 1 && !allAddedImages))
		{
			return false;
		}

		const uploader = this.#tileWidget.getUploader();
		const files = uploader.getFiles();
		const filesLength = files.length;
		const imagesLimit = this.#getImagesLimit();
		if (filesLength === 0 && addedFiles.length === 1)
		{
			return true;
		}

		const allExistImages = files.every((file) => isImage(file));

		return allAddedImages
			&& allExistImages
			&& imagesLimit - filesLength >= addedFiles.length;
	}

	#onFileBeforeAdd(event: BaseEvent)
	{
		const { files: addedFiles } = event.getData();
		const valid = this.#checkForFilesValid(addedFiles);
		if (valid)
		{
			return;
		}

		let bindElement = this.#uploadButtonsContainer.firstElementChild;
		if (Dom.hasClass(this.#uploadButtonsContainer, '--hidden'))
		{
			const {
				$refs: { container },
			} = this.#tileWidget.getRootComponent();
			bindElement = container.firstElementChild;
		}

		const errorPopup = new Popup({
			...errorPopupOptions,
			bindElement,
			content: Loc.getMessage(
				'SIGN_BLANK_SELECTOR_UPLOAD_HINT',
				{ '%imageCountLimit%': this.#getImagesLimit() },
			),
		});
		errorPopup.show();
		event.preventDefault();
	}

	#getImagesLimit(): number
	{
		return Type.isInteger(parseInt(this.#config?.uploaderOptions?.maxFileCount, 10))
			? this.#config?.uploaderOptions?.maxFileCount
			: uploaderOptions.maxFileCount
		;
	}

	#onFileAdd(event: BaseEvent)
	{
		const title = event.data.file.getName();
		this.#toggleTileVisibility(true);
		this.resetSelectedBlank();
		this.emit('addFile', { title: this.#normalizeTitle(title) });
	}

	#onFileRemove(event: BaseEvent)
	{
		this.emit('removeFile');
		const uploader = this.#tileWidget.getUploader();
		const files = uploader.getFiles();
		if (files.length === 0)
		{
			this.#toggleTileVisibility(false);
			this.emit('clearFiles');
		}
	}

	#onUploadStart()
	{
		const uploader = this.#tileWidget.getUploader();
		const [firstFile] = uploader.getFiles();
		const title = firstFile.getName();
		const fileId = firstFile.getId();
		const uploadingBlank: Blank<BlankProps> = new Blank({ title });
		uploadingBlank.setReady(false);
		Dom.prepend(uploadingBlank.getLayout(), this.#blanksContainer);
		firstFile.setCustomData(fileId, uploadingBlank);
	}

	#toggleTileVisibility(shouldShow: boolean)
	{
		const hiddenClass = '--hidden';
		if (shouldShow)
		{
			Dom.removeClass(this.#tileWidgetContainer, hiddenClass);
			Dom.addClass(this.#uploadButtonsContainer, hiddenClass);

			return;
		}

		Dom.addClass(this.#tileWidgetContainer, hiddenClass);
		Dom.removeClass(this.#uploadButtonsContainer, hiddenClass);
		this.clearFiles({ removeFromServer: false });
	}

	#createUploadButtons(): Array<HTMLElement>
	{
		const buttons = {
			img: {
				title: Loc.getMessage('SIGN_BLANK_SELECTOR_CREATE_NEW_PIC'),
				description: 'jpeg, png',
			},
			pdf: {
				title: Loc.getMessage('SIGN_BLANK_SELECTOR_NEW_PDF'),
				description: 'Adobe Acrobat',
			},
			doc: {
				title: Loc.getMessage('SIGN_BLANK_SELECTOR_NEW_DOC'),
				description: 'doc, docx',
			},
		};
		const entries = Object.entries(buttons);

		return entries.map(([key, { title, description }]) => {
			const listItem: ListItem<ListItemProps> = new ListItem({
				title,
				description,
				modifier: key,
			});

			return listItem.getLayout();
		});
	}

	async #resumeUploading()
	{
		const uploader = this.#tileWidget.getUploader();
		const pendingFiles = uploader.getFiles();
		uploader.setMaxParallelUploads(pendingFiles.length);
		const uploadPromise = new Promise((resolve) => {
			uploader.subscribeOnce('onUploadComplete', resolve);
		});
		uploader.start();
		await uploadPromise;
	}

	async createBlankFromOuterUploaderFiles(files: Array<UploaderFile>): Promise<number>
	{
		if (files.length === 0)
		{
			return;
		}
		const firstFile = files.at(0);
		const blank = new Blank({ title: firstFile.getName() });
		blank.setReady(false);
		Dom.prepend(blank.getLayout(), this.#blanksContainer);
		try
		{
			const filesIds = files.map((file) => file.getServerFileId());
			const blankData = await this.#api.createBlank(
				filesIds,
				this.#config.type ?? null,
				isTemplateMode(this.#config.documentMode),
			);
			this.#setupBlank({
				...blankData,
				userName: Loc.getMessage('SIGN_BLANK_SELECTOR_CREATED_MYSELF'),
			}, blank);

			return blankData.id;
		}
		catch (ex)
		{
			blank?.remove?.();
			console.log(ex);
			throw ex;
		}
	}

	async createBlank(): ?Promise<number>
	{
		const uploader = this.#tileWidget.getUploader();
		const files = uploader.getFiles();
		if (files.length === 0)
		{
			return;
		}
		const [firstFile] = files;
		await this.#resumeUploading();
		const blank = firstFile.getCustomData(firstFile.getId());
		try
		{
			const filesIds = files.map((file) => file.getServerFileId());
			const blankData = await this.#api.createBlank(filesIds, this.#config.type ?? null, isTemplateMode(this.#config.documentMode));
			this.#setupBlank({
				...blankData,
				userName: Loc.getMessage('SIGN_BLANK_SELECTOR_CREATED_MYSELF'),
			}, blank);

			return blankData.id;
		}
		catch (ex)
		{
			blank.remove();
			throw ex;
		}
	}

	async #loadBlanks(page: number)
	{
		const loader = new Loader({
			target: this.#blanksContainer,
			size: 80,
			mode: 'custom',
		});
		loader.show();
		try
		{
			const blanksOnPage = 3;
			const data = await this.#api.loadBlanks(page, this.#config.type ?? null, blanksOnPage);
			if (data.length < blanksOnPage)
			{
				Dom.addClass(this.#loadMoreButton, '--hidden');
			}
			else
			{
				Dom.removeClass(this.#loadMoreButton, '--hidden');
			}

			if (data.length > 0)
			{
				data.forEach((blankData: BlankData) => {
					if (this.hasBlank(blankData.id))
					{
						return;
					}

					const { title } = blankData;
					const blank: Blank<BlankProps> = new Blank({ title });
					this.#addBlank(blankData, blank);
				});
				this.#page = page;
			}
		}
		catch
		{
			Dom.removeClass(this.#loadMoreButton, '--hidden');
		}

		loader.destroy();
	}

	#setupBlank(blankData: BlankData, blank: Blank): void
	{
		const {
			id: blankId,
			previewUrl,
			userAvatarUrl,
			userName,
			dateCreate,
		} = blankData;
		const creationDate = dateCreate ? new Date(dateCreate) : new Date();
		const descriptionText = `${userName}, ${DateTimeFormat.format('j M. Y', creationDate)}`;
		blank.setId(blankId);
		blank.setReady(true);
		blank.setPreview(previewUrl);
		blank.setAvatarWithDescription(descriptionText, userAvatarUrl);
		this.#blanks.set(blankId, blank);
	}

	#normalizeTitle(title: string): string
	{
		const acceptedType = uploaderOptions.acceptedFileTypes.find((fileType) => {
			return title.endsWith(fileType);
		});
		if (!acceptedType)
		{
			return title;
		}

		const dotExtensionIndex = title.lastIndexOf(acceptedType);

		return title.slice(0, dotExtensionIndex);
	}

	#addBlank(blankData: BlankData, blank: Blank): void
	{
		this.#setupBlank(blankData, blank);
		Dom.append(blank.getLayout(), this.#blanksContainer);
	}

	resetSelectedBlank()
	{
		const blank = this.#blanks.get(this.selectedBlankId);
		blank?.deselect();
		this.selectedBlankId = 0;
		if (blank)
		{
			this.emit('toggleSelection', { selected: false });
		}
		this.#enableSaveButtonIntoSlider();
	}

	async modifyBlankTitle(blankId: number, blankTitle: string): void
	{
		let blank = this.#blanks.get(blankId);
		if (!blank)
		{
			await this.loadBlankById(blankId);
			blank = this.#blanks.get(blankId);
		}
		blank.setTitle(blankTitle);
	}

	hasBlank(blankId: number): boolean
	{
		return this.#blanks.has(blankId);
	}

	getBlank(blankId: number): Blank
	{
		return this.#blanks.get(blankId);
	}

	async loadBlankById(blankId: number): Promise<void>
	{
		const blankData = await this.#api.getBlankById(blankId);
		if (!this.hasBlank(blankId))
		{
			const blank: Blank<BlankProps> = new Blank({ title: blankData.title });
			this.#addBlank(blankData, blank);
		}
	}

	async selectBlank(blankId: number)
	{
		if (blankId !== this.selectedBlankId)
		{
			this.resetSelectedBlank();
		}

		this.selectedBlankId = blankId;
		this.#toggleTileVisibility(false);
		let blank = this.#blanks.get(blankId);

		if (!blank)
		{
			await this.loadBlankById(blankId);
			blank = this.#blanks.get(blankId);
		}
		const { title } = blank.getProps();
		blank.select();
		this.emit('toggleSelection', { id: blankId, selected: true, title: this.#normalizeTitle(title) });
	}

	deleteBlank(blankId: number)
	{
		const lastBlank = this.#blanks.get(blankId);
		if (lastBlank)
		{
			this.#blanks.delete(blankId);
			lastBlank.remove();
		}
	}

	clearFiles(options: RemoveOptions)
	{
		const uploader = this.#tileWidget.getUploader();
		uploader.removeFiles(options);
	}

	isFilesReadyForUpload(): boolean
	{
		if (this.#tileWidget.getUploader().getFiles().length === 0)
		{
			return false;
		}

		return this.#tileWidget.getUploader().getFiles()
			.every((file: UploaderFile) => file.getErrors().length <= 0)
		;
	}

	getLayout(): HTMLElement
	{
		this.#tileWidget.renderTo(this.#tileWidgetContainer);
		this.#toggleTileVisibility(false);
		const canUploadNewBlank = this.#config.canUploadNewBlank ?? true;
		const selectorContainer = Tag.render`
			<div class="sign-blank-selector">
				${this.#tileWidgetContainer}
				${canUploadNewBlank ? this.#uploadButtonsContainer : ''}
				<p class="sign-blank-selector__templates_title">
					${Loc.getMessage('SIGN_BLANK_SELECTOR_RECENT_TEMPLATES_TITLE')}
				</p>
				${this.#blanksContainer}
				${this.#loadMoreButton}
			</div>
		`;
		if (this.#page === 0)
		{
			this.#loadBlanks(1);
		}

		return selectorContainer;
	}

	openInSlider()
	{
		const SidePanel: BX.SidePanel = Reflection.getClass('BX.SidePanel');
		if (!Type.isNil(SidePanel))
		{
			SidePanel.Instance.open('v2-blank-selector', {
				width: 628,
				cacheable: false,
				events: {
					onClose: () => {
						this.emit('onSliderClose');
					},
				},
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['sign.v2.blank-selector'],
						title: Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_TITLE'),
						content: () => this.getLayout(),
						buttons: ({ cancelButton, SaveButton }) => {
							this.#setSaveButtonIntoSlider(
								new SaveButton({
									text: Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_SELECT_BLANK_BUTTON_LABEL'),
									onclick: () => {
										SidePanel.Instance.close();
									},
								}),
							);

							this.#disableSaveButtonIntoSlider();

							return [
								this.#getSaveButtonIntoSlider(),
								cancelButton,
							];
						},
					});
				},
			});
		}
	}

	#setSaveButtonIntoSlider(button)
	{
		this.#cache.set('saveButton', button);
	}

	#disableSaveButtonIntoSlider()
	{
		const saveButton = this.#getSaveButtonIntoSlider();
		saveButton?.setDisabled(true);
	}

	#enableSaveButtonIntoSlider()
	{
		const saveButton = this.#getSaveButtonIntoSlider();
		saveButton?.setDisabled(false);
	}

	#getSaveButtonIntoSlider(): any
	{
		return this.#cache.get('saveButton');
	}

	disableSelectedBlank(blankId: number): void
	{
		const blank = this.#blanks.get(blankId);

		if (blank)
		{
			Dom.addClass(blank.getLayout(), '--disabled');
		}
	}

	enableSelectedBlank(blankId: number): void
	{
		const blank = this.#blanks.get(blankId);

		if (blank)
		{
			Dom.removeClass(blank.getLayout(), '--disabled');
		}
	}
}
