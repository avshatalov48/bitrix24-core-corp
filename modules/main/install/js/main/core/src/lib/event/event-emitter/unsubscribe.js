import Type from '../../type';
import {privateProps} from './private-stores';

export default function unsubscribe(context, eventName, listener)
{
	if (!Type.isFunction(listener))
	{
		throw new TypeError(`The "listener" argument must be of type Function. Received type ${typeof event}`);
	}

	const {eventsMap} = privateProps.get(context);

	if (eventsMap.has(eventName))
	{
		eventsMap.get(eventName).delete(listener);
	}
}