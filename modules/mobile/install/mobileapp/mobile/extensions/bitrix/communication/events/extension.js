/**
 * @module communication/events
 */
jn.define('communication/events', (require, exports, module) => {
	const { BaseEvent } = require('communication/events/base');
	const { WebEvent } = require('communication/events/web');
	const { PhoneEvent } = require('communication/events/phone');
	const { ImEvent } = require('communication/events/im');
	const { EmailEvent } = require('communication/events/email');

	const SUPPORTED_EVENTS = {
		phone: PhoneEvent,
		web: WebEvent,
		im: ImEvent,
		email: EmailEvent,
	};

	/**
	 * @class CommunicationEvents
	 */
	class CommunicationEvents
	{
		static create({ type, props })
		{
			let event = BaseEvent;

			if (SUPPORTED_EVENTS.hasOwnProperty(type))
			{
				event = SUPPORTED_EVENTS[type];
			}

			return new event(props);
		}

		static execute(props)
		{
			const event = CommunicationEvents.create(props);

			event.open();
		}
	}

	module.exports = { CommunicationEvents };
});
