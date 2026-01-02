import { EventEmitter } from 'main.core.events';
import { Cache, Event } from 'main.core';
import { Analytics } from './analytics';
import type { InvitationWidgetOptions } from './types/options';
import { InvitationPopup } from './popup';
import './style.css';

export class InvitationWidget extends EventEmitter
{
	#cache = new Cache.MemoryCache();
	static #instance: InvitationWidget;

	constructor()
	{
		super();
		this.setEventNamespace('BX.Intranet.InvitationWidget');
	}

	static getInstance(): InvitationWidget
	{
		if (!this.#instance)
		{
			this.#instance = new this();
		}

		return this.#instance;
	}

	show(): void
	{
		if (this.#getPopup().getPopup().isShown())
		{
			return;
		}

		this.#getPopup().show();
	}

	setOptions(options: InvitationWidgetOptions): void
	{
		this.#cache.set('options', options);
		Analytics.isAdmin = this.getOptions().isCurrentUserAdmin;
		Event.bind(this.getOptions().button, 'click', () => {
			Analytics.send(Analytics.EVENT_SHOW);
			this.#getPopup().show();
		});
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'BX.Bitrix24.NotifyPanel:showInvitationWidget', () => {
			this.#getPopup().show();
		});

		return this;
	}

	getOptions(): InvitationWidgetOptions
	{
		return this.#cache.get('options', {});
	}

	#getPopup(): InvitationPopup
	{
		return this.#cache.remember('popup', () => {
			return new InvitationPopup({
				...this.getOptions(),
			});
		});
	}
}
