/**
 * @module testing/expectation-count
 */
jn.define('testing/expectation-count', (require, exports, module) => {
	const { ExpectationFailed } = require('testing/expectation-failed');

	/**
	 * @class ExpectationCount
	 */
	class ExpectationCount
	{
		constructor()
		{
			this.reset();
		}

		setRequiredCount(expectationCount)
		{
			this.requiredExpectationCount = expectationCount;
			this.enabled = true;
		}

		reset()
		{
			this.requiredExpectationCount = 0;
			this.completedExpectionCount = 0;
			this.enabled = false;
		}

		checkExpectations()
		{
			if (!this.enabled)
			{
				return true;
			}

			if (this.completedExpectionCount >= this.requiredExpectationCount)
			{
				return true;
			}

			throw new ExpectationFailed(`completed expectation count: ${this.completedExpectionCount}`, `need at least: ${this.requiredExpectationCount}`);
		}

		confirmExpectation()
		{
			this.completedExpectionCount++;
		}
	}

	module.exports = { expectationCount: new ExpectationCount() };
});
