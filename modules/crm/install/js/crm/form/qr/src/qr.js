import 'ui.design-tokens';
import 'ui.fonts.opensans';
import "main.qrcode";
import {Event, Loc, Tag} from 'main.core';
import {Popup} from 'main.popup';
import 'ui.notification';
import './style.css';

export type QrOptions = {
	link: string;
};

export class Qr
{
	#link: string;
	#qrNode: HTMLElement = null;
	#button: HTMLElement = null;
	#containerCopyLink: HTMLElement = null;
	#containerInputLink: HTMLElement = null;

	constructor(options: QrOptions)
	{
		this.#link = options.link;
	}

	renderTo(target: HTMLElement): HTMLElement
	{
		const button = this.#renderButton();
		target.appendChild(button);
		return button;
	}

	#renderButton(): HTMLElement
	{
		if (!this.#button)
		{
			this.#button = Tag.render`
				<button
					type="button"
					class="crm-webform-qr-btn ui-btn ui-btn-xs ui-btn-light-border ui-btn-round ui-btn-no-caps ui-btn-icon-share"
				>
					${Loc.getMessage('CRM_WEBFORM_QR_OPEN')}
				</button>
			`;

			this.#button.addEventListener("click", (e) => {
				e.stopPropagation();
				this.show();
			});
		}

		return this.#button;
	}

	#getImageContainer(): HTMLElement
	{
		if(!this.#qrNode)
		{
			this.#qrNode = Tag.render`
				<div class="crm-webform__popup-image"></div>
			`;
		}

		return this.#qrNode;
	}

	#getPopup(): Popup
	{
		if(!this.popup)
		{
			const container = Tag.render`
				<div class="crm-webform__scope">
					<div class="crm-webform__popup-container --qr">
						<div class="crm-webform__popup-wrapper">
							<div class="crm-webform__popup-content">
								<div class="crm-webform__popup-text">${Loc.getMessage('CRM_WEBFORM_QR_TITLE')}</div>
								${this.#getImageContainer()}
								<div class="crm-webform__popup-text --sm">
									${Loc.getMessage('CRM_WEBFORM_QR_DESC')}
								</div>
								<div class="crm-webform__popup-buttons">
									<a href="${this.#link}" target="_blank" class="ui-btn ui-btn-light-border ui-btn-round">
										${Loc.getMessage('CRM_WEBFORM_QR_TILE_POPUP_OPEN_SITE')}
									</a>
								</div>
							</div>
							<div class="crm-webform__popup-bottom">
								<a href="${this.#link}" target="_blank" class="crm-webform__popup-url">
									${this.#link}
									${this.#getContainerInputLink()}
								</a>
								${this.#getContainerCopyLink()}
							</div>
						</div>
					</div>
				</div>
			`;

			this.popup = new Popup({
				className: 'crm-webform__status-popup',
				content: container,
				bindElement: window,
				width: 405,
				minWidth: 220,
				closeByEsc: true,
				autoHide: true,
				animation: 'fading-slide',
				closeIcon: true,
				padding: 0
			});
		}

		return this.popup;
	}

	show(): void
	{
		this.#renderImage();
		if (!this.#getPopup().isShown())
		{
			this.#getPopup().show();
		}
	}

	close(): void
	{
		if (this.#getPopup().isShown())
		{
			this.#getPopup().close();
		}
	}

	#renderImage()
	{
		if (!this.#qrNode)
		{
			new QRCode(this.#getImageContainer(), {
				text: this.#link,
				width: 250,
				height: 250
			})
		}
	}

	#getContainerInputLink()
	{
		if(!this.#containerInputLink)
		{
			this.#containerInputLink = Tag.render`
				<input 
					type="text" 
					style="position: absolute; opacity: 0; pointer-events: none"
					value="${this.#link}">
			`;
		}

		return this.#containerInputLink;
	}

	#getContainerCopyLink()
	{
		if(!this.#containerCopyLink)
		{
			this.#containerCopyLink = Tag.render`
				<div class="crm-webform__popup-copy">
					${Loc.getMessage('CRM_WEBFORM_QR_TILE_POPUP_COPY_LINK')}
				</div>
			`;

			Event.bind(this.#containerCopyLink, 'click', ()=> {
				this.#getContainerInputLink().select();
				document.execCommand('copy');
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_WEBFORM_QR_TILE_POPUP_COPY_LINK_COMPLETE'),
					autoHideDelay: 2000,
				});
			});
		}

		return this.#containerCopyLink;
	}
}
