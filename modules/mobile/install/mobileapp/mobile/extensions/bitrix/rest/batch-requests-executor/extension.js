/**
 * @module rest/batch-requests-executor
 */
jn.define('rest/batch-requests-executor', (require, exports, module) => {
	class BatchRequestsExecutor
	{
		constructor(props = {})
		{
			const defaults = {
				continueOnError: false,
				notifyOnNetworkError: true,
				networkErrorMessage: BX.message('MOBILE_REST_API_NETWORK_ERROR'),
				time: 3
			};
			this.props = {...defaults, ...props};
		}

		execute(calls)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callBatch(calls, (result) => {
					let alreadyNotified = false;
					for (const [key, value] of Object.entries(result))
					{
						if (
							value.answer !== undefined
							&& value.answer.error !== undefined
							&& value.answer.error.error !== undefined
						)
						{
							if (
								value.answer.error.error === 'ERROR_NETWORK'
								&& this.props.notifyOnNetworkError
								&& alreadyNotified === false
							)
							{
								Notify.showMessage(
									this.props.networkErrorMessage,
									'',
									this.props
								);
								alreadyNotified = true;
							}

							console.log('rest api error:', result);
							if (this.props.continueOnError === false)
							{
								reject(value.answer.error);
								return;
							}
						}
					}
					resolve(result);
				});
			});
		}
	}

	module.exports = { BatchRequestsExecutor };
});
