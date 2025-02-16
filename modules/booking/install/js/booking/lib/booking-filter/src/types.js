export type BookingUIFilter = {
	CREATED_BY?: string[],
	CONTACT?: string[],
	COMPANY?: string[],
	RESOURCE?: string[],
	CONFIRMED?: 'Y' | 'N',
	DELAYED?: 'Y' | 'N',
};

export type BookingListFilter = {
	WITHIN: {
		DATE_FROM: number,
		DATE_TO: number,
	},
	CREATED_BY?: number[],
	CRM_CONTACT_ID?: number[],
	CRM_COMPANY_ID?: number[],
	RESOURCE_ID?: number[],
	IS_CONFIRMED?: boolean,
	IS_DELAYED?: boolean,
	ID?: number[],
};
