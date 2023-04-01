import {Dom} from 'main.core';
import {InfoPopup as InfoPopupComponent} from './components/InfoPopup';
import {BitrixVue} from 'ui.vue3';
import {Popup} from 'main.popup';
import './styles/info-popup.css';

export type InfoPopupOptions = {
	id: string;
	content: InfoPopupContentOptions,
}

export type InfoPopupContentOptions = {
	header: {
		icon: string;
		title: string;
		hint?: string;
		subtitle?: string;
	};
	fields: Object;
}

export class InfoPopup
{
	#popup: Popup | null;
	id: string;
	header: Object;
	contentFields: Object;

	constructor(options: InfoPopupOptions = {name: 'InfoPopup'})
	{
		this.id = options.id;
		this.#popup = null;
		this.header = options.content.header;
		this.contentFields = options.content.fields;
	}

	show(): void
	{
		const content = Dom.create('div');
		BitrixVue.createApp(InfoPopupComponent, {
			header: this.header,
			fields: this.contentFields,
		}).mount(content);


		this.#popup = new Popup({
			className: 'crm__info-popup-window',
			content: content,
			width: 532,
			noAllPaddings: true,
			closeByEsc: true,
			closeIcon: true,
			autoHide: true,
			borderRadius: 10,
			animation: 'fading-slide',
		});

		this.#popup.show();
	}

	hide(): void
	{
		if (this.#popup)
		{
			this.#popup.destroy();
		}
	}

	getPopup(): Popup | null
	{
		return this.#popup;
	}
}