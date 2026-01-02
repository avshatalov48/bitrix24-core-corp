import InputExtended from './input-extended';
import { BackendForUnifiedLink } from './backend';

export default class InputExtendedForUnifiedLink extends InputExtended
{
	constructor(objectId, data)
	{
		super(objectId, data);
		this.objectId = objectId; // override for unified link, since here objectId is unique code represented as a string
	}

	getBackend(): BackendForUnifiedLink
	{
		return BackendForUnifiedLink;
	}
}
