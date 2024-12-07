import { Dialog } from 'crm.entity-selector';
import { ajax as Ajax, Dom, Event, Loc, Runtime, Tag, Text, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Menu, Popup } from 'main.popup';

import 'ui.design-tokens';
import { DialogOptions } from 'ui.entity-selector';
import type { EditorOptions } from './editor-options';
import './editor.css';
import MenuPopup from './menu-popup';
import PreviewPopup, { PREVIEW_POPUP_CONTENT_STATUS } from './preview-popup';
import TextPopup from './text-popup';
import type { FilledPlaceholder } from './types';

const UPDATE_ACTION = 'update';
const DELETE_ACTION = 'delete';

const HEADER_POSITION = 'HEADER';
const PREVIEW_POSITION = 'PREVIEW';
const FOOTER_POSITION = 'FOOTER';

export class Editor
{
	#id: ?string;
	#target: HTMLElement = null;
	#entityTypeId: number = null;
	#entityId: number = null;
	#categoryId: ?number = null;
	#canUseFieldsDialog: boolean = true;
	#canUseFieldValueInput: boolean = true;

	#canUsePreview: boolean = false;
	#isUsePreviewRequestRunning: boolean = false;
	#lastPreview: ?string = null;
	#lastPreviewTemplateHash: ?number = null;
	#previewPopup: ?PreviewPopup = null;
	placeholders: string[] = [];
	filledPlaceholders: FilledPlaceholder[] = [];
	onSelect = () => {};

	// @todo replace this variables with a generic container
	#headerContainerEl: ?HTMLElement = null;
	#bodyContainerEl: ?HTMLElement = null;
	#footerContainerEl: ?HTMLElement = null;

	#placeHoldersDialogDefaultOptions: ?DialogOptions = null;

	#headerRaw: ?string = null;
	#bodyRaw: ?string = null;
	#footerRaw: ?string = null;

	#popupMenu: ?Menu = null;
	#inputPopup: ?Popup = null;

	constructor(params: EditorOptions)
	{
		this.#assertValidParams(params);

		this.#id = params.id || `crm-template-editor-${Text.getRandom()}`;
		this.#target = params.target;
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#categoryId = Type.isNumber(params.categoryId) ? params.categoryId : null;
		this.onSelect = params.onSelect;

		this.#canUseFieldsDialog = Boolean(params.canUseFieldsDialog ?? true);
		this.#canUseFieldValueInput = Boolean(params.canUseFieldValueInput ?? true);
		this.#canUsePreview = Boolean(params.canUsePreview ?? false);

		this.onPlaceholderClick = this.onPlaceholderClick.bind(this);
		this.onShowInputPopup = this.onShowInputPopup.bind(this);

		this.#placeHoldersDialogDefaultOptions = {
			multiple: false,
			showAvatars: false,
			dropdownMode: true,
			compactView: true,
			enableSearch: true,
			tagSelectorOptions: {
				textBoxWidth: '100%',
			},
		};

		if (this.#canUsePlaceholderProvider(params.usePlaceholderProvider))
		{
			this.#placeHoldersDialogDefaultOptions.entities = [{
				id: 'placeholder',
				options: {
					entityTypeId: this.#entityTypeId,
					entityId: this.#entityId,
					categoryId: this.#categoryId ?? null,
				},
			}];
		}

		if (Type.isPlainObject(params.dialogOptions))
		{
			this.#placeHoldersDialogDefaultOptions = { ...this.#placeHoldersDialogDefaultOptions, ...params.dialogOptions };
		}

		this.#createContainer();
	}

	setPlaceholders(placeholders: string[]): this
	{
		this.placeholders = placeholders;

		return this;
	}

	setFilledPlaceholders(filledPlaceholders: FilledPlaceholder[]): this
	{
		this.filledPlaceholders = filledPlaceholders;

		return this;
	}

	// region Public methods
	setHeader(input: string): void
	{
		if (!Type.isStringFilled(input))
		{
			return;
		}

		this.#headerRaw = input;
		Dom.append(this.#createContainerWithSelectors(input), this.#headerContainerEl);
	}

	setBody(input: string): void
	{
		if (!Type.isStringFilled(input))
		{
			return;
		}

		this.#bodyRaw = input;
		Dom.append(this.#createContainerWithSelectors(input), this.#bodyContainerEl);
	}

	setFooter(input: string): void
	{
		if (!Type.isStringFilled(input))
		{
			return;
		}

		this.#footerRaw = input;
		Dom.append(this.#createContainerWithSelectors(input), this.#footerContainerEl);
	}

	getData(): ?Object
	{
		if (this.placeholders === null)
		{
			return null;
		}

		return {
			header: this.#getPlainText(HEADER_POSITION),
			body: this.#getPlainText(PREVIEW_POSITION),
			footer: this.#getPlainText(FOOTER_POSITION),
		};
	}

	getRawData(): Object
	{
		return {
			header: this.#headerRaw,
			body: this.#bodyRaw,
			footer: this.#footerRaw,
		};
	}
	// endregion

	#createContainer(): void
	{
		if (!this.#target)
		{
			return;
		}

		const containerEl = Tag.render`
			<div id="${this.#id}" class="crm-template-editor crm-template-editor__scope"></div>
		`;

		this.#headerContainerEl = Tag.render`<div class="crm-template-editor-header"></div>`;
		Dom.append(this.#headerContainerEl, containerEl);
		this.#bodyContainerEl = Tag.render`<div class="crm-template-editor-body"></div>`;
		Dom.append(this.#bodyContainerEl, containerEl);
		this.#footerContainerEl = Tag.render`<div class="crm-template-editor-footer"></div>`;
		Dom.append(this.#footerContainerEl, containerEl);

		if (this.#canUsePreview)
		{
			const previewLink = Tag.render`
				<div class="crm-template-editor-preview-link" href="#">
					${Loc.getMessage('CRM_TEMPLATE_EDITOR_PREVIEW_LINK_TITLE')}
				</div>
			`;

			Event.bind(previewLink, 'click', this.#onPreviewTemplate.bind(this));
			Dom.append(previewLink, containerEl);
		}

		Dom.clean(this.#target);
		Dom.append(containerEl, this.#target);
	}

	#createContainerWithSelectors(input: string, position: string = PREVIEW_POSITION): ?HTMLElement
	{
		const placeholders = this.#getPlaceholders(position);
		if (placeholders === null)
		{
			return null;
		}

		const container = this.#getInputContainer(input, position);

		placeholders.forEach((placeholder, key) => {
			const element = [...container.childNodes].find(
				(node) => node.dataset && Number(node.dataset.templatePlaceholder) === key,
			);

			if (!element)
			{
				return;
			}

			const dlgOptions = Runtime.clone(this.#placeHoldersDialogDefaultOptions);
			this.#prepareDlgOptions(dlgOptions, element, position);

			const dialog = new Dialog(dlgOptions);

			Event.bind(element, 'click', (event) => {
				this.onPlaceholderClick({ dialog, event });
			});
		});

		return container;
	}

	onPlaceholderClick({ dialog, event }): void
	{
		this.#inputPopup?.destroy();

		const filledPlaceholder = this.#getFilledPlaceholderByElement(event.target, PREVIEW_POSITION);
		const isTextItemFirst = Type.isStringFilled(filledPlaceholder?.FIELD_VALUE);

		if (this.#canUseFieldsDialog && this.#canUseFieldValueInput)
		{
			this.#popupMenu = new MenuPopup({
				bindElement: event.target,
				isTextItemFirst,
				onEditorItemClick: () => {
					this.onShowDialogPopup(filledPlaceholder, dialog);
				},
				onTextItemClick: (element: HTMLElement) => {
					this.onShowInputPopup(element);
				},
			});

			this.#popupMenu.show();
		}
		else if (this.#canUseFieldsDialog)
		{
			this.onShowDialogPopup(filledPlaceholder, dialog);
		}
		else if (this.#canUseFieldValueInput)
		{
			this.onShowInputPopup(event.target);
		}
	}

	onShowDialogPopup(filledPlaceholder: FilledPlaceholder, dialog: Dialog): void
	{
		if (Type.isStringFilled(filledPlaceholder?.FIELD_VALUE))
		{
			dialog.getPreselectedItems().forEach((preselectedItem) => {
				const item = dialog.getItem(preselectedItem);
				if (item)
				{
					item.deselect();
				}
			});
		}

		dialog.show();
	}

	onShowInputPopup(bindElement: HTMLElement): void
	{
		const filledPlaceholder = this.#getFilledPlaceholderByElement(bindElement);
		const value = Type.isStringFilled(filledPlaceholder?.FIELD_VALUE) ? filledPlaceholder.FIELD_VALUE : '';

		this.#inputPopup = new TextPopup({
			bindElement,
			value,
			onApply: (newValue: string) => {
				this.#onApplyInputPopup(newValue, bindElement);
			},
		});

		this.#inputPopup.show();
	}

	#onApplyInputPopup(value: string, bindElement: HTMLElement): void
	{
		const placeholderId = this.#getPlaceholderIdByElement(bindElement, PREVIEW_POSITION);

		const params = {
			id: placeholderId,
			parentTitle: null,
			text: value,
			title: value,
			entityType: BX.CrmEntityType.resolveName(this.#entityTypeId).toLowerCase(),
		};

		// eslint-disable-next-line no-param-reassign
		bindElement.textContent = value;
		Dom.addClass(bindElement, '--selected');

		this.#adjustFilledPlaceholders(params);
		this.onSelect(params);
	}

	#onPreviewTemplate(event: BaseEvent): void
	{
		if (this.#previewPopup?.isShown())
		{
			return;
		}

		if (this.#isUsePreviewRequestRunning)
		{
			this.#previewPopup?.show();

			return;
		}

		if (this.#entityId <= 0)
		{
			this.#previewPopup?.show();

			return;
		}

		this.#previewPopup?.destroy();

		const currentTemplate = this.placeholders === null
			? this.getRawData().body
			: this.getData().body; // TODO: implement header and footer processing
		const currentTemplateHash = BX.util.hashCode(currentTemplate);
		if (this.#lastPreviewTemplateHash === currentTemplateHash)
		{
			this.#previewPopup = new PreviewPopup(event.target, this.#entityTypeId, this.#entityId);
			this.#previewPopup.apply(PREVIEW_POPUP_CONTENT_STATUS.SUCCESS, this.#lastPreview);
			this.#previewPopup.show();

			return;
		}

		this.#previewPopup = new PreviewPopup(event.target, this.#entityTypeId, this.#entityId);
		this.#previewPopup.apply(PREVIEW_POPUP_CONTENT_STATUS.LOADING);
		this.#previewPopup.show();
		this.#isUsePreviewRequestRunning = true;

		Ajax.runAction(
			'crm.activity.smsplaceholder.preview',
			{
				data: {
					entityTypeId: this.#entityTypeId,
					entityId: this.#entityId,
					message: currentTemplate,
					entityCategoryId: this.#categoryId,
				},
			},
		).then((response) => {
			this.#previewPopup.apply(
				PREVIEW_POPUP_CONTENT_STATUS.SUCCESS,
				response.data.preview,
			);
			this.#isUsePreviewRequestRunning = false;
			this.#lastPreviewTemplateHash = currentTemplateHash;
			this.#lastPreview = response.data.preview;
		}).catch((response) => {
			this.#previewPopup.apply(
				PREVIEW_POPUP_CONTENT_STATUS.FAILED,
				response.errors[0].message ?? 'Unknown error',
			);
			this.#isUsePreviewRequestRunning = false;
		});
	}

	#getInputContainer(input: string, position: string): ?HTMLElement
	{
		const placeholders = this.#getPlaceholders(position);
		if (placeholders === null)
		{
			return null;
		}

		let i = 0;
		placeholders.forEach((placeholder) => {
			const filledPlaceholder = this.#getFilledPlaceholderById(placeholder);

			let title = Loc.getMessage('CRM_TEMPLATE_EDITOR_EMPTY_PLACEHOLDER_LABEL');
			let spanClass = 'crm-template-editor-element-pill';
			if (filledPlaceholder)
			{
				if (
					Type.isStringFilled(filledPlaceholder.PARENT_TITLE)
					&& Type.isStringFilled(filledPlaceholder.TITLE))
				{
					title = `${filledPlaceholder.PARENT_TITLE}: ${filledPlaceholder.TITLE}`;
				}
				else if (Type.isStringFilled(filledPlaceholder.TITLE))
				{
					title = filledPlaceholder.TITLE;
				}
				else if (Type.isStringFilled(filledPlaceholder.FIELD_NAME))
				{
					title = filledPlaceholder.FIELD_NAME;
				}
				else
				{
					title = filledPlaceholder.FIELD_VALUE;
				}

				title = Text.encode(title);
				spanClass += ' --selected';
			}

			const replaceValue = `<span class="${spanClass}" data-template-placeholder="${i++}">${title}</span>`;

			// eslint-disable-next-line no-param-reassign
			input = input.replace(placeholder, replaceValue);
		});

		return Tag.render`<div>${input}</div>`;
	}

	#getPlaceholders(position: string): ?[]
	{
		const allPlaceholders = Type.isPlainObject(this.placeholders) ? this.placeholders : {};
		const placeholders = Type.isArrayFilled(allPlaceholders[position]) ? allPlaceholders[position] : [];

		return Type.isArrayLike(placeholders) ? placeholders : null;
	}

	#prepareDlgOptions(dlgOptions: DialogOptions, element: HTMLElement, position: string): void
	{
		const placeholders = this.#getPlaceholders(position);
		const placeholderId = placeholders[element.dataset.templatePlaceholder] ?? null;
		if (placeholderId)
		{
			const filledPlaceholder = this.#getFilledPlaceholderById(placeholderId);
			if (filledPlaceholder)
			{
				// eslint-disable-next-line no-param-reassign
				dlgOptions.preselectedItems = [
					[
						filledPlaceholder.FIELD_ENTITY_TYPE,
						filledPlaceholder.FIELD_NAME,
					],
				];
			}
		}

		// eslint-disable-next-line no-param-reassign
		dlgOptions.events = {
			onShow: () => {
				const keyframes = [
					{ transform: 'rotate(0)' },
					{ transform: 'rotate(90deg)' },
					{ transform: 'rotate(180deg)' },
				];
				const options = {
					duration: 200,
					pseudoElement: '::after',
				};

				element.animate(keyframes, options);
				Dom.addClass(element, '--flipped');
			},
			onHide: () => {
				const keyframes = [
					{ transform: 'rotate(180deg)' },
					{ transform: 'rotate(90deg)' },
					{ transform: 'rotate(0)' },
				];
				const options = {
					duration: 200,
					pseudoElement: '::after',
				};

				element.animate(keyframes, options);
				Dom.removeClass(element, '--flipped');
			},
			'Item:onSelect': (event: BaseEvent): void => {
				Dom.addClass(element, '--selected');

				const item = event.getData().item;
				const parentTitle = item.supertitle.text;
				const title = item.title.text;

				// eslint-disable-next-line no-param-reassign
				element.textContent = `${parentTitle}: ${title}`;

				const value = item.id;
				const entityType = item.entityId;

				const params = {
					id: placeholderId,
					value,
					parentTitle,
					title,
					entityType,
				};

				this.#adjustFilledPlaceholders(params);
				this.onSelect(params);
			},
		};

		// eslint-disable-next-line no-param-reassign
		dlgOptions.targetNode = element;
	}

	#adjustFilledPlaceholders({ id, value, text, parentTitle, title }, action: string = UPDATE_ACTION): void
	{
		if (action === DELETE_ACTION)
		{
			this.#deleteFromFilledPlaceholders(id, value);

			return;
		}

		this.#updateForFilledPlaceholders({ id, value, text, parentTitle, title });
	}

	#deleteFromFilledPlaceholders(id: string, value: string): void
	{
		this.filledPlaceholders = this.filledPlaceholders.filter(
			(filledPlaceholder) => {
				return filledPlaceholder.PLACEHOLDER_ID !== id || filledPlaceholder.FIELD_NAME !== value;
			},
		);
	}

	#updateForFilledPlaceholders({ id, value, text, parentTitle, title }): void
	{
		const filledPlaceholder = this.#getFilledPlaceholderById(id);

		if (filledPlaceholder)
		{
			filledPlaceholder.FIELD_NAME = value ?? null;
			filledPlaceholder.FIELD_VALUE = text ?? null;
			filledPlaceholder.PARENT_TITLE = parentTitle;
			filledPlaceholder.TITLE = title;
		}
		else
		{
			this.filledPlaceholders.push({
				PLACEHOLDER_ID: id,
				FIELD_NAME: value,
				FIELD_VALUE: text,
				PARENT_TITLE: parentTitle,
				TITLE: title,
			});
		}
	}

	#getFilledPlaceholderByElement(element: HTMLElement, position: string = PREVIEW_POSITION): ?FilledPlaceholder
	{
		const placeholderId = this.#getPlaceholderIdByElement(element, position);

		return this.#getFilledPlaceholderById(placeholderId);
	}

	#getPlaceholderIdByElement(element: HTMLElement, position: string = PREVIEW_POSITION): ?string
	{
		const placeholders = this.#getPlaceholders(position);

		return placeholders[element.dataset.templatePlaceholder] ?? null;
	}

	#getFilledPlaceholderById(placeholderId: string): ?FilledPlaceholder
	{
		return this.filledPlaceholders.find(
			(filledPlaceholderItem) => filledPlaceholderItem.PLACEHOLDER_ID === placeholderId,
		);
	}

	#getPlainText(position: string): ?string
	{
		let text = this.#getRawTextByPosition(position);
		if (text === null)
		{
			return null;
		}

		if (Type.isArrayFilled(this.filledPlaceholders))
		{
			this.filledPlaceholders.forEach((filledPlaceholder) => {
				if (Type.isStringFilled(filledPlaceholder.FIELD_NAME))
				{
					text = text.replace(filledPlaceholder.PLACEHOLDER_ID, `{${filledPlaceholder.FIELD_NAME}}`);
				}
				else if (Type.isStringFilled(filledPlaceholder.FIELD_VALUE))
				{
					const fieldValue = filledPlaceholder.FIELD_VALUE
						.replaceAll('{', '&#123;')
						.replaceAll('}', '&#125;')
					;
					text = text.replace(filledPlaceholder.PLACEHOLDER_ID, fieldValue);
				}
			});
		}

		const placeholders = this.placeholders[position];
		if (Type.isArrayFilled(placeholders))
		{
			placeholders.forEach((placeholder) => {
				text = text.replace(placeholder, ' ');
			});
		}

		return text;
	}

	#getRawTextByPosition(position: string): ?string
	{
		if (position === HEADER_POSITION)
		{
			return this.#headerRaw;
		}

		if (position === PREVIEW_POSITION)
		{
			return this.#bodyRaw;
		}

		if (position === FOOTER_POSITION)
		{
			return this.#footerRaw;
		}

		return null;
	}

	#assertValidParams(params: EditorOptions): void
	{
		if (!Type.isPlainObject(params))
		{
			throw new TypeError('BX.Crm.Template.Editor: The "params" argument must be object');
		}

		if (!Type.isDomNode(params.target))
		{
			throw new Error('BX.Crm.Template.Editor: The "target" argument must be DOM node');
		}

		if (
			this.#canUsePlaceholderProvider(params.usePlaceholderProvider)
			&& !BX.CrmEntityType.isDefined(params.entityTypeId)
		)
		{
			throw new TypeError('BX.Crm.Template.Editor: The "entityTypeId" argument is not correct');
		}

		if (!Type.isFunction(params.onSelect))
		{
			throw new TypeError('BX.Crm.Template.Editor: The "onSelect" argument is not correct');
		}
	}

	#canUsePlaceholderProvider(value): boolean
	{
		return (Type.isNil(value) || value === true);
	}
}
