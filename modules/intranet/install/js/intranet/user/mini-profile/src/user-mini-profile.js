import { Tag } from 'main.core';
import { BaseCache, MemoryCache } from 'main.core.cache';
import { EventEmitter } from 'main.core.events';
import { Popup, PopupManager } from 'main.popup';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';

import { UserMiniProfileComponent } from './components/app';
import type { UserMiniProfileOptions } from './type';
import { Tracking } from './utils/tracking';

import 'ui.design-tokens';
import './style.css';

export const PopupPrefixId = 'intranet-user-mini-profile-';
const AngleWidth = 33;

export class UserMiniProfile
{
	#options: UserMiniProfileOptions;

	#cache: BaseCache = new MemoryCache();
	#tracking: Tracking;

	#app: VueCreateAppResult | null = null;

	#closeHandler: () => void = null;

	constructor(options: UserMiniProfileOptions)
	{
		this.#options = options;

		this.#tracking = new Tracking({
			popup: this.#getPopup(),
			bindElement: options.bindElement,
		});
		this.#closeHandler = () => this.close();

		this.#bindEvents();
	}

	destroy(): void
	{
		this.#tracking.unbindTracking();
		this.#getPopup().destroy();
		this.#app?.unmount();

		EventEmitter.unsubscribe('SidePanel.Slider:onOpen', this.#closeHandler);
		EventEmitter.unsubscribe('Intranet.User.MiniProfile:close', this.#closeHandler);
	}

	show(): void
	{
		this.#createAppIfNeed();
		this.#getPopup().show();
	}

	close(): void
	{
		this.#getPopup().close();

		PopupManager.getPopups()
			.filter((popup) => popup.isShown() && popup.getId().includes(PopupPrefixId))
			.forEach((popup) => {
				popup.close();
			})
		;
	}

	setBindElement(element: ?HTMLElement): void
	{
		if (this.#options.bindElement === element)
		{
			return;
		}

		const popup = this.#getPopup();
		popup.close();
		popup.setBindElement(element);
		this.#tracking.setBindElement(element);

		this.#options.bindElement = element;
	}

	getBindElement(): ?HTMLElement
	{
		return this.#options.bindElement;
	}

	#getPopup(): Popup
	{
		return this.#cache.remember('popup', () => {
			return new Popup({
				className: 'intranet-user-mini-profile-popup',
				content: this.#getContainer(),
				targetContainer: document.body,
				bindElement: this.#options.bindElement,
				maxWidth: 643,
				maxHeight: 497,
				padding: 0,
				contentNoPaddings: true,
				angle: {
					offset: this.#options.bindElement.offsetWidth / 2 + AngleWidth / 2,
				},
				animation: 'fading',
				bindOptions: {
					forceBindPosition: true,
					forceTop: true,
				},
			});
		});
	}

	#getContainer(): HTMLElement
	{
		return this.#cache.remember('container', () => {
			return Tag.render`
				<div class="intranet-user-mini-profile --ui-context-content-light"></div>
			`;
		});
	}

	#createAppIfNeed(): void
	{
		if (this.#app)
		{
			return;
		}

		const { userId } = this.#options;
		const popup = this.#getPopup();

		this.#app = BitrixVue.createApp(UserMiniProfileComponent, {
			userId,
			popup,
		});

		this.#app.mount(this.#getContainer());
	}

	#bindEvents(): void
	{
		this.#tracking.setupTracking();
		this.#tracking.subscribe('close', () => this.close());
		this.#tracking.subscribe('show', () => this.show());

		EventEmitter.subscribe('SidePanel.Slider:onOpen', this.#closeHandler);
		EventEmitter.subscribe('Intranet.User.MiniProfile:close', this.#closeHandler);
	}
}
