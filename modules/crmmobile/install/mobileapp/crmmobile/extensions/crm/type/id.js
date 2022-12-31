/**
 * @module crm/type/id
 */
jn.define('crm/type/id', (require, exports, module) => {

	/**
	 * @class TypeId
	 */
	const TypeId = {
		Lead: 1,
		Deal: 2,
		Contact: 3,
		Company: 4,
		Invoice: 5,
		Activity: 6,
		Quote: 7,
		Requisite: 8,
		DealCategory: 9,
	};

	module.exports = { TypeId };
});
