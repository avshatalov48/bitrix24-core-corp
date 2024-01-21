/**
 * @module crm/terminal/services/finds-client
 */
jn.define('crm/terminal/services/finds-client', (require, exports, module) => {
	/**
	 * @class FindsClientService
	 */
	class FindsClientService
	{
		/**
		 * @param {String} phoneNumber
		 * @returns {Promise<Object[]>}
		 */
		findClient(phoneNumber)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'crmmobile.Terminal.App.findClient',
					{
						data: {
							phoneNumber,
						},
					},
				)
					.then((response) => {
						const duplicates = BX.prop.getArray(response, 'data', []);

						if (duplicates.length > 0)
						{
							resolve(duplicates);

							return;
						}

						reject();
					})
					.catch(() => reject());
			});
		}
	}

	module.exports = {
		FindsClientService,
	};
});
