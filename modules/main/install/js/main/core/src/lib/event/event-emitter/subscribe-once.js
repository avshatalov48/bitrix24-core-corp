import Type from '../../type';
import {privateProps} from './private-stores';

export default function subscribeOnce(context, eventName: any, listener: Function)
{
	if (!Type.isFunction(listener))
	{
		throw new TypeError(`The "listener" argument must be of type Function. Received type ${typeof listener}`);
	}

	const {onceMap} = privateProps.get(context);

	if (onceMap.has(listener))
	{
		return;
	}

	const once = (...args) => {
		context.unsubscribe(eventName, once);
		onceMap.delete(listener);
		listener(...args);
	};

	onceMap.set(listener);
	context.subscribe(eventName, once);
}