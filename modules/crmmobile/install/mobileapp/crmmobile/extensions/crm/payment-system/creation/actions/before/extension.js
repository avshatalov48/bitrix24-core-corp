/**
 * @module crm/payment-system/creation/actions/before
 */
jn.define('crm/payment-system/creation/actions/before', (require, exports, module) => {
	/**
	 * @class Before
	 */
	class Before
	{
		/**
		 * @param data
		 * @returns {Promise<void>}
		 */
		run(data)
		{
			if (!data || data.done === true)
			{
				return Promise.resolve();
			}

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(data.action)
					.then(() => {
						data.done = true;
						resolve();
					})
					.catch(() => {
						reject({
							errors: [
								{
									message: data.error,
								},
							],
						});
					});
			});
		}
	}

	module.exports = { Before };
});
