/* eslint-disable no-await-in-loop */
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
		async execute()
		{
			this.report.group(this.title);

			void await this.#executeArrayFunctions(this.beforeAll);

			const only = this.testCases.filter((testCase) => testCase.$only);
			const executables = (only.length > 0 ? only : this.testCases)
				.filter((testCase) => !testCase.$skip)
			;

			for (const testCase of executables)
			{
				void await this.#executeArrayFunctions(this.beforeEach);

				void await testCase.execute();

				void await this.#executeArrayFunctions(this.afterEach);
			}

			void await this.#executeArrayFunctions(this.afterAll);

			this.report.groupEnd();
		}

		/**
		 *
		 * @param {Array<Function>} arrayFn
		 * @return {Promise<void>}
		 */
		async #executeArrayFunctions(arrayFn)
		{
			for (const fn of arrayFn)
			{
				void await fn();
			}
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
