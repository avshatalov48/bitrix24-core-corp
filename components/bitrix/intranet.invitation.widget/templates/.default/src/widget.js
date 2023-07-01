import { EventEmitter } from 'main.core.events';
import { Cache, Event } from 'main.core';
import type { InvitationWidgetOptions } from './types/options';
import { InvitationPopup } from "./popup";

export class InvitationWidget extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: InvitationWidgetOptions) {
		super();
		this.setEventNamespace('BX.Intranet.InvitationWidget');
		this.setOptions(options);
		Event.bind(this.getOptions().button, 'click', () => {
			this.#getPopup().show();
		});
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'BX.Bitrix24.NotifyPanel:showInvitationWidget', () => {
			this.#getPopup().show();
		});
	}

	setOptions(options: InvitationWidgetOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): InvitationWidgetOptions
	{
		return this.#cache.get('options', {});
	}

	#getPopup(): InvitationPopup
	{
		return this.#cache.remember('popup', () => {
			return new InvitationPopup({
				isAdmin: this.getOptions().isCurrentUserAdmin,
				target: this.getOptions().button,
				isExtranetAvailable: this.getOptions().isExtranetAvailable,
				isInvitationAvailable: this.getOptions().isInvitationAvailable,
				params: {
					structureLink: this.getOptions().structureLink,
					invitationLink: this.getOptions().invitationLink,
				}
			});
		});
	}
}