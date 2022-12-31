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
	};

	module.exports = { TypeName };
});
