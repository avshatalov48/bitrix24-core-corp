import BaseEvent from '../base-event';
import {privateProps} from './private-stores';

export default function emit(context, eventName: any, event: BaseEvent)
{
	const {eventsMap} = privateProps.get(context);

	if (eventsMap.has(eventName))
	{
		const listeners = eventsMap.get(eventName);
		return [...listeners.values()].map((listener) => {
			return listener(event);
		});
	}

	return [];
}