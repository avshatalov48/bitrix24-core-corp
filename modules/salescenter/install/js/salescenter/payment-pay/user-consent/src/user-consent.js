import {EventEmitter} from 'main.core.events';
import {EventType} from 'sale.payment-pay.const';

export class UserConsent
{
	/**
	 * @public
	 * @param {object} options
	 */
	constructor(options)
	{
		this.options = options || {};

		this.accepted = this.option('accepted', false);
		this.container = document.getElementById(this.option('containerId'), '');
		this.eventName = this.option('eventName', false);
		this.callback = null;

		this.subscribeToEvents();
	}

	/**
	 * @private
	 */
	subscribeToEvents()
	{
		EventEmitter.subscribe(EventType.consent.accepted, (event) => {
			this.accepted = true;
			if (this.callback) {
				this.callback();
			}
		});
		EventEmitter.subscribe(EventType.consent.refused, (event) => {
			this.accepted = false;
			this.callback = null;
		});
	}

	/**
	 * @public
	 * @returns {boolean}
	 */
	isAvailable()
	{
		return BX.UserConsent && this.eventName;
	}

	/**
	 * @public
	 * @param callback
	 */
	askUserToPerform(callback)
	{
		if (!this.isAvailable() || this.accepted)
		{
			callback();
			return;
		}

		this.callback = callback;
		EventEmitter.emit(this.eventName);

		if (this.checkCurrentConsent())
		{
			callback();
		}
	}

	checkCurrentConsent()
	{
		if (!BX.UserConsent || !BX.UserConsent.current)
		{
			return false;
		}

		return BX.UserConsent.check(BX.UserConsent.current);
	}

	/**
	 * @private
	 * @param {string} name
	 * @param defaultValue
	 * @returns {*}
	 */
	option(name, defaultValue)
	{
		return this.options.hasOwnProperty(name) ? this.options[name] : defaultValue;
	}
}