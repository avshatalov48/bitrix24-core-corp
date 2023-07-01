/**
 * @module crm/type/name
 */
jn.define('crm/type/name', (require, exports, module) => {
	/**
	 * @class TypeName
	 */
	const TypeName = {
		Lead: 'LEAD',
		Deal: 'DEAL',
		Contact: 'CONTACT',
		Company: 'COMPANY',
		Invoice: 'INVOICE',
		Activity: 'ACTIVITY',
		Quote: 'QUOTE',
		Requisite: 'REQUISITE',
		DealCategory: 'DEAL_CATEGORY',
		CustomActivityType: 'CUSTOM_ACTIVITY_TYPE',
		WaitType: 'WAIT',
		CallListType: 'CALL_LIST',
		System: 'SYSTEM',
		DealRecurring: 'DEAL_RECURRING',
		InvoiceRecurring: 'INVOICE_RECURRING',
		Order: 'ORDER',
		OrderCheck: 'ORDER_CHECK',
		CheckCorrection: 'CHECK_CORRECTION',
		OrderShipment: 'ORDER_SHIPMENT',
		OrderPayment: 'ORDER_PAYMENT',
		SmartInvoice: 'SMART_INVOICE',
		SmartDocument: 'SMART_DOCUMENT',
		CommonDynamic: 'DYNAMIC',
		SuspendedLead: 'SUS_LEAD',
		SuspendedDeal: 'SUS_DEAL',
		SuspendedContact: 'SUS_CONTACT',
		SuspendedCompany: 'SUS_COMPANY',
		SuspendedQuote: 'SUS_QUOTE',
		SuspendedInvoice: 'SUS_INVOICE',
		SuspendedOrder: 'SUS_ORDER',
		SuspendedActivity: 'SUS_ACTIVITY',
		SuspendedRequisite: 'SUS_REQUISITE',
		SuspendedSmartInvoice: 'SUS_SMART_INVOICE',
		SuspendedSmartDocument: 'SUS_SMART_DOCUMENT',
		StoreDocument: 'STORE_DOCUMENT',
		ShipmentDocument: 'SHIPMENT_DOCUMENT',
		BankDetail: 'BANK_DETAIL',
		Scoring: 'SCORING',
	};

	/**
	 * @class DynamicTypeName
	 */
	const DynamicTypeName = {
		Name: 'DYNAMIC',
		Prefix: 'DYNAMIC_',
		SuspendedPrefix: 'SUS_DYNAMIC_',
	};

	module.exports = { TypeName, DynamicTypeName };
});
