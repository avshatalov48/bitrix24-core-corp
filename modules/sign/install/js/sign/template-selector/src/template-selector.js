import {Cache, Tag, Dom, Loc, Type, Reflection, Runtime} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {Layout} from 'ui.sidepanel.layout';
import {Loader} from 'main.loader';
import {Uploader, UploaderOptions} from 'ui.uploader.core';
import 'sidepanel';
import Backend from './backend/backend';
import ListItem from './list-item/list-item';

import './css/style.css';

type TemplateSelectorOptions = {
	upload?: {
		enabled?: boolean,
	},
	templatesList?: {
		editable?: boolean,
	},
	uploaderOptions?: UploaderOptions,
	state?: {
		selectedTemplateId?: string | number,
	},
	events?: {
		[key: string]: (event: BaseEvent) => void,
	},
};

/**
 * @namespace BX.Sign
 */
export class TemplateSelector extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: TemplateSelectorOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.TemplateSelector');
		this.subscribeFromOptions(options?.events);
		this.#setOptions(options);

		this.#cache.remember('fileUploader', () => {
			return new Uploader({
				controller: 'ui.dev.fileUploader.testUploaderController',
				controllerOptions: {
					action: 'createByFiles',
				},
				browseElement: this.getUploadItems().map((item: ListItem) => {
					return item.getLayout();
				}),
				acceptedFileTypes: ['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx', '.rtf'],
				multiple: true,
				events: {
					'File:onAdd': (event: BaseEvent) => {
						const {file} = event.getData();
						const newTemplate = new ListItem({
							id: file.id,
							title: file.clientPreview.name,
							iconClass: 'ui-icon sign-template-selector-last-templates-list-item-icon-image',
							iconBackground: 'blue',
							events: {
								onClick: this.#onLastTemplatesListItemClick.bind(this),
								onEditClick: this.#onLastTemplatesListItemEditClick.bind(this),
							},
							editable: true,
							loading: true,
						});

						this.#getLastTemplatesItems().unshift(newTemplate);
						this.#resetSelected();

						newTemplate.prependTo(this.getLastTemplatesListLayout());
					},
					'File:onUploadProgress': (event: BaseEvent) => {
						const {progress} = event.getData();
						const newTemplate = this.#getLastTemplatesItems()[0];
						newTemplate.updateStatus(progress);
					},
					'File:onUploadComplete': () => {
						const timeoutID = setTimeout(() => {
							const newTemplate = this.#getLastTemplatesItems()[0];
							newTemplate.getLoadingStatus().hide();
							newTemplate.setSelected(true);
							clearTimeout(timeoutID);
						}, 1000);
					},
				},
			});
		});
	}

	#setOptions(options: TemplateSelectorOptions)
	{
		this.#cache.set('options', options);
	}

	#getOptions(): TemplateSelectorOptions
	{
		return this.#cache.get('options', {});
	}

	getBackend(): Backend
	{
		return this.#cache.remember('backend', () => {
			return new Backend({
				events: {
					onError: (error) => {
						this.emit('onError', {error});
					},
				},
			});
		});
	}

	getFileUploader(): Uploader
	{
		return this.#cache.get('fileUploader');
	}

	getUploadItems(): Array<ListItem>
	{
		return this.#cache.remember('uploadItems', () => {
			return [
				new ListItem({
					id: 'image',
					iconClass: 'ui-icon ui-icon-file-img',
					title: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_IMAGE_TITLE'),
					description: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_IMAGE_DESCRIPTION'),
				}),
				new ListItem({
					id: 'pdf',
					iconClass: 'ui-icon ui-icon-file-pdf',
					title: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_PDF_TITLE'),
					description: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_PDF_DESCRIPTION'),
				}),
				new ListItem({
					id: 'doc',
					iconClass: 'ui-icon ui-icon-file-doc',
					title: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_DOC_TITLE'),
					description: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_DOC_DESCRIPTION'),
				}),
			];
		});
	}

	getLastTemplatesListLayout(): HTMLDivElement
	{
		return this.#cache.remember('lastTemplatesListLayout', () => {
			return Tag.render`
				<div class="sign-template-selector-last-templates-list"></div>
			`;
		});
	}

	getUploadLayout(): HTMLDivElement
	{
		return this.#cache.remember('uploadLayout', () => {
			return Tag.render`
				<div class="sign-template-selector-upload">
					<div class="sign-template-selector-upload-title">
						${Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_TITLE')}
					</div>
					<div class="sign-template-selector-upload-list">
						${this.getUploadItems().map((item) => item.getLayout())}
					</div>
				</div>
			`;
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			const options: TemplateSelectorOptions = this.#getOptions();
			return Tag.render`
				<div class="sign-template-selector">
					${options?.upload?.enabled !== false ? this.getUploadLayout() : ''}
					<div class="sign-template-selector-last-templates">
						<div class="sign-template-selector-last-templates-title">
							${Loc.getMessage('SIGN_TEMPLATE_SELECTOR_LAST_TEMPLATES_TITLE')}
						</div>
						${this.getLastTemplatesListLayout()}
					</div>
				</div>
			`;
		});
	}

	#getLoader(): Loader
	{
		return this.#cache.remember('loader', () => {
			return new Loader({
				target: this.getLastTemplatesListLayout(),
			});
		});
	}

	showLoader()
	{
		void this.#getLoader().show(this.getLastTemplatesListLayout());
	}

	hideLoader()
	{
		void this.#getLoader().hide();
	}

	#resetSelected()
	{
		this.#getLastTemplatesItems().forEach((listItem) => {
			listItem.setSelected(false);
		});
	}

	#onLastTemplatesListItemClick(event: BaseEvent)
	{
		this.#resetSelected();

		const targetItem: ListItem = event.getTarget();
		targetItem.setSelected(true);
	}

	#onLastTemplatesListItemEditClick(event: BaseEvent)
	{
		const target: ListItem = event.getTarget();
		const documentId = target.getId();

		const SidePanel: BX.SidePanel = Reflection.getClass('BX.SidePanel');
		if (!Type.isNil(SidePanel))
		{
			SidePanel.Instance.open(
				`/sign/edit/${documentId}/`,
				{
					allowChangeHistory: false,
				},
			);
		}
	}

	#onLastTemplatesListItemSelect()
	{
		this.#enableSaveButton();
	}

	#setLastTemplatesItems(items: Array<ListItem>)
	{
		this.#cache.set('lastTemplatesItems', [...items]);
	}

	#getLastTemplatesItems(): Array<ListItem>
	{
		return this.#cache.get('lastTemplatesItems');
	}

	#cleanLastTemplatesListLayout()
	{
		Dom.clean(this.getLastTemplatesListLayout());
	}

	#disableSaveButton()
	{
		const saveButton = this.#getSaveButton();
		if (saveButton)
		{
			saveButton.setDisabled(true);
		}
	}

	#enableSaveButton()
	{
		const saveButton = this.#getSaveButton();
		if (saveButton)
		{
			saveButton.setDisabled(false);
		}
	}

	drawList(): Promise<any>
	{
		this.#cleanLastTemplatesListLayout();
		this.showLoader();
		this.#disableSaveButton();

		this.getBackend()
			.getTemplatesList()
			.then(({data}) => {
				this.hideLoader();

				const options: TemplateSelectorOptions = this.#getOptions();

				this.#setLastTemplatesItems(
					data.map((blank) => {
						return new ListItem({
							id: blank.ID,
							title: blank.TITLE,
							iconClass: 'ui-icon sign-template-selector-last-templates-list-item-icon-image',
							iconBackground: 'blue',
							events: {
								onClick: this.#onLastTemplatesListItemClick.bind(this),
								onEditClick: this.#onLastTemplatesListItemEditClick.bind(this),
								onSelect: this.#onLastTemplatesListItemSelect.bind(this),
							},
							targetContainer: this.getLastTemplatesListLayout(),
							editable: options?.templatesList?.editable,
						});
					}),
				);
			});
	}

	renderTo(targetContainer: HTMLElement)
	{
		Dom.append(this.getLayout(), targetContainer);
		void this.drawList();
	}

	#setSaveButton(button)
	{
		this.#cache.set('saveButton', button);
	}

	#getSaveButton(): any
	{
		return this.#cache.get('saveButton');
	}

	openSlider()
	{
		void this.drawList();
		const SidePanel: BX.SidePanel = Reflection.getClass('BX.SidePanel');
		if (!Type.isNil(SidePanel))
		{
			SidePanel.Instance.open('template-selector', {
				width: 628,
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['sign.template-selector'],
						title: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_SLIDER_TITLE'),
						content: () => {
							return this.getLayout();
						},
						buttons: ({cancelButton, SaveButton}) => {
							this.#setSaveButton(
								new SaveButton({
									text: Loc.getMessage('SIGN_TEMPLATE_SELECTOR_SLIDER_SELECT_TEMPLATE_BUTTON_LABEL'),
									onclick: () => {
										this.emit('onSelect');
										SidePanel.Instance.close();
									},
								}),
							);

							this.#disableSaveButton();

							return [
								this.#getSaveButton(),
								cancelButton,
							];
						},
					});
				},
			});
		}
	}
}