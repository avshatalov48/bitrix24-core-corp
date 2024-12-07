/**
 * @module testing/expectation
 */
jn.define('testing/expectation', (require, exports, module) => {
	const { ExpectationFailed } = require('testing/expectation-failed');
	const { expectationCount } = require('testing/expectation-count');
	const {
		TestingMatcher,
		ExactMatcher,
		EqualityMatcher,
		ExceptionMatcher,
		AsyncExceptionMatcher,
		DefinedMatcher,
		NullMatcher,
		BooleanMatcher,
		MatchObjectMatcher,
	} = require('testing/matchers');

	class TestingExpectation
	{
		constructor(value)
		{
			this.actualValue = value;
			this.reversed = false;
			this.async = false;
		}

		get not()
		{
			this.reversed = true;

			return this;
		}

		get resolves()
		{
			return new AsyncTestingExpectation({
				value: this.actualValue,
				toBeResolved: true,
			});
		}

		get rejects()
		{
			return new AsyncTestingExpectation({
				value: this.actualValue,
				toBeRejected: true,
			});
		}

		/**
		 * @param {TestingMatcher} matcher
		 */
		apply(matcher)
		{
			let result = matcher.match();

			if (this.reversed)
			{
				result = !result;
			}

			if (!result)
			{
				throw new ExpectationFailed(matcher.actualValue, matcher.expectedValue);
			}

			expectationCount.confirmExpectation();
		}

		toBe(expectedValue)
		{
			return this.apply(new ExactMatcher(this.actualValue, expectedValue));
		}

		toEqual(expectedValue)
		{
			return this.apply(new EqualityMatcher(this.actualValue, expectedValue));
		}

		toThrow(expectedException)
		{
			return this.apply(new ExceptionMatcher(this.actualValue, expectedException));
		}

		toBeDefined()
		{
			return this.apply(new DefinedMatcher(this.actualValue));
		}

		toBeNull()
		{
			return this.apply(new NullMatcher(this.actualValue, null));
		}

		toBeTrue()
		{
			return this.apply(new BooleanMatcher(this.actualValue, true));
		}

		toBeFalse()
		{
			return this.apply(new BooleanMatcher(this.actualValue, false));
		}

		toMatchObject(expectedValue)
		{
			return this.apply(new MatchObjectMatcher(this.actualValue, expectedValue));
		}
	}

	/**
	 * @class AsyncTestingExpectation
	 */
	class AsyncTestingExpectation extends TestingExpectation
	{
		constructor({ value, toBeRejected = false, toBeResolved = false })
		{
			super(value);
			this.toBeRejected = toBeRejected;
			this.toBeResolved = toBeResolved;
			this.rejected = false;
			this.resolved = false;
		}

		async toBe(expectedValue)
		{
			await this.#awaitActualValue();

			return super.toBe(expectedValue);
		}

		async toEqual(expectedValue)
		{
			await this.#awaitActualValue();

			return super.toEqual(expectedValue);
		}

		async toThrow(expectedException)
		{
			await this.#awaitActualValue();
			if (!this.rejected)
			{
				throw new ExpectationFailed('the function or promise didn\'t throw anything', 'the function or promise will throw an error');
			}

			return this.apply(new AsyncExceptionMatcher(this.actualValue, expectedException));
		}

		async toBeDefined()
		{
			await this.#awaitActualValue();

			return super.toBeDefined();
		}

		async toBeNull()
		{
			await this.#awaitActualValue();

			return super.toBeNull();
		}

		async toBeTrue()
		{
			await this.#awaitActualValue();

			return super.toBeTrue();
		}

		async toBeFalse()
		{
			await this.#awaitActualValue();

			return super.toBeFalse();
		}

		async toMatchObject(expectedValue)
		{
			await this.#awaitActualValue();

			return super.toMatchObject(expectedValue);
		}

		async #awaitActualValue()
		{
			try
			{
				let result = null;
				if (typeof this.actualValue === 'function')
				{
					result = await this.actualValue();
				}
				else
				{
					result = await this.actualValue;
				}
				this.resolved = true;

				if (this.toBeRejected)
				{
					throw new ExpectationFailed('the function or promise was resolved', 'the function or promise to be rejected');
				}

				this.actualValue = result;
			}
			catch (error)
			{
				this.rejected = true;

				if (error instanceof ExpectationFailed)
				{
					throw error;
				}

				if (this.toBeResolved)
				{
					throw new ExpectationFailed('the function or promise was rejected', 'the function or promise to be resolved');
				}

				this.actualValue = error;
			}
		}
	}

	module.exports = {
		TestingExpectation,
	};
});
