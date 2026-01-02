import { ResponseServiceDataType } from '../types/response-service-data-type';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { PresenterDefault } from './presenter-default';

export class PresenterSlider extends PresenterDefault
{
	constructor(
		serviceCode: string,
		serviceData: ResponseServiceDataType,
		dataProviderPromiseCreator: ?Function = null,
	)
	{
		super(serviceCode, serviceData, dataProviderPromiseCreator);
	}

	hide(): void
	{
		FeaturePromotersRegistry.getPromoter({ code: this.serviceData.featurePromotionCode }).hide();
	}

	show(node: ?HTMLElement): void
	{
		FeaturePromotersRegistry.getPromoter({ code: this.serviceData.featurePromotionCode }).show();
	}
}
