/**
 * @module crm/terminal/services/payment
 */
jn.define('crm/terminal/services/payment', (require, exports, module) => {
	/**
	 * @class PaymentService
	 */
	class PaymentService
	{
		/**
		 * @param {TerminalCreatePaymentProps} props
		 * @returns {Promise<TerminalPayment>}
		 */
		create(props)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'crmmobile.Terminal.createPayment',
					{
						data: {
							sum: props.sum,
							currency: props.currency,
							phoneNumber: props.phoneNumber,
							client: props.client,
							clientName: props.clientName,
						},
					},
				)
					.then((response) => {
						if (response.data.payment)
						{
							resolve(response.data.payment);
							return;
						}
						reject();
					}).catch(() => {
						reject();
					});
			});
		}

		/**
		 * @param {number} id
		 * @returns {Promise<TerminalPayment>}
		 */
		get(id)
		{
			return new Promise((resolve) => {
				BX.ajax.runAction('crmmobile.Terminal.getPayment', { data: { id } })
					.then((response) => resolve(response.data))
					.catch(() => reject());
			});
		}

		/**
		 * @param {number} id
		 * @returns {Promise}
		 */
		delete(id)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'crm.order.terminalpayment.delete',
					{
						data: { id },
					},
				)
					.then(() => resolve())
					.catch(() => reject());
			});
		}

		/**
		 * @param {TerminalInitiatesPaymentProps} props
		 * @returns {Promise<string>}
		 */
		initiate(props)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'crmmobile.Terminal.InitiatePay',
					{
						data: {
							paymentId: props.paymentId,
							paySystemId: props.paymentSystemId,
							accessCode: props.accessCode,
						},
					},
				)
					.then((response) => {
						if (response.data.qr)
						{
							resolve(response.data.qr);
							return;
						}
						reject(response.errors || []);
					}).catch((response) => {
						reject(response.errors || []);
					});
			});
		}

		/**
		 * @param {number} paymentId
		 * @returns {Promise<string>}
		 */
		getLink(paymentId)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'salescenter.payment.getPublicUrl',
					{
						data: {
							id: paymentId,
							options: {
								qr: {
									w: 380,
									h: 380,
									p: 0,
									wq: 0,
								},
							},
						},
					},
				)
					.then((response) => {
						const payment = response.data.payment || {};
						if (payment.qr)
						{
							resolve(payment.qr);
							return;
						}
						reject();
					}).catch(() => {
						reject();
					});
			});
		}
	}

	module.exports = {
		PaymentService,
	};
});
