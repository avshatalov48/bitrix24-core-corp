import InputExtended from "./input-extended";
import {BackendForTrackedObject} from './backend';

export default class InputExtendedForTrackedObject extends InputExtended
{
	constructor(objectId, data)
	{
		console.log('InputExtendedForTrackedObject!!!');
		super(objectId, data);
	}

	getBackend()
	{
		return BackendForTrackedObject;
	}
}
