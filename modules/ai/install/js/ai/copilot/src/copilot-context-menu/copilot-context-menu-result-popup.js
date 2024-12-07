import { Dom, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { CopilotMenu } from 'ai.copilot';
import {
	ChangeRequestMenuItem,
	type CopilotTextControllerEngine,
	CopyResultMenuItem,
	FeedbackMenuItem,
} from 'ai.copilot.copilot-text-controller';
import type { CopilotAnalytics } from '../copilot-analytics';

import type { CopilotMenuItem } from '../copilot-menu';
import { CopilotWarningResultField } from '../copilot-warning-result-field';

import './css/copilot-context-menu-result-popup.css';

type CopilotReadonlyResultPopupOptions = {
	bindElement: HTMLElement;
	additionalResultMenuItems: CopilotItem[];
	engine: CopilotTextControllerEngine;
	analytics: CopilotAnalytics;
}

export const CopilotContextMenuResultPopupEvents = {
	SAVE: 'save',
	CANCEL: 'cancel',
	CHANGE_REQUEST: 'change-request',
	CLOSE: 'close',
};

export class CopilotContextMenuResultPopup extends EventEmitter
{
	#popup: Popup | null = null;
	#bindElement: HTMLElement;
	#resultContainer: HTMLElement;
	#resultText: string = '';
	#resultMenu: CopilotMenu;
	#additionalResultMenuItems: CopilotMenuItem[] = [];
	#engine: CopilotTextControllerEngine;
	#analytics: CopilotAnalytics;

	constructor(options: CopilotReadonlyResultPopupOptions)
	{
		super();
		this.setEventNamespace('AI.CopilotReadonly:ResultPopup');
		this.#bindElement = options.bindElement;
		this.#additionalResultMenuItems = options.additionalResultMenuItems.map((menuItem: CopilotMenuItem) => {
			return {
				...menuItem,
				command: () => {
					menuItem.command(this.#resultText);
				},
			};
		});

		this.#analytics = options.analytics;
		this.#engine = options.engine;
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#initPopup();
			this.#popup.setOffset({
				offsetTop: 6,
			});
		}

		if (!this.#resultMenu)
		{
			this.#initResultMenu();
		}

		this.#popup.show();
		this.#resultMenu.setBindElement(this.#popup.getPopupContainer(), {
			top: 4,
		});

		this.#resultMenu.open();
	}

	isShown(): boolean
	{
		return Boolean(this.#popup?.isShown());
	}

	adjustPosition(): void
	{
		this.#popup?.adjustPosition();
		this.#resultMenu?.adjustPosition();
	}

	destroy(): void
	{
		this.#popup?.destroy();
		this.#popup = null;
		this.#resultMenu?.close();
		this.#resultMenu = null;
		this.#resultContainer = null;
	}

	setBindElement(bindElement): void
	{
		this.#popup?.setBindElement(bindElement);
		this.adjustPosition();
	}

	getResult(): string
	{
		return this.#resultText;
	}

	setResult(text: string): void
	{
		this.#resultText = text;

		if (this.#resultContainer)
		{
			this.#resultContainer.innerText = text;
		}
	}

	#initPopup(): void
	{
		this.#popup = new Popup({
			content: this.#renderPopupContent(),
			bindElement: this.#bindElement,
			cacheable: false,
			className: 'ai__copilot-scope ai__copilot-context-menu__result-popup',
			width: 530,
			closeIcon: true,
			closeIconSize: 'large',
			events: {
				onPopupShow: () => {
					if (this.#resultContainer.scrollHeight > this.#resultContainer.offsetHeight)
					{
						Dom.addClass(this.#resultContainer, '--with-scroll');
					}
				},
				onPopupClose: () => {
					this.#resultMenu.close();
					this.emit(CopilotContextMenuResultPopupEvents.CLOSE);
				},
				onPopupAfterClose: () => {
					this.destroy();
				},
			},
		});
	}

	#initResultMenu(): void
	{
		this.#resultMenu = new CopilotMenu({
			bindElement: this.#resultContainer,
			cacheable: false,
			forceTop: false,
			items: [
				(new ChangeRequestMenuItem({
					icon: null,
					onClick: () => {
						this.emit(CopilotContextMenuResultPopupEvents.CHANGE_REQUEST);
						this.#analytics.sendEventCancel();
					},
				})).getOptions(),
				{
					separator: true,
				},
				(new CopyResultMenuItem({
					getText: () => {
						this.#analytics.sendEventCopyResult();

						return this.#resultText;
					},
				})).getOptions(),
				...this.#additionalResultMenuItems,
				{
					separator: true,
				},
				(new FeedbackMenuItem({
					icon: null,
					isBeforeGeneration: false,
					engine: this.#engine,
				})).getOptions(),
			],
		});
	}

	#renderPopupContent(): HTMLElement
	{
		const warningField = new CopilotWarningResultField();

		return Tag.render`
			<div class="ai__copilot-context-menu__result-popup-content">
				${this.#renderResultContainer()}
				<div class="ai__copilot-context-menu_result-popup-warning">
					${warningField.render(true)}
				</div>
			</div>
		`;
	}

	#renderResultContainer(): HTMLElement
	{
		this.#resultContainer = Tag.render`
			<div class="ai__copilot-context-menu__result-popup-text">${this.#resultText}</div>
		`;

		return this.#resultContainer;
	}
}
