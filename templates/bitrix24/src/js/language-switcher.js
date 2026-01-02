import { Tag, Dom, Event, Runtime } from 'main.core';
import { type Popup as PopupInstance, type PopupOptions } from 'main.popup';

import { getBackUrl } from './helpers';

import '../css/language-switcher.css';

type LanguageList = {
	[languageCode: string]: LanguageListItem[];
}

type LanguageListItem = {
	NAME: string;
	IS_BETA: boolean;
};

export class LanguageSwitcher
{
	#popup: ?PopupInstance;

	async showLanguageListPopup(bindElement: HTMLElement, languages: LanguageList): void
	{
		if (!this.#popup)
		{
			await this.#initPopup(bindElement, languages);
		}

		if (this.#popup.isShown())
		{
			return;
		}

		this.#popup.show();
	}

	async #initPopup(bindElement: HTMLElement, languages: LanguageList): PopupInstance
	{
		const windowScrollHandler = () => {
			this.hideLanguageListPopup();
		};

		const { Popup } = await Runtime.loadExtension('main.popup');

		const popupOptions: PopupOptions = {
			bindElement,
			autoHide: true,
			closeByEcs: true,
			cachable: false,
			content: this.#renderPopupContent(languages),
			events: {
				onPopupClose: () => {
					Event.unbind(window, 'scroll', windowScrollHandler);
					this.#popup.destroy();
					this.#popup = null;
				},
			},
		};

		this.#popup = new Popup(popupOptions);

		Event.bind(window, 'scroll', windowScrollHandler);
	}

	hideLanguageListPopup(): void
	{
		this.#popup?.close();
	}

	switchPortalLanguage(languageCode: string): void
	{
		window.location.href = `/auth/?user_lang=${languageCode}&backurl=${getBackUrl()}`;
	}

	#renderPopupContent(languages: LanguageList): HTMLElement
	{
		const container = Tag.render`<div class="intranet__language-popup_list"></div>`;

		Object.entries(languages).forEach(([languageCode: string, languageItem: LanguageListItem]) => {
			const languageItemElement = Tag.render`
				<div class="intranet__language-popup_language-item">
					<span class="intranet__language-popup_language-item-name">${languageItem.NAME}</span>
					<span class="intranet__language-popup_language-beta">${languageItem.IS_BETA ? ', beta' : ''}</span>
				</div>
			`;

			Event.bind(languageItemElement, 'click', () => {
				this.switchPortalLanguage(languageCode);
			});

			Dom.append(languageItemElement, container);
		});

		return container;
	}
}

export const languageSwitcher = new LanguageSwitcher();
