import { EventEmitter } from 'main.core.events';
import { EventType } from 'sale.payment-pay.const';

export class UserConsent
{
	/**
	 * @public
	 * @param {object} options
	 */
	constructor(options)
	{
		this.options = options || {};

		this.eventName = this.option('eventName', false);
		this.items = this.option('items', []);
		this.preselectedConsentsIsSubmitted = false;
		this.setCallback(null);

		this.subscribeToEvents();
	}

	setCallback(callback): void
	{
		this.callback = callback;
	}

	runCallback(): void
	{
		if (this.callback)
		{
			this.callback();
		}
		this.setCallback(null);
	}

	/**
	 * @private
	 */
	subscribeToEvents()
	{
		EventEmitter.subscribe(EventType.consent.accepted, (event) => {
			const currentId = event.getData()[0].config.id;
			const currentItem = this.getItemById(currentId);
			if (currentItem)
			{
				currentItem.checked = 'Y';
			}

			const firstRequiredUncheckedItem = this.getFirstRequiredUncheckedItem();
			if (firstRequiredUncheckedItem && this.callback)
			{
				EventEmitter.emit(`${this.eventName}-${firstRequiredUncheckedItem.id}`);

				return;
			}

			this.runCallback();
		});
		EventEmitter.subscribe(EventType.consent.refused, (event) => {
			const currentId = event.getData()[0].config.id;
			const currentItem = this.getItemById(currentId);
			if (currentItem)
			{
				currentItem.checked = 'N';
			}

			this.setCallback(null);
		});
	}

	/**
	 * @public
	 * @returns {boolean}
	 */
	isAvailable(): boolean
	{
		return BX.UserConsent && this.eventName;
	}

	/**
	 * @public
	 * @param callback
	 */
	askUserToPerform(callback): void
	{
		this.setCallback(callback);
		if (!this.isAvailable())
		{
			this.runCallback();

			return;
		}

		if (!this.preselectedConsentsIsSubmitted)
		{
			this.sendEventsForCheckedConsents();
			this.preselectedConsentsIsSubmitted = true;
		}

		const firstRequiredUncheckedItem = this.getFirstRequiredUncheckedItem();
		if (firstRequiredUncheckedItem)
		{
			EventEmitter.emit(`${this.eventName}-${firstRequiredUncheckedItem.id}`);

			return;
		}

		this.runCallback();
	}

	sendEventsForCheckedConsents(): void
	{
		this.items.forEach((item) => {
			if (item.checked === 'Y')
			{
				EventEmitter.emit(`${this.eventName}-${item.id}`);
			}
		});
	}

	getFirstRequiredUncheckedItem(): ?Object
	{
		return this.items.find((item) => item.required === 'Y' && item.checked === 'N') ?? null;
	}

	getItemById(currentId): ?Object
	{
		return this.items.find((item) => parseInt(item.id, 10) === currentId) ?? null;
	}

	/**
	 * @private
	 * @param {string} name
	 * @param defaultValue
	 * @returns {*}
	 */
	option(name: string, defaultValue): Object
	{
		return Object.hasOwn(this.options, name) ? this.options[name] : defaultValue;
	}
}
