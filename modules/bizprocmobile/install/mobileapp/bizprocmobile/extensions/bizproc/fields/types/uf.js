/**
 * @module bizproc/fields/types/uf
 */
jn.define('bizproc/fields/types/uf', (require, exports, module) => {

	const UfType = {
		IBLOCK_ELEMENT: 'UF:iblock_element',
		IBLOCK_SECTION: 'UF:iblock_section',
		CRM_STATUS: 'UF:crm_status',
		CRM: 'UF:crm',
		// RESOURCEBOOKING: 'UF:resourcebooking',
		MONEY: 'UF:money',
		ADDRESS: 'UF:address',
		URL: 'UF:url',
	};

	module.exports = { UfType };
});
