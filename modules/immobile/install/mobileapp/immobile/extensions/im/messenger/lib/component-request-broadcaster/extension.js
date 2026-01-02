/**
 * @module im/messenger/lib/component-request-broadcaster
 */
jn.define('im/messenger/lib/component-request-broadcaster', (require, exports, module) => {
	const { Type } = require('type');
	const { isEqual } = require('utils/object');
	const { EntityReady } = require('entity-ready');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('component-request-broadcaster');

	const EVENT_PREFIX = 'ImMobileRequestBroadcaster';

	let instance = null;
	/**
	 * @class ComponentRequestBroadcaster
	 */
	class ComponentRequestBroadcaster
	{
		/**
		 * @return {ComponentRequestBroadcaster}
		 */
		static getInstance()
		{
			instance ??= new this();

			return instance;
		}

		constructor()
		{
			/**
			 * @type {Map<string, function>}
			 */
			this.requestHandlerCollection = new Map();
			this.internalComponentEmitter = serviceLocator.get('emitter');
		}

		get className()
		{
			return this.constructor.name;
		}

		get currentComponentCode()
		{
			return MessengerParams.getComponentCode();
		}

		/**
		 * @desc Allows you to send events to components, expecting until all event handlers have been executed.
		 *
		 * @param {string} eventId
		 * @param {Array<ComponentRequest>} requestOptionList
		 * @return {Promise<void>}
		 */
		async send(eventId, requestOptionList)
		{
			return new Promise((resolve, reject) => {
				if (!Type.isStringFilled(eventId))
				{
					reject(new Error('requestId is not a string filled'));

					return;
				}

				if (!Type.isArrayFilled(requestOptionList))
				{
					reject(new Error('requestOptionList is not an array filled'));

					return;
				}

				const handlerIdListToWait = this.#getHandlerIdListToExpect(requestOptionList);

				const currentRespondingList = [];

				const responseHandler = (response) => {
					const { handlerId, error } = response;
					logger.log(`${this.className} handle response from ${eventId} handler`, response);

					currentRespondingList.push(handlerId);
					currentRespondingList.sort(); // required for proper operation isEqual

					if (error)
					{
						logger.error(`${this.className} handle response with error`, {
							eventId,
							handlerId,
							error,
						});

						reject(error);

						return;
					}

					if (isEqual(handlerIdListToWait, currentRespondingList))
					{
						logger.warn(`${this.className} broadcast event: ${eventId} completed`);

						this.#off(this.#getResponseId(eventId), responseHandler);

						resolve();
					}
				};

				this.#on(this.#getResponseId(eventId), responseHandler);

				this.#expectRegisteredHandlerList(eventId, handlerIdListToWait)
					.then(() => {
						const filteredRequestOptionList = requestOptionList.filter((request) => {
							return handlerIdListToWait.includes(request.handlerId);
						});

						for (const requestOptions of filteredRequestOptionList)
						{
							this.#emitRequest(eventId, requestOptions);
						}
					})
					.catch((error) => {
						logger.error(`${this.constructor.name}.send: error`, error);

						reject(error);
					})
				;
			});
		}

		/**
		 *
		 * @param {string} eventId
		 * @param {string} handlerId the unique ID of the handler.
		 * It is required to register multiple handlers for an event in the same component.
		 * @param {({data: object}) => void | Promise} handler
		 */
		registerHandler(eventId, handlerId, handler)
		{
			const fullHandlerId = this.#getFullHandlerId(eventId, handlerId);

			if (this.requestHandlerCollection.has(fullHandlerId))
			{
				logger.warn(`${this.className}.registerHandler: handler is already exist`, eventId, handlerId);

				return;
			}

			EntityReady.addCondition(fullHandlerId, () => this.requestHandlerCollection.has(fullHandlerId));

			const requestHandler = async (request) => {
				const { handlerId: requestHandlerId, data, fromComponent } = request;
				if (requestHandlerId !== handlerId)
				{
					return;
				}
				logger.log(`${this.className}.registerHandler: handle request:`, request);

				try
				{
					const result = handler(data);

					if (result instanceof Promise)
					{
						await result;
					}

					this.#emitResponse(eventId, {
						handlerId,
						error: null,
						toComponent: fromComponent,
					});
				}
				catch (error)
				{
					this.#emitResponse(eventId, {
						handlerId,
						error,
						toComponent: fromComponent,
					});
				}
			};

			this.#on(this.#getRequestId(eventId), requestHandler);
			this.requestHandlerCollection.set(fullHandlerId, requestHandler);

			EntityReady.ready(fullHandlerId);
		}

		/**
		 * @param {string} eventId
		 * @param {string} handlerId
		 */
		unregisterHandler(eventId, handlerId)
		{
			const fullHandlerId = this.#getFullHandlerId(eventId, handlerId);

			if (!this.requestHandlerCollection.has(fullHandlerId))
			{
				logger.warn(`${this.className}.unregisterHandler: handler was not registered`, eventId, handlerId);

				return;
			}

			const handler = this.requestHandlerCollection.get(fullHandlerId);

			this.#off(this.#getRequestId(eventId), handler);
			this.requestHandlerCollection.delete(fullHandlerId);
		}

		unregisterAllHandlers()
		{
			for (const fullHandlerId of this.requestHandlerCollection.keys())
			{
				const handler = this.requestHandlerCollection.get(fullHandlerId);

				const [eventId] = fullHandlerId.split('.');

				this.#off(this.#getRequestId(eventId), handler);
				this.requestHandlerCollection.delete(fullHandlerId);
			}
		}

		#getRequestId(eventId)
		{
			return `${EVENT_PREFIX}::${eventId}::request`;
		}

		#getResponseId(eventId)
		{
			return `${EVENT_PREFIX}::${eventId}::response`;
		}

		#getFullHandlerId(eventId, handlerId)
		{
			return `${eventId}.${handlerId}`;
		}

		/**
		 * @param {Array<ComponentRequest>} requestOptionList
		 */
		#getHandlerIdListToExpect(requestOptionList)
		{
			return requestOptionList
				.filter((requestData) => this.#isComponentAvailable(requestData))
				.map((requestData) => requestData.handlerId)
				.sort() // required for proper operation isEqual
			;
		}

		/**
		 * @desc check is available and launched component
		 * @param {ComponentRequest} requestData
		 */
		#isComponentAvailable(requestData)
		{
			if (!MessengerParams.isComponentAvailable(requestData.toComponent))
			{
				return false;
			}

			return EntityReady.isReady(`${requestData.toComponent}::launched`);
		}

		/**
		 * @param {string} eventId
		 * @param {Array<string>} expectingHandlerList
		 */
		async #expectRegisteredHandlerList(eventId, expectingHandlerList)
		{
			const expectedHandlerPromiseList = expectingHandlerList.map((handlerId) => {
				return EntityReady.wait(this.#getFullHandlerId(eventId, handlerId));
			});

			return Promise.all(expectedHandlerPromiseList);
		}

		/**
		 * @param {string} encodedEventId
		 * @param {({data: object}) => void | Promise} eventHandler
		 */
		#off(encodedEventId, eventHandler)
		{
			BX.removeCustomEvent(encodedEventId, eventHandler);
			this.internalComponentEmitter.off(encodedEventId, eventHandler);
		}

		/**
		 * @param {string} encodedEventId
		 * @param {({data: object}) => void | Promise} eventHandler
		 */
		#on(encodedEventId, eventHandler)
		{
			BX.addCustomEvent(encodedEventId, eventHandler);
			this.internalComponentEmitter.on(encodedEventId, eventHandler);
		}

		/**
		 * @param {string} eventId
		 * @param {ComponentRequest} requestOptions
		 */
		#emitRequest(eventId, requestOptions)
		{
			const { toComponent, data, handlerId } = requestOptions;
			const eventData = {
				handlerId,
				data,
				fromComponent: MessengerParams.getComponentCode(),
			};

			const fullEventName = this.#getRequestId(eventId);

			if (toComponent === this.currentComponentCode)
			{
				logger.log(`${this.className}.emitRequest: emit event to current component`, eventId, requestOptions);
				this.internalComponentEmitter.emit(fullEventName, [eventData]);

				return;
			}

			logger.log(`${this.className}.emitRequest: emit event to external component`, eventId, requestOptions, toComponent);

			MessengerEmitter.emit(fullEventName, eventData, toComponent);
		}

		/**
		 * @param {string} eventId
		 * @param {ComponentRequest} requestOptions
		 */
		#emitResponse(eventId, requestOptions)
		{
			const { toComponent, handlerId, error } = requestOptions;
			const eventData = {
				handlerId,
				error,
			};
			const fullEventName = this.#getResponseId(eventId);

			if (toComponent === this.currentComponentCode)
			{
				logger.log(`${this.className}.emitResponse: emit event to current component`, eventId, requestOptions);
				this.internalComponentEmitter.emit(fullEventName, [eventData]);

				return;
			}

			logger.log(`${this.className}.emitResponse: emit event to external component`, eventId, requestOptions, toComponent);

			MessengerEmitter.emit(fullEventName, eventData, toComponent);
		}
	}

	module.exports = { ComponentRequestBroadcaster };
});
