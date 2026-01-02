import { BaseCache, MemoryCache } from 'main.core.cache';
import { ajax, Event, Runtime, Type, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { WidgetLoader } from 'intranet.widget-loader';
import { InvitationWidget } from 'intranet.invitation-widget';
import {Counter, CounterStyle} from 'ui.cnt';

type InvitationButtonOptions = {
	skeleton: Object,
	counterId: string,
	invitationCounter: number,
	shouldShowStructureCounter: boolean,
};

export class InvitationButton
{
	static #buttonWrapper: HTMLElement;
	static #cache: BaseCache<any> = new MemoryCache();
	static #options: InvitationButtonOptions;

	static init(options: InvitationButtonOptions): void
	{
		this.#options = options;
		this.#buttonWrapper = document.querySelector('[data-id="invitationButton"]');

		Event.bind(this.#buttonWrapper, 'click', () => {
			Event.unbindAll(this.#buttonWrapper);
			this.#getWidgetLoader()
				.createSkeletonFromConfig(options.skeleton)
				.show();
			Runtime.loadExtension(['intranet.invitation-widget']).then(() => {
				this.#showWidget();
			}).catch(() => {});
		});

		if (this.#options.invitationCounter > 0)
		{
			this.#getCounter().renderTo(this.#getCounterWrapper());
		}

		this.#setEventHandlers();
	}

	static #showWidget(): void
	{
		this.#getContent().then((response) => {
			this.#getWidgetLoader().clearBeforeInsertContent();
			InvitationWidget.getInstance().setOptions({
				buttonWrapper: this.#buttonWrapper,
				loader: this.#getWidgetLoader().getPopup(),
				...response.data,
			}).show();
			Event.bind(this.#buttonWrapper, 'click', () => {
				InvitationWidget.getInstance().show();
			});
		}).catch(() => {});
	}

	static #getWidgetLoader(): WidgetLoader
	{
		return this.#cache.remember('widgetLoader', () => {
			return new WidgetLoader({
				bindElement: this.#buttonWrapper,
				width: 350,
				id: 'bx-invitation-header-popup',
			});
		});
	}

	static #getContent(): Promise
	{
		return this.#cache.remember('content', () => {
			return new Promise((resolve, reject) => {
				ajax.runAction('intranet.invitationwidget.getData', {
					data: {},
					analyticsLabel: {
						headerPopup: 'Y',
					},
				})
					.then((response) => resolve(response))
					.catch((response) => reject(response))
				;
			});
		});
	}

	static #setEventHandlers(): void
	{
		EventEmitter.subscribeOnce('HR.company-structure:first-popup-showed', this.#onFirstWatchNewStructure.bind(this));
		EventEmitter.subscribe('onPullEvent-main', (event) => {
			const [command, params] = event.getCompatData();

			if (command === 'user_counter' && params[Loc.getMessage('SITE_ID')])
			{
				const value = params[Loc.getMessage('SITE_ID')][this.#options.counterId];

				if (value > 0)
				{
					this.#onReceiveCounterValue(value);
				}
			}
		});
	}

	static #onReceiveCounterValue(value): void
	{
		if (this.#options.shouldShowStructureCounter)
		{
			value++;
		}

		this.#getCounter().update(value);
		this.#options.invitationCounter = value;

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

	static #getCounterWrapper(): HTMLElement
	{
		return this.#cache.remember('counter-wrapper', () => {
			return this.#buttonWrapper.querySelector('.invitation-widget-counter');
		});
	}

	static #getCounter(): Counter
	{
		return this.#cache.remember('counter', () => {
			return new Counter({
				value: this.#getCounterValue(),
				color: Counter.Color.DANGER,
				useAirDesign: true,
				style: CounterStyle.FILLED_ALERT,
			});
		});
	}

	static #getCounterValue(): number
	{
		let counterValue = Number(this.#options.invitationCounter);
		if (this.#options.shouldShowStructureCounter ?? false)
		{
			counterValue++;
		}

		return counterValue;
	}

	static #onFirstWatchNewStructure(): void
	{
		let value = this.#getCounter().value;

		if (!Type.isNumber(value))
		{
			return;
		}

		if (!this.#options.shouldShowStructureCounter)
		{
			return;
		}

		value--;
		this.#options.shouldShowStructureCounter = false;
		this.#getCounter().update(value);
		this.#options.invitationCounter = value;

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
