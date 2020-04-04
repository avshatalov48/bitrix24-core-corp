import {privateProps} from './private-stores';

export default function getListeners(context, eventName: any): Function[] | null
{
	return privateProps.get(context).eventsMap.get(eventName);
}