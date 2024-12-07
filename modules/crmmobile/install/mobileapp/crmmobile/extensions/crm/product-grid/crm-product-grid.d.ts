type CrmProductGridProps = {
	tabId: string,
	uid: string,
	summary: {},
	measures: CrmProductGridMeasure[],
	catalog: {
		id: number,
		basePriceId: number,
		currencyId: string,
	},
	entity: {
		id: number,
		typeId: number,
		typeName: string,
		categoryId: string,
		editable: boolean,
		currencyId: string,
		detailPageUrl: string,
	},
	products: [],
	inventoryControl: {
		isAllowedReservation: boolean,
		mode: string,
		isReservationRestrictedByPlan: boolean,
		defaultDateReserveEnd: number,
		isCatalogHidden: boolean,
	},
	taxes: {
		vatRates: CrmProductGridVatRate[],
		productRowTaxUniform: boolean,
	},
	permissions: CrmProductGridCatalogPermissions,
};

type CrmProductGridCatalogPermissions = {
	catalog_read: boolean,
	catalog_price: boolean,
	catalog_product_add: boolean,
	catalog_product_edit: boolean,
	catalog_purchas_info: boolean,
	catalog_entity_price: number[],
	catalog_discount: number[],
};

type CrmProductGridState = {
	summary: {},
	products: [],
	currencyId: string,
};

type CrmProductGridVatRate = {
	id: number,
	name: string,
	value: number,
};

type CrmProductGridMeasure = {
	id: number,
	code: string,
	name: string,
	isDefault: boolean;
};
