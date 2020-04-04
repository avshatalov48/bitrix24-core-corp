import {privateProps} from './private-stores';

export default function getMaxListeners(context)
{
	return privateProps.get(context).maxListeners;
}