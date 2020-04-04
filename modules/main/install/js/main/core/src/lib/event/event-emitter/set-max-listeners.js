import Type from '../../type';
import {privateProps} from './private-stores';

export default function setMaxListener(context, count)
{
	if (!Type.isNumber(count) || count < 0)
	{
		throw new TypeError(`The value of "count" is out of range. It must be a non-negative number. Received ${count}`);
	}

	// eslint-disable-next-line
	privateProps.get(context).maxListeners = count;
}