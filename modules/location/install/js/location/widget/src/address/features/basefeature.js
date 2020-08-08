import {Address, MethodNotImplemented} from "location.core";

/**
 * Base class for the address widget feature
 */
export default class BaseFeature
{
	render(props)
	{
		throw new MethodNotImplemented('Method render must be implemented');
	}

	setAddressWidget(addressWidget): void
	{
		throw new MethodNotImplemented('Method render must be implemented');
	}

	setAddress(address: Address): void
	{
		throw new MethodNotImplemented('Method set address must be implemented');
	}

	setMode(mode: string): void
	{

	}

	destroy(): void
	{

	}

	resetView(): void
	{

	}
}