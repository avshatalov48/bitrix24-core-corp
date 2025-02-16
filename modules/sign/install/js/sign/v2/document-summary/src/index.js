import { Tag, Text, Dom, Loc } from 'main.core';
import { Api } from 'sign.v2.api';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import './style.css';

const buttonClassList = [
	'ui-btn',
	'ui-btn-sm',
	'ui-btn-light-border',
	'ui-btn-round',
];

type DocumentSummaryOptions = {
	events?: {
		[key: string]: (event: BaseEvent) => void
	}
};

type ItemDetails = {
	blocks: Array<{ party: number; }>;
	entityId: number;
	id: number;
	isTemplate: boolean;
	title: string;
	uid: string;
	urls: Array<string>;
};

export class DocumentSummary extends EventEmitter
{
	items: {
		[uid: string]: ItemDetails
	};

	constructor(options: DocumentSummaryOptions = {})
	{
		super();
		this.setEventNamespace('BX.Sign.V2.DocumentSummary');
		this.subscribeFromOptions(options.events);
	}

	#createDocumentDetails(item: Object): HTMLElement
	{
		const title = Text.encode(item.title ?? '');

		return Tag.render`
			<div class="sign-document-summary__details">
				<div class="sign-document-summary__details_title">
					<span
						class="sign-document-summary__details_title-text"
						title="${title}"
					>
						${title}
					</span>
					<span
						class="sign-document-summary__details_edit-title-btn"
						onclick="${({ target: button }) => {
							this.#toggleTitleEditor(item, button, true);
						}}"
					>
					</span>
				</div>
				${this.#createNumber(item.externalId)}
			</div>
		`;
	}

	addItem(uid: string, itemDetails: ItemDetails): void
	{
		if (!this.items)
		{
			this.items = {};
		}
		this.items[uid] = itemDetails;
	}

	deleteItem(uid: string): void
	{
		if (!this.items || !this.items[uid])
		{
			return;
		}

		delete this.items[uid];
	}

	setItems(documentObject): void
	{
		this.items = {};

		Object.keys(documentObject).forEach((uid) => {
			this.items[uid] = documentObject[uid];
		});
	}

	#createNumber(number: number): ?HTMLElement
	{
		if (!number)
		{
			return null;
		}

		const title = Loc.getMessage('SIGN_DOCUMENT_SUMMARY_REG_NUMBER', {
			'#NUMBER#': Text.encode(number),
		});

		return Tag.render`
			<div class="sign-document-summary__reg_number" title="${title}">
				${title}
			</div>
		`;
	}

	#createTitleEditor(item: ItemDetails): HTMLElement
	{
		const okButtonClassName = [
			...buttonClassList.slice(0, 2),
			'ui-btn-primary',
			'sign-document-summary__title-editor_ok-btn',
		].join(' ');
		const discardButtonClassName = [
			...buttonClassList.slice(0, 3),
			'sign-document-summary__title-editor_discard-btn',
		].join(' ');
		const input = Tag.render`<input type="text" class="ui-ctl-element" maxlength="255" />`;
		input.value = item.title ?? '';
		this.#focusInput(input);

		return Tag.render`
			<div class="sign-document-summary__title-editor">
				<div class="sign-document-summary__title-editor_controls">
					<span class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						${input}
					</span>
					<span
						class="${okButtonClassName}"
						onclick="${async ({ target }) => {
							Dom.addClass(target, 'ui-btn-wait');
							await this.#modifyDocumentTitle(item, input.value);
							Dom.removeClass(target, 'ui-btn-wait');
							this.#toggleTitleEditor(item, target, false);
						}}"
					>
					</span>
					<span
						class="${discardButtonClassName}"
						onclick="${({ target }) => {
							this.#toggleTitleEditor(item, target, false);
						}}"
					>
					</span>
				</div>
				<p class="sign-document-summary__title-editor_help">
					${Loc.getMessage('SIGN_DOCUMENT_SUMMARY_TITLE_EDITOR_HELP')}
				</p>
			</div>
		`;
	}

	#toggleTitleEditor(item: ItemDetails, button: HTMLElement, shouldShow: boolean)
	{
		const summaryNode = button.closest('.sign-document-summary');
		if (shouldShow)
		{
			Dom.clean(summaryNode);
			Dom.append(this.#createTitleEditor(item), summaryNode);

			return;
		}

		Dom.replace(summaryNode.firstElementChild, this.#createDocumentDetails(item));
		Dom.append(this.#createEditDocumentBtn(item.id, item.uid), summaryNode);
	}

	#focusInput(input: HTMLElement)
	{
		const observer = new MutationObserver(() => {
			if (input.isConnected)
			{
				input.focus();
				observer.disconnect();
			}
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}

	async #modifyDocumentTitle(item: ItemDetails, newValue: string)
	{
		if (item.title === newValue)
		{
			return;
		}

		try
		{
			const api = new Api();
			const titleData = await api.modifyTitle(item.uid, newValue);
			this.items[item.uid].title = newValue;
			this.emit('changeTitle', { uid: item.uid, title: newValue, blankTitle: titleData.blankTitle });
		}
		catch (ex)
		{
			console.error(ex);
		}
	}

	setNumber(uid: string, number: ?string): void
	{
		this.items[uid].number = number;
	}

	#createEditDocumentBtn(id: number, uid: string): HTMLElement
	{
		return Tag.render`
			<span
				class="${buttonClassList.join(' ')}" data-id="${id}"
				onclick="${() => this.emit('showEditor', { uid })}"
			>
				${Loc.getMessage('SIGN_DOCUMENT_SUMMARY_EDIT')}
			</span>
		`;
	}

	getLayout(): HTMLElement
	{
		const container = Tag.render`
			<div class="sign-document-summary-wrapper"></div>
		`;

		for (const item of Object.values(this.items))
		{
			const itemBlock = Tag.render`
				<div class="sign-document-summary">
					${this.#createDocumentDetails(item)}
					${this.#createEditDocumentBtn(item.id, item.uid)}
				</div>
			`;

			Dom.append(itemBlock, container);
		}

		return container;
	}
}
