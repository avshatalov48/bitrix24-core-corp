(() => {

	const require = ext => jn.require(ext);

	const { describe, it, test, expect } = require('testing');

	describe('basic expectations test', () => {

		it('just works', () => {
			expect(true).toBe(true);
		});

		test('two plus two is four', () => {
			expect(2 + 2).toBe(4);
		});

		test('two plus two is not five', () => {
			expect(2 + 2).not.toBe(5);
		});

		it('correctly compare objects', () => {
			const foo = {qux: 'eggs'};
			const bar = foo;
			const baz = {qux: 'eggs'};

			expect(foo).toBe(bar);
			expect(foo).not.toBe(baz);
			expect(foo).toEqual(baz);
		});

		it('correctly compare arrays', () => {
			const foo = [1, 2, 3];
			const bar = foo;
			const baz = [1, 2, 3];
			const qux = [1, 2, 4];

			expect(foo).toBe(bar);
			expect(foo).not.toBe(baz);
			expect(foo).toEqual(baz);
			expect(foo).not.toEqual(qux);
		});

		it('correctly compare nested objects and arrays', () => {
			const origin = {
				foo: 'bar',
				baz: {
					eggs: 'qux'
				},
				qux: [
					1,
					2,
					[
						{hello: 'world'},
						{say: 'hi'}
					]
				]
			};

			const exactCopy = {foo: 'bar', baz: {eggs: 'qux'}, qux: [1, 2, [{hello: 'world'}, {say: 'hi'}]]};
			const wrongCopy1 = {foo: 'bar', baz: {eggs: 'qux'}, qux: [1, 2, [{say: 'hi'}, {hello: 'world'}]]};
			const wrongCopy2 = {foo: 'bar', baz: {eggs: 'qux'}, qux: [1, 2, [{hello: 'WRONG'}, {say: 'hi'}]]};

			expect(exactCopy).toEqual(origin);
			expect(wrongCopy1).not.toEqual(origin);
			expect(wrongCopy2).not.toEqual(origin);
		});

		it('can handle exceptions', () => {
			expect(() => { throw new Error }).toThrow(Error);
			expect(() => { throw 'err' }).toThrow('err');
			expect(() => { throw 'err' }).toThrow();
			expect(() => { throw new Error }).toThrow();
		});

	});

})();