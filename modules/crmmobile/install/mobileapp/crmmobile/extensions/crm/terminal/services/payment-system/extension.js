/**
 * @module crm/terminal/services/payment-system
 */
jn.define('crm/terminal/services/payment-system', (require, exports, module) => {
	/**
	 * @class PaymentSystemService
	 */
	class PaymentSystemService
	{
		/**
		 * @param {TerminalCreatePaymentSystemProps} props
		 * @returns {Promise<number>}
		 */
		create(props)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'sale.paysystem.entity.addPaySystem',
					{
						data: {
							fields: {
								actionFile: props.handler,
								psMode: props.type,
							},
						},
					},
				).then((response) => {
					if (response.data.id)
					{
						resolve(response.data.id);

						return;
					}
					reject();
				}).catch(() => reject());
			});
		}
	}

	module.exports = {
		PaymentSystemService,
	};
});
