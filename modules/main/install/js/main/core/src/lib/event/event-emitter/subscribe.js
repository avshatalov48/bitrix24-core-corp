import Type from '../../type';
import {privateProps} from './private-stores';

export default function subscribe(context, eventName: any, listener: Function)
{
	if (!Type.isFunction(listener))
	{
		throw new TypeError(`The "listener" argument must be of type Function. Received type ${typeof listener}`);
	}

	const {eventsMap, maxListeners} = privateProps.get(context);

	if (eventsMap.has(eventName))
	{
		eventsMap.get(eventName).add(listener);
	}
	else
	{
		eventsMap.set(eventName, new Set([listener]));
	}

	const listeners = eventsMap.get(eventName);

	if (listeners.size > maxListeners)
	{
		console.warn(`Possible BX.Event.EventEmitter memory leak detected. ${listeners.size} ${eventName} listeners added. Use emitter.setMaxListeners() to increase limit`);
	}

	return context;
}