export type ServiceInPurchasedPackageType = {
	code: string,
	current: number,
	maximal: number
};

export type ResponsePurchasedPackageDataType = {
	code: string,
	purchaseCode: string,
	startDate: Date,
	expirationDate: Date,
	actual: string,
	services: Array<ServiceInPurchasedPackageType>,
};
