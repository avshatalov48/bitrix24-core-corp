import type { ResponseAdsInfoDataType } from './response-ads-info-data-type';
import type { ResponsePackageDataType } from './response-package-data-type';
import type { ResponseServiceDataType } from './response-service-data-type';

export type ResponseDataType = {
	service: ?ResponseServiceDataType,
	adsInfo: ResponseAdsInfoDataType,
	packages: ResponsePackageDataType[],
};
