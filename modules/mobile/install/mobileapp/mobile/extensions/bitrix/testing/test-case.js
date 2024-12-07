/**
 * @module testing/test-case
 */
jn.define('testing/test-case', (require, exports, module) => {
	const { ExpectationFailed } = require('testing/expectation-failed');
	const { TimeoutFailed } = require('testing/timeout-failed');
	const { config } = require('testing/test-config');
	const { expectationCount } = require('testing/expectation-count');

	class TestCase
	{
		#isKilledByTimeout;
		#shutdownTimerId;

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

			this.#isKilledByTimeout = false;
			this.#shutdownTimerId = null;
		}

		/**
		 * @public
		 */
		async execute()
		{
			expectationCount.reset();

			return new Promise((resolve) => {
				try
				{
					const startTime = Date.now();
					this.#startShutDownTimeout(resolve);

					const result = this.callback();

					if (result instanceof Promise)
					{
						result
							.then(() => this.#checkCase(startTime, resolve))
							.catch((error) => {
								this.#reportFail(error);
								resolve();
							})
						;

						return;
					}

					this.#checkCase(startTime, resolve);
				}
				catch (error)
				{
					this.#reportFail(error);

					resolve();
				}
			});
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

		/**
		 * @private
		 */
		#reportSuccess()
		{
			if (this.#isKilledByTimeout)
			{
				return;
			}

			if (this.#shutdownTimerId)
			{
				clearTimeout(this.#shutdownTimerId);

				this.#shutdownTimerId = null;
			}

			this.report.success(`${this.prefix} ${this.title}`);
		}

		/**
		 * @private
		 */
		#reportFail(error)
		{
			if (this.#isKilledByTimeout)
			{
				return;
			}

			if (this.#shutdownTimerId)
			{
				clearTimeout(this.#shutdownTimerId);

				this.#shutdownTimerId = null;
			}

			if (error instanceof ExpectationFailed)
			{
				this.report.fail(`${this.prefix} ${this.title}`, error.expectedValue, error.actualValue);
			}
			else if (error instanceof TimeoutFailed)
			{
				this.report.fail(`${this.prefix} ${this.title}: ${error.message}`, error.expectedValue, error.actualValue);
			}
			else
			{
				throw error;
			}
		}

		#checkCase(startTime, resolvingFn)
		{
			expectationCount.checkExpectations();
			const deltaTime = Date.now() - startTime;

			if (deltaTime < config.timeout)
			{
				this.#reportSuccess();
			}
			else
			{
				this.#reportFail(new TimeoutFailed(deltaTime, config.timeout));
			}

			resolvingFn();
		}

		#startShutDownTimeout(resolvingFn)
		{
			const shotDownTimeout = config.timeout * 1.5;

			this.#shutdownTimerId = setTimeout(
				() => {
					this.#isKilledByTimeout = true;

					this.#reportFail(new TimeoutFailed(`more ${shotDownTimeout}`, config.timeout));
					resolvingFn();
				},
				shotDownTimeout,
			);
		}
	}

	module.exports = {
		TestCase,
	};
});
