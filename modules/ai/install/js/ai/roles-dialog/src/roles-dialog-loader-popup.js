import { Tag } from 'main.core';
import { Popup } from 'main.popup';
import './css/roles-dialog-loader-popup.css';
import './css/roles-dialog-skeleton.css';

export class RolesDialogLoaderPopup
{
	#popup: Popup;

	show(): void
	{
		if (!this.#popup)
		{
			this.#initPopup();
		}

		this.#popup.show();
	}

	hide(): void
	{
		this.#popup?.close();
	}

	#initPopup(): void
	{
		this.#popup = new Popup({
			content: this.#renderContent(),
			resizable: true,
			width: 881,
			height: 621,
			padding: 0,
			contentPadding: 0,
			borderRadius: '10px 10px 4px 4px',
			className: 'ai_roles-dialog_popup',
			animation: true,
			cacheable: false,
		});
	}

	#renderContent(): HTMLElement
	{
		return Tag.render`
			<div class="ai__roles-dialog_loader-popup-inner">
				${this.#renderPopupTitleBar()}
				<div class="ai__roles-dialog_loader-popup-content">
					<div class="ai__roles-dialog_loader-popup-content-left">
						<div style="width: 145px; height: 10px; margin-bottom: 22px; margin-left: 12px;">
							<div class="rec --color-ai"></div>
						</div>
						<div style="width: 100%; height: 119px; margin-bottom: 14px;">
							<div class="rec --color-ai"></div>
						</div>
						<div style="width: 100%; height: 54px;">
							<div class="rec --color-ai"></div>
						</div>
					</div>
					<div class="ai__roles-dialog_loader-popup-content-right">
					<div style="width: 101px; height: 10px; margin-bottom: 22px;">
							<div class="rec"></div>
						</div>
						<div style="width: 100%; height: 75px; margin-bottom: 8px;">
							<div class="rec"></div>
						</div>
						<div style="width: 100%; height: 75px;">
							<div class="rec"></div>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	#renderPopupTitleBar(): HTMLElement
	{
		return Tag.render`
			<div class="ai__roles-dialog_loader-popup-title-bar">
				<div
					style="width: 152px; height: 16px;"
					class="ai__roles-dialog_loader-popup-title-bar-left"
				>
					<div class="rec"></div>
				</div>
				<div
					style="width: 98px; height: 16px;"
					class="ai__roles-dialog_loader-popup-title-bar-left"
				>
					<div class="rec"></div>
				</div>
			</div>
		`;
	}
}
