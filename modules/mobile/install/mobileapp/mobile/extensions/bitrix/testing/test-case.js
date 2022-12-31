/**
 * @module testing/test-case
 */
jn.define('testing/test-case', (require, exports, module) => {

	const { ExpectationFailed } = require('testing/expectation-failed');

	class TestCase
	{
		constructor(title, callback, prefix, report)
		{
			/** @type {string} */
			this.title = title;

			/** @type {function} */
			this.callback = callback;

			/** @type {string} */
			this.prefix = prefix || 'test';

			/** @type {TestingReport} */
			this.report = report;

			/**
			 * @public
			 * @type {boolean}
			 */
			this.$only = false;

			/**
			 * @public
			 * @type {boolean}
			 */
			this.$skip = false;
		}

		/**
		 * @public
		 */
		execute()
		{
			try
			{
				this.callback();
				this.report.success(`${this.prefix} ${this.title}`);
			}
			catch (error)
			{
				if (error instanceof ExpectationFailed)
				{
					this.report.fail(`${this.prefix} ${this.title}`, error.expectedValue, error.actualValue);
				}
				else
				{
					throw error;
				}
			}
		}

		/**
		 * @public
		 * @return {TestCase}
		 */
		only()
		{
			this.$only = true;
			return this;
		}

		/**
		 * @public
		 * @return {TestCase}
		 */
		skip()
		{
			this.$skip = true;
			return this;
		}
	}

	module.exports = {
		TestCase,
	};

});