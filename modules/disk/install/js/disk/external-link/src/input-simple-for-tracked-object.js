import InputSimple from './input-simple';
import InputExtendedForTrackedObject from "./input-extended-for-tracked-object";
import {BackendForTrackedObject} from './backend';

export default class InputSimpleForTrackedObject extends InputSimple
{
	constructor(objectId, data)
	{
		super(objectId, data);
	}

	getBackend()
	{
		return BackendForTrackedObject;
	}

	static getExtendedInputClass()
	{
		return InputExtendedForTrackedObject
	}
}
