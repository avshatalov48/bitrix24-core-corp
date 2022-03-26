(() => {

	/**
	 * @class Testing
	 */
	class Testing
	{
		static describe()
		{
			return (title, setupFn) => {
				const suite = new TestSuite(title);
				Testing.suites.push(suite);
				Testing.currentSuite = suite;

				setupFn();

				Testing.currentSuite.execute();
			}
		}

		static it()
		{
			return (title, callback) => {
				const testCase = new TestCase(title, callback, 'it');
				Testing.currentSuite.testCases.push(testCase);
			}
		}

		static test()
		{
			return (title, callback) => {
				const testCase = new TestCase(title, callback, 'test');
				Testing.currentSuite.testCases.push(testCase);
			}
		}

		static expect()
		{
			return (value) => new TestingExpectation(value);
		}

		static beforeEach()
		{
			return (fn) => Testing.currentSuite.beforeEach.push(fn);
		}

		static afterEach()
		{
			return (fn) => Testing.currentSuite.afterEach.push(fn);
		}

		static beforeAll()
		{
			return (fn) => Testing.currentSuite.beforeAll.push(fn);
		}

		static afterAll()
		{
			return (fn) => Testing.currentSuite.afterAll.push(fn);
		}

		static helpers()
		{
			return {
				describe: Testing.describe(),
				it: Testing.it(),
				test: Testing.test(),
				expect: Testing.expect(),
				beforeEach: Testing.beforeEach(),
				afterEach: Testing.afterEach(),
				beforeAll: Testing.beforeAll(),
				afterAll: Testing.afterAll(),
			}
		}
	}

	Testing.suites = [];

	/**
	 * @type {TestSuite|null}
	 */
	Testing.currentSuite = null;

	class TestSuite
	{
		constructor(title)
		{
			this.title = title;

			this.testCases = [];
			this.beforeAll = [];
			this.afterAll = [];
			this.beforeEach = [];
			this.afterEach = [];
		}

		execute()
		{
			console.group(this.title);

			this.beforeAll.map(fn => fn.call());

			this.testCases.map(testCase => {
				this.beforeEach.map(fn => fn.call());

				testCase.execute();

				this.afterEach.map(fn => fn.call());
			});

			this.afterAll.map(fn => fn.call());

			console.groupEnd();
		}
	}

	/**
	 * @class TestCase
	 */
	class TestCase
	{
		constructor(title, callback, prefix)
		{
			this.title = title;
			this.callback = callback;
			this.prefix = prefix || 'test';
		}

		execute()
		{
			try
			{
				this.callback();
				console.log(`✅ [ok] ${this.prefix} ${this.title}`);
			}
			catch (error)
			{
				if (error instanceof ExpectationFailed)
				{
					console.group(`🛑 [fail] ${this.prefix} ${this.title}`);
					console.log('expected: ', error.expectedValue);
					console.log('actual: ', error.actualValue);
					console.groupEnd();
				}
				else
				{
					throw error;
				}
			}
		}
	}

	/**
	 * @class TestingExpectation
	 */
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
	}

	class TestingMatcher
	{
		constructor(actualValue, expectedValue)
		{
			this.actualValue = actualValue;
			this.expectedValue = expectedValue;
		}

		/**
		 * @returns {boolean}
		 */
		match()
		{
			throw new Error('match() method must be overridden');
		}
	}

	class ExactMatcher extends TestingMatcher
	{
		/**
		 * @returns {boolean}
		 */
		match()
		{
			return Object.is(this.actualValue, this.expectedValue);
		}
	}

	class EqualityMatcher extends TestingMatcher
	{
		/**
		 * @returns {boolean}
		 */
		match()
		{
			if (this.isObject(this.actualValue) && this.isObject(this.expectedValue))
			{
				return this.isEqual(this.actualValue, this.expectedValue);
			}

			return Object.is(this.actualValue, this.expectedValue);
		}

		isObject(value)
		{
			return typeof value === 'object';
		}

		isArray(value)
		{
			return Array.isArray(value);
		}

		isEqual(value, other)
		{
			if (value === other)
			{
				return true;
			}

			const valueIsObject = this.isObject(value);
			const otherIsObject = this.isObject(other);

			if (value == null || other == null || (!valueIsObject && !otherIsObject))
			{
				return value !== value && other !== other;
			}

			const bothObjects = valueIsObject && otherIsObject;
			const bothArrays = this.isArray(value) && this.isArray(other);
			const oneIsArray = this.isArray(value) || this.isArray(other);

			if (bothObjects)
			{
				if (bothArrays)
				{
					return this.arraysEquals(value, other);
				}
				else if (oneIsArray)
				{
					return false;
				}
				else
				{
					return this.objectsEquals(value, other);
				}
			}

			return false;
		}

		arraysEquals(a, b)
		{
			if (a === b) return true;
			if (a.length !== b.length) return false;

			for (let i = 0; i < a.length; i++)
			{
				if (this.isArray(a[i]) && this.isArray(b[i]))
				{
					if (!this.arraysEquals(a[i], b[i]))
					{
						return false;
					}
				}
				else if (this.isObject(a[i]) && this.isObject(b[i]))
				{
					if (!this.objectsEquals(a[i], b[i]))
					{
						return false;
					}
				}
				else if (a[i] !== b[i])
				{
					return false;
				}
			}

			return true;
		}

		objectsEquals(obj1, obj2)
		{
			const props1 = Object.keys(obj1);
			const props2 = Object.keys(obj2);

			if (props1.length !== props2.length)
			{
				return false;
			}

			const objLength = props1.length;
			let index = objLength;
			let key;
			while (index--)
			{
				key = props1[index];
				if (!props2.includes(key))
				{
					return false;
				}
			}

			while (++index < objLength)
			{
				key = props1[index];
				let value1 = obj1[key];
				let value2 = obj2[key];

				if (!this.isEqual(value1, value2))
				{
					return false;
				}
			}

			return true;
		}
	}

	class ExceptionMatcher extends TestingMatcher
	{
		match()
		{
			try
			{
				this.actualValue();
				return false;
			}
			catch (error)
			{
				if (typeof this.expectedValue === 'function')
				{
					return error instanceof this.expectedValue;
				}
				else if (typeof this.expectedValue === 'string')
				{
					return this.expectedValue === error;
				}

				return true;
			}
		}
	}

	class DefinedMatcher extends TestingMatcher
	{
		/**
		 * @returns {boolean}
		 */
		match()
		{
			return typeof this.actualValue !== 'undefined';
		}
	}

	class NullMatcher extends TestingMatcher
	{
		/**
		 * @returns {boolean}
		 */
		match()
		{
			return this.actualValue === null;
		}
	}

	class BooleanMatcher extends TestingMatcher
	{
		constructor(actualValue, expectedValue)
		{
			super(actualValue, expectedValue);

			this.expectedValue = Boolean(expectedValue);
		}

		/**
		 * @returns {boolean}
		 */
		match()
		{
			const valuesAreEqual = this.actualValue === this.expectedValue;
			const typeIsBoolean = this.actualValue === true || this.actualValue === false;

			return typeIsBoolean && valuesAreEqual;
		}
	}

	/**
	 * class ExpectationFailed
	 */
	class ExpectationFailed
	{
		constructor(actualValue, expectedValue)
		{
			this.actualValue = actualValue;
			this.expectedValue = expectedValue;
		}
	}

	jnexport(Testing);

})();
