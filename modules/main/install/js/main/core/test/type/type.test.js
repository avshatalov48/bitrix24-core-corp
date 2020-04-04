import Type from '../../src/lib/type';

describe('core/type', () => {
	describe('isPlainObject', () => {

		it("accepts an empty object", function() {
			assert(Type.isPlainObject({}) === true);
		});

		it("accepts an object with nullable prototype", function() {
			assert(Type.isPlainObject(Object.create(null)) === true);
		});

		it("accepts an regular non-empty object", function() {
			assert(Type.isPlainObject({a: 1, b: 2}) === true);
		});

		it("rejects a function", function() {
			assert(Type.isPlainObject(function() {}) === false);
		});

		it("rejects a boolean value", function() {
			assert(Type.isPlainObject(true) === false);
		});

		it("rejects an undefined value", function() {
			assert(Type.isPlainObject() === false);
			assert(Type.isPlainObject(undefined) === false);
		});

		it("rejects a null value", function() {
			assert(Type.isPlainObject(null) === false);
		});

		it("rejects an instance", function() {
			assert(Type.isPlainObject(new function() { this.a = 1 }) === false);
			assert(Type.isPlainObject(new Type()) === false);
		});
	})
});