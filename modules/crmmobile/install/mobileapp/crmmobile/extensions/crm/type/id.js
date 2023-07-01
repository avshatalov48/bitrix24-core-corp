/**
 * @module crm/type/id
 */
jn.define('crm/type/id', (require, exports, module) => {
	/**
	 * @class TypeId
	 */
	const TypeId = {
		Undefined: 0,
		Lead: 1,
		Deal: 2,
		Contact: 3,
		Company: 4,
		Invoice: 5,
		Activity: 6,
		Quote: 7,
		Requisite: 8,
		DealCategory: 9,
		CustomActivityType: 10,
		Wait: 11,
		CallList: 12,
		DealRecurring: 13,
		Order: 14,
		OrderCheck: 15,
		OrderShipment: 16,
		OrderPayment: 17,
		SuspendedLead: 18,
		SuspendedDeal: 19,
		SuspendedContact: 20,
		SuspendedCompany: 21,
		SuspendedQuote: 22,
		SuspendedInvoice: 23,
		SuspendedOrder: 24,
		SuspendedActivity: 25,
		SuspendedRequisite: 26,
		InvoiceRecurring: 27,
		Scoring: 28,
		CheckCorrection: 29,
		DeliveryRequest: 30,
		SmartInvoice: 31,
		SuspendedSmartInvoice: 32,
		StoreDocument: 33,
		ShipmentDocument: 34,
		BankDetail: 35,
		SmartDocument: 36,
		SuspendedSmartDocument: 37,
	};

	/**
	 * @class DynamicTypeId
	 */
	const DynamicTypeId = {
		Start: 128,
		End: 192,
		SuspendedStart: 192,
		SuspendedEnd: 256,
	};

	module.exports = { TypeId, DynamicTypeId };
});
