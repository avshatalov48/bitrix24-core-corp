import { Dom, Event, Loc, Runtime, Tag, Text, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Dialog } from 'crm.entity-selector';
import { DialogOptions } from 'ui.entity-selector';

import type { EditorOptions } from './editor-options';

import 'ui.design-tokens';
import './editor.css';

const SELECTOR_TARGET_NAME = 'crm-template-editor-element-pill';

export class Editor
{
	#id: ?string;
	#target: HTMLElement = null;
	#entityTypeId: number = null;
	#entityId: number = null;
	#categoryId: ?number = null;
	#placeHolderMaskRe: ?RegExp = null;

	#headerContainerEl: ?HTMLElement = null;
	#bodyContainerEl: ?HTMLElement = null;
	#footerContainerEl: ?HTMLElement = null;

	#placeHoldersDialogDefaultOptions: ?DialogOptions = null;

	#headerRaw: ?string = null;
	#bodyRaw: ?string = null;
	#footerRaw: ?string = null;

	constructor(params: EditorOptions)
	{
		this.#assertValidParams(params);

		this.#id = params.id || `crm-template-editor-${Text.getRandom()}`;
		this.#target = params.target;
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#categoryId = Type.isNumber(params.entityId) ? params.entityId : null;
		this.#placeHolderMaskRe = Type.isStringFilled(params.placeHolderMaskRe)
			? params.placeHolderMaskRe
			: /{{\s+(\d+)\s+}}/g; // {{ 1 }}, {{ 2 }}, {{ 3 }} ... default regex

		this.#placeHoldersDialogDefaultOptions = {
			multiple: false,
			showAvatars: false,
			dropdownMode: true,
			compactView: true,
			enableSearch: true,
			tagSelectorOptions: {
				textBoxWidth: '100%',
			},
			entities: [{
				id: 'placeholder',
				options: {
					entityTypeId: this.#entityTypeId,
					entityId: this.#entityId,
					categoryId: this.#categoryId,
				},
			}],
		};

		this.#createContainer();
		this.#bindEvents();
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

	getData(): Object
	{
		return {
			header: this.#getPlainText(this.#headerContainerEl),
			body: this.#getPlainText(this.#bodyContainerEl),
			footer: this.#getPlainText(this.#footerContainerEl),
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

		Dom.append(containerEl, this.#target);
	}

	#createContainerWithSelectors(input: string): HTMLElement
	{
		const placeHolders = input.match(this.#placeHolderMaskRe);
		if (!placeHolders)
		{
			return input;
		}

		const result: string = input.replace(
			this.#placeHolderMaskRe,
			`<span class="${SELECTOR_TARGET_NAME}">${Loc.getMessage('CRM_TEMPLATE_EDITOR_EMPTY_PLACEHOLDER_LABEL')}</span>`,
		);

		const container = Tag.render`<div>${result}</div>`;
		const dlgOptions = this.#placeHoldersDialogDefaultOptions;
		const elements = container.querySelectorAll(`.${SELECTOR_TARGET_NAME}`);
		elements.forEach((element?: Node): void => {
			dlgOptions.events = {
				'Item:onSelect': (event: BaseEvent): void => {
					const item = event.getData().item;

					// eslint-disable-next-line no-param-reassign
					element.textContent = item.getTitle();
					Dom.attr(element, 'placeholder-id', item.id);
					Dom.addClass(element, '--selected');
				},
				'Item:onDeselect': (event: BaseEvent): void => {
					// eslint-disable-next-line no-param-reassign
					element.textContent = Loc.getMessage('CRM_TEMPLATE_EDITOR_EMPTY_PLACEHOLDER_LABEL');
					Dom.attr(element, 'placeholder-id', '');
					Dom.removeClass(element, '--selected');
				},
			};
			dlgOptions.targetNode = element;

			const dlg = new Dialog(dlgOptions);

			Event.bind(element, 'click', () => dlg.show());
		});

		return container;
	}

	#getPlainText(container: HTMLElement): ?string
	{
		if (!Type.isDomNode(container))
		{
			return null;
		}

		const containerCopy = Runtime.clone(container);
		const elements = containerCopy.querySelectorAll(`.${SELECTOR_TARGET_NAME}`);
		elements.forEach((element?: Node): void => {
			// eslint-disable-next-line no-param-reassign
			element.textContent = element?.hasAttribute('placeholder-id')
				? `{${element.getAttribute('placeholder-id')}}`
				: '';
		});

		return containerCopy.textContent;
	}

	// region Event handlers
	#bindEvents(): void
	{
		// TODO: not implemented yet
	}
	// endregion

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

		if (!BX.CrmEntityType.isDefined(params.entityTypeId))
		{
			throw new TypeError('BX.Crm.Template.Editor: The "entityTypeId" argument is not correct');
		}

		if (!Type.isNumber(params.entityId) || params.entityId <= 0)
		{
			throw new TypeError('BX.Crm.Template.Editor: The "entityId" argument is not correct');
		}
	}
}
