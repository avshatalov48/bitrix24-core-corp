import subscribe from './event-emitter/subscribe';
import subscribeOnce from './event-emitter/subscribe-once';
import unsubscribe from './event-emitter/unsubscribe';
import emit from './event-emitter/emit';
import setMaxListener from './event-emitter/set-max-listeners';
import getMaxListeners from './event-emitter/get-max-listeners';
import getListeners from './event-emitter/get-listeners';
import prepareEvent from './event-emitter/prepare-event';
import type BaseEvent from './base-event';
import {instances, privateProps} from './event-emitter/private-stores';

/**
 * Implements EventEmitter interface
 */
export default class EventEmitter
{
	constructor()
	{
		instances.add(this);
		privateProps.set(this, {
			eventsMap: new Map(),
			onceMap: new Map(),
			maxListeners: 10,
		});
	}

	/**
	 * Subscribes listener on specified event
	 * @param eventName
	 * @param listener
	 * @return {EventEmitter}
	 */
	subscribe(eventName: any, listener: (event: BaseEvent) => void): this
	{
		subscribe(this, eventName, listener);
		return this;
	}

	/**
	 * Subscribes listener on specified global event
	 * @param eventName
	 * @param listener
	 * @return {EventEmitter}
	 */
	static subscribe(eventName: any, listener: (event: BaseEvent) => void): this
	{
		subscribe(EventEmitter, eventName, listener);
		return this;
	}

	/**
	 * Subscribes a listener that is called at
	 * most once for a specified event.
	 * @param eventName
	 * @param listener
	 * @return {EventEmitter}
	 */
	subscribeOnce(eventName: any, listener: (event: BaseEvent) => void): this
	{
		subscribeOnce(this, eventName, listener);
		return this;
	}

	/**
	 * Subscribes a listener that is called at
	 * most once for a specified global event.
	 * @param eventName
	 * @param listener
	 * @return {EventEmitter}
	 */
	static subscribeOnce(eventName: any, listener: (event: BaseEvent) => void): this
	{
		subscribeOnce(EventEmitter, eventName, listener);
		return this;
	}

	/**
	 * Unsubscribes specified event listener
	 * @param eventName
	 * @param listener
	 * @return {EventEmitter}
	 */
	unsubscribe(eventName: any, listener: (event: BaseEvent) => void): this
	{
		unsubscribe(this, eventName, listener);
		return this;
	}

	/**
	 * Unsubscribes specified global event listener
	 * @param eventName
	 * @param listener
	 * @return {EventEmitter}
	 */
	static unsubscribe(eventName: any, listener: (event: BaseEvent) => void): this
	{
		unsubscribe(EventEmitter, eventName, listener);
		return this;
	}

	/**
	 * Emits specified event with specified event object
	 * @param eventName
	 * @param event
	 * @return {EventEmitter}
	 */
	emit(eventName: any, event?: BaseEvent | {[key: string]: any}): this
	{
		const preparedEvent = prepareEvent(this, eventName, event, this);

		emit(this, eventName, preparedEvent);
		emit(EventEmitter, eventName, preparedEvent);
		return this;
	}

	/**
	 * Emits specified global event with specified event object
	 * @param eventName
	 * @param event
	 * @return {EventEmitter}
	 */
	static emit(eventName: any, event?: BaseEvent | {[key: string]: any}): this
	{
		const preparedEvent = prepareEvent(this, eventName, event, this);

		emit(EventEmitter, eventName, preparedEvent);
		instances.forEach((instance) => {
			const {eventsMap} = privateProps.get(instance);

			if (eventsMap.has(eventName))
			{
				emit(instance, eventName, preparedEvent);
			}
		});
		return this;
	}

	/**
	 * Emits event and returns a promise that is resolved when
	 * all promise returned from event handlers are resolved,
	 * or rejected when at least one of the returned promise is rejected.
	 * Importantly. You can return any value from synchronous handlers, not just promise
	 * @param eventName
	 * @param event
	 * @return {Promise<Array>}
	 */
	emitAsync(eventName: any, event?: BaseEvent | {[key: string]: any}): Promise<Array>
	{
		const preparedEvent = prepareEvent(this, eventName, event, this);

		return Promise.all([
			...emit(this, eventName, preparedEvent),
			...emit(EventEmitter, eventName, preparedEvent),
		]);
	}

	/**
	 * Emits global event and returns a promise that is resolved when
	 * all promise returned from event handlers are resolved,
	 * or rejected when at least one of the returned promise is rejected.
	 * Importantly. You can return any value from synchronous handlers, not just promise
	 * @param eventName
	 * @param event
	 * @return {Promise<Array>}
	 */
	static emitAsync(eventName: any, event?: BaseEvent | {[key: string]: any}): Promise<Array>
	{
		const preparedEvent = prepareEvent(this, eventName, event, this);

		return Promise.all([
			...emit(EventEmitter, eventName, preparedEvent),
			...[...instances].reduce((acc, instance) => {
				const {eventsMap} = privateProps.get(instance);

				if (eventsMap.has(eventName))
				{
					return [...acc, ...emit(instance, eventName, preparedEvent)];
				}

				return acc;
			}, []),
		]);
	}

	/**
	 * Sets max events listeners count
	 * @param {number} count
	 * @return {EventEmitter}
	 */
	setMaxListeners(count: number)
	{
		setMaxListener(this, count);
		return this;
	}

	/**
	 * Sets max global events listeners count
	 * @param {number} count
	 * @return {EventEmitter}
	 */
	static setMaxListeners(count: number)
	{
		setMaxListener(EventEmitter, count);
		return this;
	}

	/**
	 * Gets max event listeners count
	 * @return {*}
	 */
	getMaxListeners(): number
	{
		return getMaxListeners(this);
	}

	/**
	 * Gets max global event listeners count
	 * @return {*}
	 */
	static getMaxListeners(): number
	{
		return getMaxListeners(EventEmitter);
	}

	/**
	 * Gets listeners list for specified event
	 * @param eventName
	 */
	getListeners(eventName: any): Function[] | null
	{
		return getListeners(this, eventName);
	}

	/**
	 * Gets global listeners list for specified event
	 * @param eventName
	 */
	static getListeners(eventName: any): Function[] | null
	{
		return getListeners(EventEmitter, eventName);
	}
}

privateProps.set(EventEmitter, {
	eventsMap: new Map(),
	onceMap: new Map(),
	maxListeners: 10,
});