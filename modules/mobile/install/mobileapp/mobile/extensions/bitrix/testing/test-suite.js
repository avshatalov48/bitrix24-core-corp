/**
 * @module testing/test-suite
 */
jn.define('testing/test-suite', (require, exports, module) => {

	class TestSuite
	{
		constructor(title, report)
		{
			/** @type {string} */
			this.title = title;

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

			/**
			 * @public
			 * @type {TestCase[]}
			 */
			this.testCases = [];

			/**
			 * @public
			 * @type {function[]}
			 */
			this.beforeAll = [];

			/**
			 * @public
			 * @type {function[]}
			 */
			this.afterAll = [];

			/**
			 * @public
			 * @type {function[]}
			 */
			this.beforeEach = [];

			/**
			 * @public
			 * @type {function[]}
			 */
			this.afterEach = [];
		}

		/**
		 * @public
		 */
		execute()
		{
			this.report.group(this.title);

			this.beforeAll.map(fn => fn.call());

			const only = this.testCases.filter(testCase => testCase.$only);
			const executables = only.length ? only : this.testCases;

			executables.filter(testCase => !testCase.$skip).map(testCase => {
				this.beforeEach.map(fn => fn.call());

				testCase.execute();

				this.afterEach.map(fn => fn.call());
			});

			this.afterAll.map(fn => fn.call());

			this.report.groupEnd();
		}

		/**
		 * @public
		 * @return {TestSuite}
		 */
		only()
		{
			this.$only = true;
			return this;
		}

		/**
		 * @public
		 * @return {TestSuite}
		 */
		skip()
		{
			this.$skip = true;
			return this;
		}
	}

	module.exports = {
		TestSuite,
	};

});