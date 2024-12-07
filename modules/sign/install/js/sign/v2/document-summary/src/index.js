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

export class DocumentSummary extends EventEmitter
{
	uid: string;
	title: string;
	#blocks: Array<{ party: number; }>;
	#editDocumentBtn: HTMLElement;
	#number: string | null = null;

	constructor(options: DocumentSummaryOptions = {})
	{
		super();
		this.setEventNamespace('BX.Sign.V2.DocumentSummary');
		this.subscribeFromOptions(options.events);
		this.#editDocumentBtn = Tag.render`
			<span
				class="${buttonClassList.join(' ')}"
				onclick="${() => this.emit('showEditor')}"
			>
				${Loc.getMessage('SIGN_DOCUMENT_SUMMARY_EDIT')}
			</span>
		`;
	}

	#createDocumentDetails(): HTMLElement
	{
		const title = Text.encode(this.title ?? '');

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
							this.#toggleTitleEditor(button, true);
						}}"
					>
					</span>
				</div>
				${this.#createNumber()}
			</div>
		`;
	}

	#createNumber(): ?HTMLElement
	{
		if (!this.#number)
		{
			return null;
		}

		const title = Loc.getMessage('SIGN_DOCUMENT_SUMMARY_REG_NUMBER', {
			'#NUMBER#': Text.encode(this.#number),
		});

		return Tag.render`
			<div class="sign-document-summary__reg_number" title="${title}">
				${title}
			</div>
		`;
	}

	#createTitleEditor(): HTMLElement
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
		input.value = this.title ?? '';
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
							await this.#modifyDocumentTitle(input.value);
							Dom.removeClass(target, 'ui-btn-wait');
							this.#toggleTitleEditor(target, false);
						}}"
					>
					</span>
					<span
						class="${discardButtonClassName}"
						onclick="${({ target }) => {
							this.#toggleTitleEditor(target, false);
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

	#toggleTitleEditor(button: HTMLElement, shouldShow: boolean)
	{
		const summaryNode = button.closest('.sign-document-summary');
		if (shouldShow)
		{
			Dom.clean(summaryNode);
			Dom.append(this.#createTitleEditor(), summaryNode);

			return;
		}

		Dom.replace(summaryNode.firstElementChild, this.#createDocumentDetails());
		Dom.append(this.#editDocumentBtn, summaryNode);
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

	async #modifyDocumentTitle(newValue: string)
	{
		if (this.title === newValue)
		{
			return;
		}

		try
		{
			const api = new Api();
			const titleData = await api.modifyTitle(this.uid, newValue);
			this.title = newValue;
			this.emit('changeTitle', { title: newValue, blankTitle: titleData.blankTitle });
		}
		catch (ex)
		{
			console.error(ex);
		}
	}

	set blocks(blocks: Array<{ party: number; }>)
	{
		this.#blocks = blocks;
		this.emit('reloadEntities');
	}

	setNumber(number: ?string): void
	{
		this.#number = number;
	}

	getLayout(): HTMLElement
	{
		return Tag.render`
			<div class="sign-document-summary">
				${this.#createDocumentDetails()}
				${this.#editDocumentBtn}
			</div>
		`;
	}
}
