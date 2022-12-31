/**
 * @module crm/entity-tab/sort
 */
jn.define('crm/entity-tab/sort', (require, exports, module) => {

	/**
	 * @class TypeSort
	 */
	const TypeSort = {
		Id: 'BY_ID',
		LastActivityTime: 'BY_LAST_ACTIVITY_TIME',
	};

	module.exports = { TypeSort };
});
