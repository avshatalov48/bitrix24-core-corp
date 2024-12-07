import type { CopilotMenuItem } from 'ai.copilot';
import { CopilotMenu } from 'ai.copilot';
import { ChangeRequestMenuItem, CancelCopilotMenuItem, RepeatCopilotMenuItem } from 'ai.copilot.copilot-text-controller';
import { BaseError, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';

import { Icon, Main } from 'ui.icon-set.api.core';

import { CopilotInputError } from '../copilot-input-field/src/copilot-input-error';

import './css/copilot-context-menu-error-popup.css';

type CopilotContextMenuErrorPopupOptions = {
	bindElement: HTMLElement;
	error: BaseError;
}

export const CopilotContextMenuErrorPopupEvents = {
	CANCEL: 'cancel',
	REPEAT: 'repeat',
	CHANGE_REQUEST: 'change-request',
};

export class CopilotContextMenuErrorPopup extends EventEmitter
{
	#error: BaseError;
	#bindElement: HTMLElement;
	#popup: Popup;
	#menu: CopilotMenu;
	#errorField: CopilotInputError;

	constructor(options: CopilotContextMenuErrorPopupOptions)
	{
		super(options);

		this.setEventNamespace('AI.CopilotContextMenu:ErrorPopup');
		this.#bindElement = options.bindElement;
		this.#error = options.error;
		this.#errorField = new CopilotInputError({
			errors: [this.#error],
		});
	}

	setError(error: BaseError): void
	{
		this.#error = error;
		this.#errorField.setErrors([this.#error]);
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#initPopup();
		}

		this.#popup.show();
	}

	destroy(): void
	{
		this.#popup?.destroy();
		this.#popup = null;
	}

	isShown(): boolean
	{
		return Boolean(this.#popup?.isShown());
	}

	adjustPosition(): void
	{
		this.#popup?.adjustPosition();
		this.#menu?.adjustPosition();
	}

	setBindElement(bindElement): void
	{
		this.#popup?.setBindElement(bindElement);
		this.adjustPosition();
	}

	#initPopup(): void
	{
		if (this.#popup)
		{
			return;
		}

		this.#popup = new Popup({
			bindElement: this.#bindElement,
			content: this.#getPopupContent(),
			className: 'ai__copilot-scope ai__copilot-context-menu_error-popup',
			maxWidth: 600,
			minHeight: 42,
			padding: 6,
			events: {
				onAfterPopupShow: () => {
					this.#showMenu();
				},
				onPopupClose: () => {
					this.#hideMenu();
				},
				onPopupDestroy: () => {
					this.#hideMenu();
				},
			},
		});

		this.#popup.setOffset({
			offsetTop: 6,
		});
	}

	#getPopupContent(): HTMLElement
	{
		const icon = new Icon({
			icon: Main.WARNING,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-text-alert'),
			size: 24,
		});

		return Tag.render`
			<div class="ai__copilot-context-menu-error-content">
				<div class="ai__copilot-context-menu-error-content-icon">
					${icon.render()}
				</div>
				<div class="ai__copilot-context-menu-error-content-text">
					${this.#errorField.render()}
				</div>
			</div>
		`;
	}

	#showMenu(): void
	{
		if (!this.#menu)
		{
			this.#initMenu();
		}

		this.#menu.setBindElement(this.#popup.getPopupContainer(), {
			top: 6,
		});

		this.#menu.open();
		this.#menu.show();
	}

	#hideMenu(): void
	{
		this.#menu?.close();
		this.#menu = null;
	}

	#initMenu(): void
	{
		this.#menu = new CopilotMenu({
			bindElement: this.#popup.getPopupContainer(),
			items: this.#getMenuItems(),
			cacheable: false,
			forceTop: false,
		});
	}

	#getMenuItems(): CopilotMenuItem[]
	{
		return [
			(new RepeatCopilotMenuItem({
				icon: null,
				onClick: () => {
					this.emit(CopilotContextMenuErrorPopupEvents.REPEAT);
				},
			})).getOptions(),
			(new ChangeRequestMenuItem({
				icon: null,
				onClick: () => {
					this.emit(CopilotContextMenuErrorPopupEvents.CHANGE_REQUEST);
				},
			})).getOptions(),
			(new CancelCopilotMenuItem({
				icon: null,
				onClick: () => {
					this.emit(CopilotContextMenuErrorPopupEvents.CANCEL);
				},
			})).getOptions(),
		];
	}
}
