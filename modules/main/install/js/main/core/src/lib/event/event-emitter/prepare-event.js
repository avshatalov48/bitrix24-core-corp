import BaseEvent from '../base-event';
import Type from '../../type';

export default function prepareEvent(context, eventName: any, event?: BaseEvent, target)
{
	let preparedEvent = event;

	if (!(event instanceof BaseEvent))
	{
		preparedEvent = new BaseEvent();
	}

	if (!Type.isNil(event))
	{
		if (Type.isPlainObject(event))
		{
			preparedEvent.data = {...preparedEvent.data, ...event};
		}
		else
		{
			preparedEvent.data.value = event;
		}
	}

	preparedEvent.type = eventName;
	preparedEvent.isTrusted = !Type.isFunction(target);

	return Object.seal(preparedEvent);
}