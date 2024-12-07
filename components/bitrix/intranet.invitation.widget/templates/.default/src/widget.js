import { EventEmitter } from 'main.core.events';
import { Cache, Event, Type } from 'main.core';
import { Counter } from 'ui.cnt';
import { Analytics } from './analytics';
import type { InvitationWidgetOptions } from './types/options';
import { InvitationPopup } from "./popup";

export class InvitationWidget extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: InvitationWidgetOptions) {
		super();
		this.setEventNamespace('BX.Intranet.InvitationWidget');
		this.setOptions(options);
		Analytics.isAdmin = this.getOptions().isCurrentUserAdmin;
		Event.bind(this.getOptions().button, 'click', () => {
			Analytics.send(Analytics.EVENT_SHOW);
			this.#getPopup().show();
		});
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'BX.Bitrix24.NotifyPanel:showInvitationWidget', () => {
			this.#getPopup().show();
		});
		this.#showCounter();
	}

	setOptions(options: InvitationWidgetOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): InvitationWidgetOptions
	{
		return this.#cache.get('options', {});
	}

	#showCounter(): void
	{
		if (this.getOptions().invitationCounter > 0)
		{
			this.#getCounter().renderTo(this.#getCounterWrapper());
		}

		BX.addCustomEvent('onPullEvent-main', this.#onReceiveCounterValue.bind(this));
	}

	#onReceiveCounterValue(command, params): void
	{
		if (command === 'user_counter' && params[BX.message('SITE_ID')])
		{
			const counters = BX.clone(params[BX.message('SITE_ID')]);
			const value = counters[this.getOptions().counterId];

			if (!Type.isNumber(value))
			{
				return;
			}

			this.#getCounter().update(value);
			this.getOptions().invitationCounter = value;

			if (value > 0)
			{
				this.#getCounter().renderTo(this.#getCounterWrapper());
			}
			else
			{
				this.#getCounter().destroy();
				this.#cache.delete('counter');
			}
		}
	}

	#getCounterWrapper(): HTMLElement
	{
		return this.#cache.remember('counter-wrapper', () => {
			return this.getOptions().button.querySelector('.invitation-widget-counter');
		});
	}

	#getCounter(): Counter
	{
		return this.#cache.remember('counter', () => {
			return new Counter({
				value: Number(this.getOptions().invitationCounter),
				color: Counter.Color.DANGER,
			});
		});
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
					invitationCounter: this.getOptions().invitationCounter,
					counterId: this.getOptions().counterId,
				},
			});
		});
	}
}