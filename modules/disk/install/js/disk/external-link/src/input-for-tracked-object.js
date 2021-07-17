import Input from './input';
import {BackendForTrackedObject} from './backend';

export default class InputForTrackedObject extends Input
{
	constructor(objectId, data, params)
	{
		super(objectId, data, params);
		this.backendClass = BackendForTrackedObject;
	}
}
