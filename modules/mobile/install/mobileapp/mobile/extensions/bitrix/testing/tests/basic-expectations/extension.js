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
			expect(() => { throw new Error(); }).toThrow(Error);
			expect(() => { throw 'err'; }).toThrow('err');
			expect(() => { throw 'err'; }).toThrow();
			expect(() => { throw new Error(); }).toThrow();
			expect('').toThrow();
		});
	});

	describe('basic async expectations test', () => {
		it('just works', async () => {
			const fn = async () => true;

			expect(await fn()).toBe(true);
			await expect(fn()).resolves.toBe(true);
		});

		test('two plus two is four', async () => {
			const plus = async (a, b) => a + b;

			expect(await plus(2, 2)).toBe(4);
			await expect(plus(2, 2)).resolves.toBe(4);
		});

		test('two plus two is not five', async () => {
			const plus = async (a, b) => a + b;

			expect(await plus(2, 2)).not.toBe(5);
			await expect(plus(2, 2)).resolves.not.toBe(5);
		});

		it('can handle exceptions with rejects property', async () => {
			await expect(Promise.reject('error')).rejects.toThrow('error');
			await expect(async () => { throw new Error(); }).rejects.toThrow(Error);
			await expect(async () => { throw 'err'; }).rejects.toThrow('err');
			await expect(async () => { throw new Error('err'); }).rejects.toThrow();

			const exceptionFn = async () => { throw new Error(); };
			await expect(async () => { await exceptionFn(); }).rejects.toThrow();
		});

		it('can handle exceptions with try/catch block', async () => {
			const exceptionFn = async () => { throw new Error(); };
			expect.assertions(1);
			try
			{
				await exceptionFn();
			}
			catch (error)
			{
				expect(error).toThrow();
			}
		});
	});
})();
