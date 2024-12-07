import { Dom, Loc, Tag, Text } from 'main.core';
import { Popup, PopupWindowManager } from 'main.popup';
import { ProgressBar } from 'ui.progressbar';
import { UI } from 'ui.notification';

export const PREVIEW_POPUP_CONTENT_STATUS = {
	LOADING: 1,
	SUCCESS: 2,
	FAILED: 3,
};

export default class PreviewPopup
{
	#popup: Popup = null;
	#bindElement: HTMLElement = null;
	#previewContentContainer: HTMLElement = null;
	#previewLoader: ProgressBar = null;
	#entityTypeId: number = null;
	#entityId: number = null;

	constructor(bindElement: HTMLElement, entityTypeId: number, entityId: number)
	{
		this.#bindElement = bindElement;
		this.#entityTypeId = entityTypeId;
		this.#entityId = entityId;
	}

	destroy(): void
	{
		this.#getPopup()?.destroy();
	}

	isShown(): boolean
	{
		this.#getPopup()?.isShown();
	}

	show(): void
	{
		this.#getPopup()?.show();
	}

	apply(status: number, data: string = ''): void
	{
		const closeIconElement = this
			.#getPopup()
			.getPopupContainer()
			.querySelector('.popup-window-close-icon')
		;

		switch (status)
		{
			case PREVIEW_POPUP_CONTENT_STATUS.LOADING:
			{
				Dom.addClass(closeIconElement, '--hidden');

				this.#previewContentContainer.innerText = '';

				if (!this.#previewLoader)
				{
					this.#previewLoader = new ProgressBar({
						color: ProgressBar.Color.PRIMARY,
						size: 10,
						maxValue: 100,
						value: 30,
						infiniteLoading: true,
					});
				}
				this.#getPopup().setHeight(75);
				this.#previewLoader.renderTo(this.#previewContentContainer);

				break;
			}

			case PREVIEW_POPUP_CONTENT_STATUS.SUCCESS:
			{
				this.#getPopup().setHeight(null);
				this.#getPopup().setAutoHide(true);
				this.#previewContentContainer.innerText = data;

				Dom.removeClass(closeIconElement, '--hidden');
				Dom.addClass(this.#previewContentContainer, '--loaded');

				break;
			}

			case PREVIEW_POPUP_CONTENT_STATUS.FAILED:
			{
				this.#getPopup().destroy();

				UI.Notification.Center.notify({
					content: Text.encode(data),
					autoHideDelay: 5000,
				});

				break;
			}
			default:
				throw new TypeError(`Unsupported preview popup content status ${status}`);
		}
	}

	#getPopup(): Popup
	{
		if (this.#popup === null)
		{
			this.#popup = PopupWindowManager.create({
				id: `crm-template-editor-preview-popup-${this.#entityTypeId}-${this.#entityId}`,
				bindElement: this.#bindElement,
				closeIcon: { top: '10px' },
				cacheable: false,
				closeByEsc: false,
				autoHide: false,
				angle: {
					position: 'top',
					offset: 70,
				},
				content: this.#getContent(),
			});
		}

		return this.#popup;
	}

	#getContent(): HTMLElement
	{
		this.#previewContentContainer = Tag.render`<div class="crm-template-editor-preview-popup-content"></div>`;

		return Tag.render`
			<div class="crm-template-editor-preview-popup-wrapper">
				<div class="crm-template-editor-preview-popup-title">
					${Loc.getMessage('CRM_TEMPLATE_EDITOR_PREVIEW_POPUP_TITLE')}
				</div>
				${this.#previewContentContainer}
			</div>
		`;
	}
}
