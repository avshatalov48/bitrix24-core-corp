/**
 * @module testing/expectation
 */
jn.define('testing/expectation', (require, exports, module) => {

	const { ExpectationFailed } = require('testing/expectation-failed');
	const {
		TestingMatcher,
		ExactMatcher,
		EqualityMatcher,
		ExceptionMatcher,
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
		}

		get not()
		{
			this.reversed = true;
			return this;
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

	module.exports = {
		TestingExpectation,
	};

});