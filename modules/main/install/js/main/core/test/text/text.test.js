import {Text} from '../../src/core';

describe('core/text', () => {
	describe('encode', () => {
		it('Should be exported as function', () => {
			assert(typeof Text.encode === 'function');
		});

		it('Should encode passed string with html', () => {
			let source = `Yo <div class="name">World</div>`;
			let result = 'Yo &lt;div class=&quot;name&quot;&gt;World&lt;/div&gt;';

			assert(Text.encode(source) === result);
		});

		it('Should return passed value if passed not string', () => {
			assert(Text.encode(null) === null);
			assert(Text.encode(true) === true);

			let arr = [];
			assert(Text.encode(arr) === arr);

			let obj = {};
			assert(Text.encode(obj) === obj);
		});
	});

	describe('decode', () => {
		it('Should be exported as function', () => {
			assert(typeof Text.decode === 'function');
		});

		it('Should Text.decode passed string with encoded html', () => {
			let source = 'Yo &lt;div class=&quot;name&quot;&gt;World&lt;/div&gt;';
			let result = `Yo <div class="name">World</div>`;

			assert(Text.decode(source) === result);
		});

		it('Should return passed value if passed not string', () => {
			assert(Text.decode(null) === null);
			assert(Text.decode(true) === true);

			let arr = [];
			assert(Text.decode(arr) === arr);

			let obj = {};
			assert(Text.decode(obj) === obj);
		});
	});

	// https://www.ecma-international.org/ecma-262/5.1/#sec-15.1.2.3
	describe('toNumber', () => {
		it('Should be a function', () => {
			assert.ok(typeof Text.toNumber === 'function');
		});

		it('Should return 1 for 1 (number)', () => {
			assert.ok(Text.toNumber(1) === 1);
		});

		it('Should return 0 for 0 (number)', () => {
			assert.ok(Text.toNumber(0) === 0);
		});

		it('Should return 1.1 for 1.1 (number)', () => {
			assert.ok(Text.toNumber(1.1) === 1.1);
		});

		it('Should return 1.00001 for 1.00001 (number)', () => {
			assert.ok(Text.toNumber(1.00001) === 1.00001);
		});

		it('Should return 1 for "1" (string)', () => {
			assert.ok(Text.toNumber("1") === 1);
		});

		it('Should return 0 for "0" (string)', () => {
			assert.ok(Text.toNumber("0") === 0);
		});

		it('Should return 1.1 for "1.1" (string)', () => {
			assert.ok(Text.toNumber("1.1") === 1.1);
		});

		it('Should return 1.00001 for "1.00001" (string)', () => {
			assert.ok(Text.toNumber("1.00001") === 1.00001);
		});

		it('Should return 0 for true (boolean)', () => {
			assert.ok(Text.toNumber(true) === 0);
		});

		it('Should return 0 for false (boolean)', () => {
			assert.ok(Text.toNumber(false) === 0);
		});

		it('Should return 0 for {} (object)', () => {
			assert.ok(Text.toNumber({}) === 0);
		});

		it('Should return 0 for [] (object)', () => {
			assert.ok(Text.toNumber({}) === 0);
		});

		it('Should return 10 for "10px" (string)', () => {
			assert.ok(Text.toNumber('10test') === 10);
		});

		it('Should return 0 for "px10" (string)', () => {
			assert.ok(Text.toNumber('px10') === 0);
		});

		it('Should return 0 for NaN (number)', () => {
			assert.ok(Text.toNumber(NaN) === 0);
		});
	});

	// https://www.ecma-international.org/ecma-262/5.1/#sec-15.1.2.2
	describe('toInteger', () => {
		it('Should be a function', () => {
			assert.ok(typeof Text.toInteger === 'function');
		});

		it('Should return 1 for 1 (number)', () => {
			assert.ok(Text.toInteger(1) === 1);
		});

		it('Should return 0 for 0 (number)', () => {
			assert.ok(Text.toInteger(0) === 0);
		});

		it('Should return 1 for 1.1 (number)', () => {
			assert.ok(Text.toInteger(1.1) === 1);
		});

		it('Should return 1 for 1.4 (number)', () => {
			assert.ok(Text.toInteger(1.4) === 1);
		});

		it('Should return 1 for 1.9 (number)', () => {
			assert.ok(Text.toInteger(1.9) === 1);
		});

		it('Should return 0 for 0.9 (number)', () => {
			assert.ok(Text.toInteger(0.9) === 0);
		});

		it('Should return 1 for "1" (string)', () => {
			assert.ok(Text.toInteger('1') === 1);
		});

		it('Should return 0 for "0" (string)', () => {
			assert.ok(Text.toInteger('0') === 0);
		});

		it('Should return 1.1 for "1.1" (string)', () => {
			assert.ok(Text.toInteger('1.1') === 1);
		});

		it('Should return 1 for "1.4" (string)', () => {
			assert.ok(Text.toInteger('1.4') === 1);
		});

		it('Should return 1 for "1.9" (string)', () => {
			assert.ok(Text.toInteger('1.9') === 1);
		});

		it('Should return 0 for {} (object)', () => {
			assert.ok(Text.toInteger({}) === 0);
		});

		it('Should return 0 for [] (object)', () => {
			assert.ok(Text.toInteger({}) === 0);
		});

		it('Should return 0 for "" (string)', () => {
			assert.ok(Text.toInteger('') === 0);
		});

		it('Should return 2 for "2.5%" (string)', () => {
			assert.ok(Text.toInteger('') === 0);
		});

		it('Should return 0 for NaN (number)', () => {
			assert.ok(Text.toInteger(NaN) === 0);
		});
	});

	describe('toBoolean', () => {
		it('Should be a function', () => {
			assert.ok(typeof Text.toBoolean === 'function');
		});

		it('Should return true for true (boolean)', () => {
			assert.ok(Text.toBoolean(true) === true);
		});

		it('Should return false for false (boolean)', () => {
			assert.ok(Text.toBoolean(false) === false);
		});

		it('Should return true for 1 (number)', () => {
			assert.ok(Text.toBoolean(1) === true);
		});

		it('Should return false for 0 (number)', () => {
			assert.ok(Text.toBoolean(0) === false);
		});

		it('Should return true for "Y" (string)', () => {
			assert.ok(Text.toBoolean("Y") === true);
		});

		it('Should return true for "y" (string)', () => {
			assert.ok(Text.toBoolean("y") === true);
		});

		it('Should return false for "N" (string)', () => {
			assert.ok(Text.toBoolean("N") === false);
		});

		it('Should return false for "n" (string)', () => {
			assert.ok(Text.toBoolean("n") === false);
		});

		it('Should return true for "1" (string)', () => {
			assert.ok(Text.toBoolean("1") === true);
		});

		it('Should return false for "0" (string)', () => {
			assert.ok(Text.toBoolean("0") === false);
		});

		it('Should return true for custom true-value', () => {
			assert.ok(Text.toBoolean('on', ['on']) === true);
		});

		it('Should return false for custom true-value', () => {
			assert.ok(Text.toBoolean('no', ['on']) === false);
		});
	});
});