import { PresenterDefault } from './presenter-default';
import { PresenterPopupMain } from './presenter-popup-main';
import { PresenterPopup } from './presenter-popup';
import { PresenterSlider } from './presenter-slider';
import { ResponseServiceDataType } from '../types/response-service-data-type';

export class PresenterFactory
{
	static createForWidget(
		serviceCode: String,
		serviceData: ResponseServiceDataType,
		dataProviderPromiseCreator: ?Function = null,
	): PresenterDefault
	{
		if (serviceCode === 'main')
		{
			return new PresenterPopupMain(serviceCode, serviceData, dataProviderPromiseCreator);
		}

		if (serviceData?.advertisingStrategy === 'market')
		{
			return new PresenterSlider(serviceCode, serviceData, dataProviderPromiseCreator);
		}

		return new PresenterPopup(serviceCode, serviceData, dataProviderPromiseCreator);
	}
}
