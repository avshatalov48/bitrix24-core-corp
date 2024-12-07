/**
 * @module testing/timeout-failed
 */
jn.define('testing/timeout-failed', (require, exports, module) => {

	/**
	 * @class TimeoutFailed
	 */
	class TimeoutFailed
	{
		constructor(delta, failedTimeout)
		{
			this.message = 'timeout failed';
			this.expectedValue = `${failedTimeout} ms`;
			this.actualValue = `${delta} ms`;
		}
	}

	module.exports = { TimeoutFailed };
});
