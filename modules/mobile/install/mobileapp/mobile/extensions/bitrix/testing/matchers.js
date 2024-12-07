/**
 * @module testing/matchers
 */
jn.define('testing/matchers', (require, exports, module) => {
	const { Type } = require('type');
	const { isEqual } = require('utils/object');

	/**
	 * @abstract
	 */
	class TestingMatcher
	{
		constructor(actualValue, expectedValue)
		{
			this.actualValue = actualValue;
			this.expectedValue = expectedValue;
		}

		/**
		 * @abstract
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
				const value1 = obj1[key];
				const value2 = obj2[key];

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

	class AsyncExceptionMatcher extends ExceptionMatcher
	{
		match()
		{
			if (typeof this.expectedValue === 'function')
			{
				return this.actualValue instanceof this.expectedValue;
			}

			if (typeof this.expectedValue === 'string')
			{
				return this.expectedValue === this.actualValue;
			}

			return true;
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

	class MatchObjectMatcher extends TestingMatcher
	{
		/**
		 * @returns {boolean}
		 */
		match()
		{
			if (Type.isPlainObject(this.actualValue) && Type.isPlainObject(this.expectedValue))
			{
				return this.isEqualExpectedProperties(this.actualValue, this.expectedValue);
			}

			return false;
		}

		isEqualExpectedProperties(actual, expected)
		{
			const actualProps = Object.keys(actual);
			const expectedProps = Object.keys(expected);

			if (expectedProps.length > actualProps.length)
			{
				return false;
			}

			const objLength = expectedProps.length;
			let index = objLength;
			let key;
			while (index--)
			{
				key = expectedProps[index];
				if (!actualProps.includes(key))
				{
					return false;
				}
			}

			while (++index < objLength)
			{
				key = expectedProps[index];
				const value1 = expected[key];
				const value2 = actual[key];

				if (!isEqual(value1, value2))
				{
					return false;
				}
			}

			return true;
		}
	}

	module.exports = {
		TestingMatcher,
		ExactMatcher,
		EqualityMatcher,
		ExceptionMatcher,
		AsyncExceptionMatcher,
		DefinedMatcher,
		NullMatcher,
		BooleanMatcher,
		MatchObjectMatcher,
	};

});