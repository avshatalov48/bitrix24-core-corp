import { ResponsePurchaseDataType } from './response-purchase-data-type';

export type ResponsePackageDataType = {
	code: string,
	isActive: boolean,
	title: string,
	description: string,
	featurePromotionCode: ?string,
	helperCode: ?string,
	purchaseUrl: ?string,
	icon: {
		className: string,
		color: string,
		style: ?Object,
	},
	price: {
		value: ?string,
		currency: ?string,
		description: string,
	},
	purchaseInfo: {
		purchaseCount: number,
		purchaseBalance: number,
		purchases: ResponsePurchaseDataType[]
	}
};
