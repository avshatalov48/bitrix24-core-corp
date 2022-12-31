/**
 * @module testing/expectation-failed
 */
jn.define('testing/expectation-failed', (require, exports, module) => {

	class ExpectationFailed
	{
		constructor(actualValue, expectedValue)
		{
			this.actualValue = actualValue;
			this.expectedValue = expectedValue;
		}
	}

	module.exports = {
		ExpectationFailed,
	};

});