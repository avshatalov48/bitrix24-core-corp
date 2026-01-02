import { ResponseServiceDataType } from '../types/response-service-data-type';
import { Popup } from 'main.popup';

export class PresenterDefault
{
	serviceCode: String;
	serviceData: ?ResponseServiceDataType = null;
	dataProviderPromiseCreator: ?Function = null;

	constructor(
		serviceCode: string,
		serviceData: ResponseServiceDataType,
		dataProviderPromiseCreator: ?Function = null,
	)
	{
		this.serviceCode = serviceCode;
		this.serviceData = { ...serviceData };
		this.dataProviderPromiseCreator = dataProviderPromiseCreator;
	}

	adjustServiceData(serviceData: ResponseServiceDataType): void
	{}

	getPopup(): ?Popup
	{
		return new Popup();
	}

	toggle(node: ?HTMLElement): void
	{}

	show(node: ?HTMLElement): void
	{}

	hide(): void
	{}
}
