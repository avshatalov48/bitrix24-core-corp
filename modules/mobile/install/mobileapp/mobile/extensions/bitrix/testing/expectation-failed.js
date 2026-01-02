/**
 * @module testing/expectation-failed
 */
jn.define('testing/expectation-failed', (require, exports, module) => {

	class ExpectationFailed
	{
		constructor(actualValue, expectedValue, trace)
		{
			this.actualValue = actualValue;
			this.expectedValue = expectedValue;
			this.trace = trace;
		}
	}

	module.exports = {
		ExpectationFailed,
	};

});