/**
 * @module testing
 */
jn.define('testing', (require, exports, module) => {
	const { TestSuite } = require('testing/test-suite');
	const { TestCase } = require('testing/test-case');
	const { TestingExpectation } = require('testing/expectation');
	const { TestingReport } = require('testing/report');
	const { config } = require('testing/test-config');
	const { ConsolePrinter, JnLayoutPrinter } = require('testing/printers');
	const { expectationCount } = require('testing/expectation-count');

	/** @type {TestSuite[]} */
	const testSuites = [];

	const report = new TestingReport();

	/** @type {function():TestSuite} */
	const currentSuite = () => {
		if (testSuites.length === 0)
		{
			testSuites.push(new TestSuite('Default test suite', report));
		}

		return testSuites[testSuites.length - 1];
	};

	/**
	 * @param {string} title
	 * @param {function} setupFn
	 * @return {TestSuite}
	 */
	const describe = (title, setupFn) => {
		const suite = new TestSuite(title, report);
		testSuites.push(suite);

		setupFn();

		return suite;
	};

	/**
	 * @param {string} title
	 * @param {function} callback
	 * @return {TestCase}
	 */
	const it = (title, callback) => {
		const testCase = new TestCase(title, callback, 'it', report);
		currentSuite().testCases.push(testCase);

		return testCase;
	};

	/**
	 * @param {string} title
	 * @param {function} callback
	 * @return {TestCase}
	 */
	const test = (title, callback) => {
		const testCase = new TestCase(title, callback, 'test', report);
		currentSuite().testCases.push(testCase);

		return testCase;
	};

	const expect = (value) => new TestingExpectation(value);

	expect.assertions = (requiredAssertionsCount) => {
		expectationCount.setRequiredCount(requiredAssertionsCount);
	};

	expect.hasAssertions = () => {
		expectationCount.setRequiredCount(1);
	};

	const beforeEach = (fn) => currentSuite().beforeEach.push(fn);

	const afterEach = (fn) => currentSuite().afterEach.push(fn);

	const beforeAll = (fn) => currentSuite().beforeAll.push(fn);

	const afterAll = (fn) => currentSuite().afterAll.push(fn);

	module.exports = {
		describe,
		it,
		test,
		expect,
		beforeEach,
		afterEach,
		beforeAll,
		afterAll,
		report,
		testSuites,
		ConsolePrinter,
		JnLayoutPrinter,
		config,
	};
});
