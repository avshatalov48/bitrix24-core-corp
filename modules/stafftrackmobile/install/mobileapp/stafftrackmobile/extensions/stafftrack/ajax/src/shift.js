/**
 * @module stafftrack/ajax/shift
 */
jn.define('stafftrack/ajax/shift', (require, exports, module) => {
	const { BaseAjax } = require('stafftrack/ajax/base');

	const ShiftActions = {
		ADD: 'add',
		UPDATE: 'update',
		DELETE: 'delete',
		LIST: 'list',
		LOAD_MAIN: 'loadMain',
		GET_GEO_INFO: 'getGeoInfo',
		MUTE_COUNTER: 'muteCounter',
	};

	class ShiftAjax extends BaseAjax
	{
		/**
		 * @returns {string}
		 */
		getEndpoint()
		{
			return 'stafftrackmobile.Shift';
		}

		/**
		 * @param date
		 * @returns {Promise<Object, void>}
		 */
		loadMain(date)
		{
			return this.fetch(ShiftActions.LOAD_MAIN, { date });
		}

		/**
		 * @param filter {object}
		 * @param select {object}
		 * @param order {object}
		 * @param limit {object}
		 * @returns {Promise<Object, void>}
		 */
		list(filter, select = {}, order = {}, limit = 0)
		{
			return this.fetch(ShiftActions.LIST, {
				filter,
				select,
				order,
				limit,
			});
		}

		/**
		 * @param fields {ShiftDto}
		 * @returns {Promise<Object, void>}
		 */
		add(fields)
		{
			return this.fetch(ShiftActions.ADD, { fields });
		}

		/**
		 * @param id {number}
		 * @param fields {ShiftDto}
		 * @returns {Promise<Object, void>}
		 */
		update(id, fields)
		{
			return this.fetch(ShiftActions.UPDATE, { id, fields });
		}

		/**
		 * @param id {number}
		 * @returns {Promise<Object, void>}
		 */
		delete(id)
		{
			return this.fetch(ShiftActions.DELETE, { id });
		}

		/**
		 *
		 * @param latitude
		 * @param longitude
		 * @returns {Promise<Object, void>}
		 */
		getGeoInfo({ latitude, longitude })
		{
			return this.fetch(ShiftActions.GET_GEO_INFO, { latitude, longitude });
		}

		/**
		 *
		 * @param muteStatus
		 * @returns {Promise<Object, void>}
		 */
		muteCounter(muteStatus)
		{
			return this.fetch(ShiftActions.MUTE_COUNTER, { muteStatus });
		}
	}

	module.exports = {
		ShiftActions,
		ShiftAjax: new ShiftAjax(),
	};
});
