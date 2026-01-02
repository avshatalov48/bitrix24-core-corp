export type ActivePackInfo = {
	current: number;
	maximal: number;
	expirationDateObject: Date;
	expirationDate: string;
};

export type NextPackInfo = {
	current: number;
	maximal: number;
	startDateObject: Date;
	startDate: string;
};

export type ActiveAndFuturePurchasesResult = {
	expirationDate: string;
	active?: ActivePackInfo;
	next?: NextPackInfo;
};
