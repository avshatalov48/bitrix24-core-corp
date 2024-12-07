import { Cache, Tag, Dom, Loc, Type, Reflection, Text } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { Layout } from 'ui.sidepanel.layout';
import { Loader } from 'main.loader';
import { Uploader, UploaderOptions } from 'ui.uploader.core';
import { Button } from 'ui.buttons';
import 'sidepanel';
import Backend from '../backend/backend';
import ListItem from '../list-item/list-item';

import './css/style.css';

export type BlankSelectorOptions = {
	upload?: {
		enabled?: boolean,
	},
	blanksList?: {
		editable?: boolean,
	},
	uploaderOptions?: UploaderOptions,
	state?: {
		selectedBlankId?: string | number,
	},
	events?: {
		[key: string]: (event: BaseEvent) => void,
	},
};

/**
 * @namespace BX.Sign
 */
export default class BlankSelector extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: BlankSelectorOptions)
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
				acceptedFileTypes: ['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx', '.rtf', '.odt'],
				multiple: true,
				events: {
					'File:onAdd': (event: BaseEvent) => {
						const {file} = event.getData();
						const newBlank = new ListItem({
							id: file.id,
							title: file.clientPreview.name,
							iconClass: 'ui-icon sign-blank-selector-last-blanks-list-item-icon-image',
							iconBackground: 'blue',
							events: {
								onClick: this.#onLastBlanksListItemClick.bind(this),
								onEditClick: this.#onLastBlanksListItemEditClick.bind(this),
							},
							editable: true,
							loading: true,
						});

						this.#getLastBlanksItems().unshift(newBlank);
						this.#resetSelected();

						newBlank.prependTo(this.getLastBlanksListLayout());
					},
					'File:onUploadProgress': (event: BaseEvent) => {
						const {progress} = event.getData();
						const newBlank = this.#getLastBlanksItems()[0];
						newBlank.updateStatus(progress);
					},
					'File:onUploadComplete': () => {
						const timeoutID = setTimeout(() => {
							const newBlank = this.#getLastBlanksItems()[0];
							newBlank.getLoadingStatus().hide();
							newBlank.setSelected(true);
							clearTimeout(timeoutID);
						}, 1000);
					},
				},
			});
		});
	}

	#setOptions(options: BlankSelectorOptions)
	{
		this.#cache.set('options', options);
	}

	#getOptions(): BlankSelectorOptions
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
					title: Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_IMAGE_TITLE'),
					description: Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_IMAGE_DESCRIPTION'),
				}),
				new ListItem({
					id: 'pdf',
					iconClass: 'ui-icon ui-icon-file-pdf',
					title: Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_PDF_TITLE'),
					description: Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_PDF_DESCRIPTION'),
				}),
				new ListItem({
					id: 'doc',
					iconClass: 'ui-icon ui-icon-file-doc',
					title: Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_DOC_TITLE'),
					description: Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_DOC_DESCRIPTION'),
				}),
			];
		});
	}

	getLastBlanksListLayout(): HTMLDivElement
	{
		return this.#cache.remember('lastTemplatesListLayout', () => {
			return Tag.render`
				<div class="sign-blank-selector-last-blanks-list"></div>
			`;
		});
	}

	getUploadLayout(): HTMLDivElement
	{
		return this.#cache.remember('uploadLayout', () => {
			return Tag.render`
				<div class="sign-blank-selector-upload">
					<div class="sign-blank-selector-upload-title">
						${Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_TITLE')}
					</div>
					<div class="sign-blank-selector-upload-list">
						${this.getUploadItems().map((item) => item.getLayout())}
					</div>
				</div>
			`;
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			const options: BlankSelectorOptions = this.#getOptions();
			return Tag.render`
				<div class="sign-blank-selector">
					${options?.upload?.enabled !== false ? this.getUploadLayout() : ''}
					<div class="sign-blank-selector-last-blanks">
						<div class="sign-blank-selector-last-blanks-title">
							${Loc.getMessage('SIGN_BLANK_SELECTOR_LAST_BLANKS_TITLE')}
						</div>
						${this.getLastBlanksListLayout()}
					</div>
					<div class="sign-blank-selector-footer">
						${this.#getLoadMoreButton().render()}
					</div>
				</div>
			`;
		});
	}

	#setCurrentPageNumber(page: number): number
	{
		return this.#cache.set('currentPageNumber', page);
	}

	#getCurrentPageNumber(): number
	{
		return this.#cache.get('currentPageNumber', 1);
	}

	#getLoadMoreButton(): Button
	{
		return this.#cache.remember('loadMoreButton', () => {
			return new Button({
				text: Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE_BUTTON_LABEL'),
				color: Button.Color.LIGHT_BORDER,
				size: Button.Size.LARGE,
				onclick: (button: Button) => {
					const currentPageNumber = this.#getCurrentPageNumber();
					this.#setCurrentPageNumber(currentPageNumber + 1);
					button.setWaiting(true);
					this.#loadPage(currentPageNumber).then((data) => {
						button.setWaiting(false);
						if (!Type.isArrayFilled(data))
						{
							button.setDisabled(true);
							button.setText(Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE_BUTTON_ALL_LOADED_LABEL'));
						}
					});
				},
			});
		});
	}

	#getLoader(): Loader
	{
		return this.#cache.remember('loader', () => {
			return new Loader({
				target: this.getLastBlanksListLayout(),
			});
		});
	}

	showLoader()
	{
		void this.#getLoader().show(this.getLastBlanksListLayout());
	}

	hideLoader()
	{
		void this.#getLoader().hide();
	}

	#resetSelected()
	{
		this.#getLastBlanksItems().forEach((listItem) => {
			listItem.setSelected(false);
		});
	}

	#onLastBlanksListItemClick(event: BaseEvent)
	{
		this.#resetSelected();

		const targetItem: ListItem = event.getTarget();
		targetItem.setSelected(true);
	}

	#onLastBlanksListItemEditClick(event: BaseEvent)
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

	#onLastBlanksListItemSelect()
	{
		this.#enableSaveButton();
	}

	#setLastBlanksItems(items: Array<ListItem>)
	{
		this.#cache.set('lastBlanksItems', [...items]);
	}

	#getLastBlanksItems(): Array<ListItem>
	{
		return this.#cache.get('lastBlanksItems', []);
	}

	#cleanLastBlanksListLayout()
	{
		Dom.clean(this.getLastBlanksListLayout());
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

	#loadPage(page = 1): Promise<Array<any>>
	{
		return this.getBackend()
			.getBlanksList({page, countPerPage: 12})
			.then(({data}) => {
				this.hideLoader();

				const options: BlankSelectorOptions = this.#getOptions();

				this.#setLastBlanksItems([
					...this.#getLastBlanksItems(),
					...data.map((blank) => {
						return new ListItem({
							id: blank.ID,
							title: blank.TITLE,
							data: {
								id: blank.ID,
								title: blank.TITLE,
							},
							iconClass: 'ui-icon sign-blank-selector-last-blanks-list-item-icon-image',
							iconBackground: 'blue',
							events: {
								onClick: this.#onLastBlanksListItemClick.bind(this),
								onEditClick: this.#onLastBlanksListItemEditClick.bind(this),
								onSelect: this.#onLastBlanksListItemSelect.bind(this),
							},
							targetContainer: this.getLastBlanksListLayout(),
							editable: options?.blanksList?.editable,
							selected: String(options?.state?.selectedBlankId) === String(blank.ID),
						});
					}),
				]);

				return data;
			});
	}

	drawList(): Promise<any>
	{
		this.#setCurrentPageNumber(1);
		this.#cleanLastBlanksListLayout();
		this.showLoader();
		this.#disableSaveButton();
		void this.#loadPage();
		const moreButton = this.#getLoadMoreButton();
		moreButton.setDisabled(false);
		moreButton.setText(Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE_BUTTON_LABEL'));
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

	getSelectedItem(): ?ListItem
	{
		return this.#getLastBlanksItems().find((item: ListItem) => {
			return item.isSelected();
		});
	}

	openSlider()
	{
		void this.drawList();
		const SidePanel: BX.SidePanel = Reflection.getClass('BX.SidePanel');
		if (!Type.isNil(SidePanel))
		{
			SidePanel.Instance.open('blank-selector', {
				width: 628,
				cacheable: false,
				events: {
					onClose: () => {
						this.emit('onCancel');
					},
				},
				contentCallback: () => {
					return Layout.createContent({
						extensions: ['sign.blank-selector'],
						title: Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_TITLE'),
						content: () => {
							return this.getLayout();
						},
						buttons: ({ cancelButton, SaveButton }) => {
							this.#setSaveButton(
								new SaveButton({
									text: Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_SELECT_BLANK_BUTTON_LABEL'),
									onclick: () => {
										this.emit('onSelect', this.getSelectedItem().getData());
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
