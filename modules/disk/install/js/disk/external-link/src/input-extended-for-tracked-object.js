import InputExtended from './input-extended';
import { BackendForTrackedObject } from './backend';

export default class InputExtendedForTrackedObject extends InputExtended
{
	getBackend(): BackendForTrackedObject
	{
		return BackendForTrackedObject;
	}
}
