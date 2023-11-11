/**
 * @module catalog/store/document-type
 */
jn.define('catalog/store/document-type', (require, exports, module) => {
	/*
	 * @class DocumentType
	 */
	const DocumentType = {
		Arrival: 'A',
		StoreAdjustment: 'S',
		Deduct: 'D',
		Moving: 'M',
		SalesOrders: 'W',
	};

	module.exports = { DocumentType };
});
