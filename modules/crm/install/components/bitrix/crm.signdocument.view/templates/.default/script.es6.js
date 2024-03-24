import { Dom, Reflection, Tag, Loc, Type } from 'main.core';
import { BaseButton, ButtonManager } from 'ui.buttons';
import type { DocumentSend } from 'sign.v2.document-send';
import { ButtonState } from '../../../../../js/crm/timeline/item/src/components/enums/button-state';

const namespace = Reflection.namespace('BX.Crm.Component');
const Viewer = Reflection.namespace('BX.UI.Viewer');

declare type SignDocumentViewParameters = {
	pdfNode: ?Element,
	pdfSource: ?string,
	printButtonId: ?string,
	downloadButtonId: ?string,
};

let defaultComponent = null;

/**
 * @memberOf BX.Crm.Component
 */
class SignDocumentView
{
	pdfNode: ?Element;
	pdfSource: ?string;
	printButton: ?BaseButton;
	downloadButton: ?BaseButton;
	viewer: ?Viewer.SingleDocumentController;

	constructor(parameters: SignDocumentViewParameters)
	{
		this.pdfNode = parameters.pdfNode;
		this.pdfSource = parameters.pdfSource;
		this.printButton = ButtonManager.createByUniqId('crm-document-print');
		this.downloadButton = ButtonManager.createByUniqId('crm-document-download');

		this.#initViewer();
		this.#bindEvents();

		defaultComponent = this;
	}

	#initViewer(): void
	{
		const viewer = this.getViewer();
		if (!viewer)
		{
			return;
		}
		viewer.setItems([Viewer.buildItemByNode(this.pdfNode)]);
		viewer.setPdfSource(this.pdfSource);
		viewer.setScale(1.2);
		viewer.open();
	}

	getViewer(): ?Viewer.SingleDocumentController
	{
		if (!this.viewer && this.pdfNode)
		{
			this.viewer = new Viewer.SingleDocumentController({baseContainer: this.pdfNode, stretch: true});
		}

		return this.viewer ?? null;
	}

	#bindEvents(): void
	{
		if (this.printButton && this.getViewer())
		{
			this.printButton.bindEvent('click', () => {
				this.getViewer().print();
			})
		}
		if (this.downloadButton)
		{
			this.downloadButton.bindEvent('click', () => {
				window.open(this.pdfSource, '_blank');
			});
		}
	}

	static getDefaultComponent(): ?SignDocumentView
	{
		return defaultComponent;
	}
}

declare type SignDocumentViewSendWidgetParameters = {
	memberIds: Array<number>
}

class SignDocumentViewSendWidget
{
	#container: HTMLElement = null;
	#button: HTMLElement = null;
	#docSend: DocumentSend | undefined = undefined;
	#memberIds: Array<number> = [];

	constructor(options: SignDocumentViewSendWidgetParameters = {})
	{
		this.#memberIds = options?.memberIds ?? [];
		this.#button = this.#createButton();

		if (!Type.isUndefined(BX?.Sign?.V2?.DocumentSend))
		{
			this.#docSend = new BX.Sign.V2.DocumentSend();
			this.#docSend.subscribeOnce('ready', (event) => {
				if (event.getData()?.readyMembers?.length)
				{
					this.enableButton();
				}
			});
			this.#docSend.loadStatus(this.#memberIds);
		}
	}

	enableButton(): void
	{
		Dom.removeClass(this.#button, 'crm__sign-document-view-resend--button-disabled');
	}

	disableButton(): void
	{
		Dom.addClass(this.#button, 'crm__sign-document-view-resend--button-disabled');
	}

	isButtonDisabled(): boolean
	{
		return Dom.hasClass(this.#button, 'crm__sign-document-view-resend--button-disabled');
	}

	renderTo(node: HTMLElement): void
	{
		Dom.append(this.render(), node);
	}

	render(): HTMLElement
	{
		if (!this.#container)
		{
			this.#container = Tag.render`
				<div class="crm__sign-document-view-resend--container">
					<div class="crm__sign-document-view-resend--list">
						${this.#button}
					</div>
				</div>
			`;
		}

		return this.#container;
	}

	send(memberIds: Array<number>): Promise
	{
		return this.#docSend.send(memberIds);
	}

	#updateStatus(memberIds: Array<number>): Promise
	{
		return this.#docSend.loadStatus(memberIds);
	}

	#createButton(): HTMLElement
	{
		const onResendBtnClick = (event) => {
			if (this.isButtonDisabled())
			{
				return;
			}

			this.#updateStatus(this.#memberIds).then((readyMembers) => {
				if (readyMembers.length > 0)
				{
					return this.send(readyMembers).then(() => {
						this.disableWithTimer(60);
						BX.UI.Notification.Center.notify({
							content: Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_NOTIFY_SUCCESS'),
						});
					});
				}

				throw new Error('no members in appropriate status');
			});
		};

		return Tag.render`
			<div
				class="crm__sign-document-view-resend--button crm__sign-document-view-resend--button-disabled"
				onclick="${onResendBtnClick.bind(this)}"
			>
				<div class="crm__sign-document-view-resend--button-icon --service-sms"></div>
				<div class="crm__sign-document-view-resend--button-main-title">
					${Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_SEND_AGAIN')}
				</div>
				<div class="crm__sign-document-view-resend--button-helper"></div>
			</div>
		`;
	}

	#createButtonText(remainingSeconds: number): string
	{
		return remainingSeconds > 0
			? Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_SEND_AGAIN_TIMER', {
				'#COUNTDOWN#': this.#formatSeconds(remainingSeconds),
			})
			: Loc.getMessage('CRM_DOCUMENT_VIEW_DOCUMENT_SEND_SEND_AGAIN')
		;
	}

	#setButtonText(text: string): void
	{
		this.#button
			.querySelector('.crm__sign-document-view-resend--button-main-title')
			.textContent = text;
	}

	disableWithTimer(sec: number): void
	{
		this.disableButton();
		let remainingSeconds = sec;

		this.#setButtonText(this.#createButtonText(remainingSeconds));

		const timer = setInterval(() => {
			if (remainingSeconds < 1)
			{
				clearInterval(timer);
				this.#setButtonText(this.#createButtonText(0));
				this.enableButton();
				return;
			}

			remainingSeconds--;
			this.#setButtonText(this.#createButtonText(remainingSeconds));
		}, 1000);
	}

	#formatSeconds(sec: number): string
	{
		const minutes = Math.floor(sec / 60);
		const seconds = sec % 60;

		const formatMinutes = this.#formatNumber(minutes);
		const formatSeconds = this.#formatNumber(seconds);

		return `${formatMinutes}:${formatSeconds}`;
	}

	#formatNumber(num: number): string
	{
		return num < 10 ? `0${num}` : num;
	}
}

namespace.SignDocumentView = SignDocumentView;
namespace.SignDocumentViewSendWidget = SignDocumentViewSendWidget;
