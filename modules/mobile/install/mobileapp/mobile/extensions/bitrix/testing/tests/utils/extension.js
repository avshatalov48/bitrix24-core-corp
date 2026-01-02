(() => {

	const require = ext => jn.require(ext);

	const { describe, it, test, expect, beforeEach } = require('testing');

	const { md5 } = require('utils/hash');
	const { clone, get, set, has, isEqual, isEmpty, merge, mergeImmutable } = require('utils/object');
	const { isRegExp } = require('utils/type');
	const { replaceAll, camelize, trim, number_format } = require('utils/string');
	const { formatFileSize } = require('utils/file');
	const { reflectFunction, debounce } = require('utils/function');
	const { Random } = require('utils/random');
	const { Loc } = require('loc');

	describe('global utils objects', () => {

		test('console.color exists', () => {
			expect(console.color).toBeDefined();
		});

		test('BX.prop helper exists', () => {
			expect(BX.prop).toBeDefined();
			expect(typeof BX.prop).toBe('object');
			expect(BX.prop.get).toBeDefined();
		});

		test('Application.storage exists', () => {
			expect(typeof Application.storage).toBe('object');
			expect(typeof Application.storageById).toBe('function');
		});

		test('ifApi exists and works', () => {
			expect(typeof ifApi).toBe('function');

			const apiVersion = Application.getApiVersion();
			const oldVersion = apiVersion - 1;
			const nextVersion = apiVersion + 1;

			let value = 10;

			ifApi(oldVersion, () => {
				value = 20;
			}).else(null, () => {
				value = 30;
			});

			expect(value).toBe(20);

			ifApi(nextVersion, () => {
				value = 40;
			}).else(null, () => {
				value = 50;
			});

			expect(value).toBe(50);
		});

	});

	describe('string utils tests', () => {
		test('camelize', () => {
			expect(camelize('emoji👍_test')).toBe('emoji👍Test');
			expect(camelize('  foo  bar  spaces ')).toBe('FooBarSpaces');
			expect(camelize(null)).toBe('');
			expect(camelize('hello world1')).toBe('helloWorld1');
			expect(camelize('hello worldEverything works')).toBe('helloWorldEverythingWorks');
			expect(camelize('hello worldEverythingWorks')).toBe('helloWorldEverythingWorks');
			expect(camelize('hello worldEverything Works')).toBe('helloWorldEverythingWorks');
			expect(camelize('hello world Everything Works')).toBe('helloWorldEverythingWorks');
			expect(camelize('hello world everything Works')).toBe('helloWorldEverythingWorks');
			expect(camelize('helloWorld2')).toBe('helloWorld2');
			expect(camelize('Hello world3')).toBe('helloWorld3');
			expect(camelize('hello_world4')).toBe('helloWorld4');
			expect(camelize('hello')).toBe('hello');
			expect(camelize('')).toBe('');
			expect(camelize('Hello')).toBe('hello');
		});

		test('trim', () => {
			expect(trim('  spaces  ')).toBe('spaces');
			expect(trim('	tabs	')).toBe('tabs');

			const multiline = `
			
				hello	
			
			`;

			expect(trim(multiline)).toBe('hello');
		});

		test('number_format', () => {
			const r1 = number_format(5500.824, 2, '.', ' ');
			const r2 = number_format(5500.869);
			const r3 = number_format(0);
			const r4 = number_format(-5500.869);
			const r5 = number_format(5500.845, 2);

			expect(r1).toBe('5 500.82');
			expect(r2).toBe('5.500,87');
			expect(r3).toBe('0,00');
			expect(r4).toBe('-5.500,87');
			expect(r5).toBe('5.500,85');
		});

		test('replaceAll polyfill', () => {

			expect(replaceAll('foobarfoo', 'foo', 'baz')).toBe('bazbarbaz');
			expect(replaceAll('foobarfoo', 'bar', 'foo')).toBe('foofoofoo');
			expect(replaceAll('foo', '', 'b')).toBe('bfbobob');
			expect(replaceAll('foobarfoo', /foo/g, 'baz')).toBe('bazbarbaz');
			expect(replaceAll('foobarfoo', new RegExp('bar', 'g'), 'baz')).toBe('foobazfoo');
			expect(replaceAll('foo', 'egg', 'bro')).toBe('foo');

		});

	});

	describe('hash utils test', () => {

		test('md5 hash works', () => {
			expect(md5('foobar')).toBe('3858f62230ac3c915f300c664312c63f');
			expect(md5(1100)).toBe('1e6e0a04d20f50967c64dac2d639a577');
			expect(md5(true)).toBe('b326b5062b2f0e69046810717534cb09');
			expect(md5(false)).toBe('68934a3e9455fa72420237eb05902327');
			expect(md5('')).toBe('d41d8cd98f00b204e9800998ecf8427e');
			expect(md5({})).toBe('99914b932bd37a50b983c5e7c90ae93b');
			expect(md5({ foo: 'bar' })).toBe('9bb58f26192e4ba00f01e2e7b136bbd8');
			expect(md5([])).toBe('d751713988987e9331980363e24189ce');
			expect(md5(['foo', 'bar'])).toBe('1ea13cb52ddd7c90e9f428d1df115d8f');
		});
	});

	describe('object utils test', () => {

		it('properly clones object', () => {
			const sources = [
				{foo: 'bar', baz: {eggs: 'qux'}, qux: [1, 2, {hello: 'world'}]},
				{},
				[],
				123,
				'foobar'
			];

			sources.map(source => {

				let cloned = clone(source);
				expect(cloned).toEqual(source);

			});
		});

		it('returns new object while cloning', () => {
			const source = { foo: 'bar' };
			const cloned = clone(source);

			expect(cloned).toEqual(source);
			expect(cloned).not.toBe(source);
		});

		it('properly clones arrays', () => {
			const source = [100, 200, '300', {foo: 'bar'}];
			const cloned = clone(source);
			const mapped = cloned.map(item => item);

			expect(Array.isArray(cloned)).toBeTrue();
			expect(cloned.map).toBeDefined();
			expect(typeof cloned.map).toBe('function');
			expect(mapped[0]).toBe(100);
			expect(mapped[1]).toBe(200);
			expect(mapped[2]).toBe('300');
			expect(mapped[3]).toEqual({foo: 'bar'});
		});

		it('properly clones nested arrays', () => {
			const source = {foo: 'bar', baz: [1, 2, 3, {test: 'case'}]};
			const cloned = clone(source);
			const mapped = cloned.baz.map(item => item);

			expect(Array.isArray(cloned.baz)).toBeTrue();
			expect(cloned.baz.map).toBeDefined();
			expect(typeof cloned.baz.map).toBe('function');
			expect(mapped[0]).toBe(1);
			expect(mapped[1]).toBe(2);
			expect(mapped[2]).toBe(3);
			expect(mapped[3]).toEqual({test: 'case'});
		});

		it('mutates target object while merging', () => {
			const origin = {foo: 'bar', baz: {eggs: 'qux'}, qux: [1, 2, 3]};
			const add = {hello: 'world', baz: {test: 'case'}, qux: [4, 5]};

			const result = merge(origin, add);

			expect(result).toBe(origin);
			expect(origin).toEqual({
				foo: 'bar',
				baz: {
					eggs: 'qux',
					test: 'case',
				},
				qux: [4, 5],
				hello: 'world',
			});
		});

		it('allows to keep objects immutable while merging', () => {
			const origin = {foo: 'bar', baz: {eggs: 'qux'}, qux: [1, 2, 3]};
			const add = {hello: 'world', baz: {test: 'case'}, qux: [4, 5]};

			const result = mergeImmutable(origin, add);

			expect(result).not.toBe(origin);
			expect(origin).toEqual({foo: 'bar', baz: {eggs: 'qux'}, qux: [1, 2, 3]});
			expect(result).toEqual({
				foo: 'bar',
				baz: {
					eggs: 'qux',
					test: 'case',
				},
				qux: [4, 5],
				hello: 'world',
			});
		});

		test('merge corner cases', () => {
			expect(merge()).not.toBeDefined();
			expect(merge([1], [2, 3])).toEqual([2, 3]);
			expect(merge({foo: 'bar'})).toEqual({foo: 'bar'});
			expect(merge({foo: 'bar'}, [2, 3])).toEqual({foo: 'bar', 0: 2, 1: 3});
			expect(merge({foo: 'bar'}, 100)).toEqual({foo: 'bar'});
			expect(merge(100, 200)).toThrow();
		});

		it('can merge multiple objects', () => {
			const result = merge(
				{},
				{foo: 'bar'},
				{foo: {baz: 'eggs'}},
				{foo: {qux: 'fix'}},
				{ar: [1, 2, 3, {hello: 'world'}]}
			);

			expect(result).toEqual({
				foo: {baz: 'eggs', qux: 'fix'},
				ar: [1, 2, 3, {hello: 'world'}]
			});
		});

		test('object deep set', () => {
			const origin = {foo: 'bar'};

			const result = set(origin, 'baz.eggs', 'qux');

			expect(result).toBe(origin);
			expect(origin).toEqual({foo: 'bar', baz: {eggs: 'qux'}});

			expect(set(origin, 'foo.hello', 'world')).toEqual({
				foo: {hello: 'world'},
				baz: {eggs: 'qux'}
			});

			expect(set(origin, 'test', 'case')).toEqual({
				foo: {hello: 'world'},
				baz: {eggs: 'qux'},
				test: 'case',
			});
		});

		test('object deep get', () => {
			const origin = {
				foo: 'bar',
				baz: {
					eggs: 'qux',
					meh: 0,
				},
				qux: [1, 2, 3]
			};

			expect(get(origin, 'foo', 'def')).toBe('bar');
			expect(get(origin, 'baz.eggs', 'def')).toBe('qux');
			expect(get(origin, 'unknown', 'def')).toBe('def');
			expect(get(origin, 'unknown')).not.toBeDefined();
			expect(get(origin, 'baz.meh', 'def')).toBe(0);
		});

		test('deep get from object with inheritance', () => {
			const base = {
				baseProp: 'baseValue',
				falseProp: false,
				falsyProp: null,
				nestedProp: { foo: 'bar' }
			};
			const child = { childProp: 'childValue' };
			Object.setPrototypeOf(child, base);

			expect(get(child, 'childProp', 'def')).toBe('childValue');
			expect(get(child, 'baseProp', 'def')).toBe('baseValue');
			expect(get(child, 'falseProp', 'def')).toBeFalse();
			expect(get(child, 'falsyProp', 'def')).toBeNull();
			expect(get(child, 'nestedProp.foo', 'def')).toBe('bar');
			expect(get(child, 'undefinedProp', 'def')).toBe('def');
		});

		test('object has property', () => {
			const base = {
				baseProp: 'baseValue',
				falseProp: false,
				falsyProp: null,
				nestedProp: { foo: 'bar' }
			};
			const child = {
				foo: 'bar',
				baz: {
					eggs: 'qux',
					meh: 0,
				},
				qux: [1, 2, 3]
			};
			Object.setPrototypeOf(child, base);

			expect(has(child, 'foo')).toBeTrue();
			expect(has(child, 'baz.eggs')).toBeTrue();
			expect(has(child, 'baz.meh')).toBeTrue();
			expect(has(child, 'baz.qux')).toBeFalse();

			expect(has(child, 'baseProp')).toBeTrue();
			expect(has(child, 'nestedProp.foo')).toBeTrue();
			expect(has(child, 'falseProp')).toBeTrue();
			expect(has(child, 'falsyProp')).toBeTrue();
			expect(has(child, 'undefinedProp')).toBeFalse();

		});

		test('objects equality', () => {

			let none1, none2;

			const cases = [
				[{}, {}, true],
				[{foo: 'bar'}, {foo: 'bar'}, true],
				[{foo: {bar: 'baz'}}, {foo: {bar: 'baz'}}, true],
				[{foo: {bar: 'baz'}}, {foo: {bar: 'qux'}}, false],
				[{foo: {bar: 'baz'}}, {foo: {bar: 'baz'}, hello: 'world'}, false],
				[{foo: 'bar', bar: 'baz'}, {bar: 'baz', foo: 'bar'}, true],
				[[], [], true],
				[[1, 2, 3], [1, 2, 3], true],
				[[1, 2, 3], [3, 2, 1], false],
				[[1, 2, 3], [1, 2, 4], false],
				[[1, 2, 3], [1, 2, 3, 4], false],
				[[], {}, false],
				[12, 12, true],
				[12, 13, false],
				[12, '12', false],
				[null, null, true],
				[none1, none2, true],
				[none1, none1, true],
				[none1, null, false],
				['', '', true],
				['foo', 'bar', false],
				[true, true, true],
				[true, false, false],
			];

			cases.map(([val, other, result]) => {
				expect(isEqual(val, other)).toBe(result);
			});

		});

		test('dates equality', () => {
			expect(isEqual(new Date('2023-04-05'), new Date('2023-04-05'))).toBeTrue();
			expect(isEqual(new Date('2023-04-05'), new Date('2023-04-06'))).toBeFalse();
			expect(isEqual(new Date('wrong date'), new Date('other wrong date'))).toBeFalse();
			expect(isEqual(new Date(), 'not date')).toBeFalse();
		});

		test('object emptiness', () => {

			const cases = [
				[[], true],
				[{}, true],
				[[1, 2, 3], false],
				[{foo: 'bar'}, false],
				['123', false],
				['', true],
				[123, true],
				[true, true],
				[false, true],
				[new Map, true],
				[new Map([[1, 'foo'], [2, 'bar']]), false],
				[new Set, true],
				[new Set([1, 2]), false],
			];

			cases.map(([val, result]) => {
				expect(isEmpty(val)).toBe(result);
			});

		});

	});

	describe('random utils test', () => {

		test('random string', () => {
			const r1 = Random.getString(0);
			const r2 = Random.getString();
			const r3 = Random.getString(16);
			const r4 = Random.getString(255);

			expect(typeof r1).toBe('string');
			expect(r1).toBe('');
			expect(typeof r2).toBe('string');
			expect(r2.length).toBe(8);
			expect(r3.length).toBe(16);
			expect(r4.length).toBe(255);
		});

		test('random int', () => {
			const rand = (min, max) => Random.getInt(min, max);

			const cases = [
				[1, 100],
				[-100, -1],
				[-100, 100],
				[Number.MIN_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
			];

			cases.map(([min, max]) => {
				const r = rand(min, max);
				expect(typeof r).toBe('number');
				expect(r >= min).toBeTrue();
				expect(r <= max).toBeTrue();
			});

			const r = rand(0, 1);
			expect(r === 0 || r === 1).toBeTrue();

			expect(isNaN(rand())).toBeTrue();
		});

	});

	describe('function utils test', () => {
		test('debounce helper exists', () => {
			const fn = () => {};
			const debounced = debounce(fn, 300);

			expect(typeof debounced).toBe('function');
		});

		test('reflect function', () => {
			const greeter = {
				phrase: 'Hello',
				getMessage(name) {
					return `${this.phrase}, ${name}!`;
				},
			};

			const decoratedGreeter = reflectFunction(greeter, 'get message');

			expect(greeter.getMessage('world')).toBe('Hello, world!');
			expect(decoratedGreeter('Tester')).toBe('Hello, Tester!');
		});
	});

	describe('localization', () => {
		test('plural form', () => {
			expect(Loc.getPluralForm(1, 'ru')).toBe(0);
			expect(Loc.getPluralForm(2, 'ru')).toBe(1);
			expect(Loc.getPluralForm(-2, 'ru')).toBe(1);
			expect(Loc.getPluralForm(5, 'ru')).toBe(2);
			expect(Loc.getPluralForm(0, 'ru')).toBe(2);

			expect(Loc.getPluralForm(1, 'en')).toBe(0);
			expect(Loc.getPluralForm(2, 'en')).toBe(1);
			expect(Loc.getPluralForm(-2, 'en')).toBe(1);
			expect(Loc.getPluralForm(5, 'en')).toBe(1);
			expect(Loc.getPluralForm(0, 'en')).toBe(1);
		});
	});

	describe('type utils test', () => {

		test('isRegExp function', () => {

			const obj = new RegExp('foo', 'g');
			const literal = /foo/g;

			expect(isRegExp(obj)).toBeTrue();
			expect(isRegExp(literal)).toBeTrue();
			expect(isRegExp({})).toBeFalse();
			expect(isRegExp('hello')).toBeFalse();

		});
	});

	describe('file utils test', () => {
		const precision = 1;
		const phrases = formatFileSize.defaultPhrases;

		test('filesize format falsy values', () => {
			expect(formatFileSize(undefined, precision, phrases)).toBe('0 bytes');
			expect(formatFileSize(0, precision, phrases)).toBe('0 bytes');
			expect(formatFileSize('', precision, phrases)).toBe('0 bytes');
			expect(formatFileSize(-1, precision, phrases)).toBe('0 bytes');
		});

		test('filesize format string values', () => {
			expect(formatFileSize('123', precision, phrases)).toBe('123 bytes');
			expect(formatFileSize('-1', precision, phrases)).toBe('0 bytes');
			expect(formatFileSize('0', precision, phrases)).toBe('0 bytes');
		});

		test('filesize format numbers', () => {
			expect(formatFileSize(1024, precision, phrases)).toBe('1 KB');
			expect(formatFileSize(2048, precision, phrases)).toBe('2 KB');
			expect(formatFileSize(2049, precision, phrases)).toBe('2 KB');
			expect(formatFileSize(3500, precision, phrases)).toBe('3.4 KB');

			expect(formatFileSize(2_000_000, precision, phrases)).toBe('1.9 MB');
			expect(formatFileSize(2_000_000_000, precision, phrases)).toBe('1.9 GB');
			expect(formatFileSize(2_000_000_000_000, precision, phrases)).toBe('1.8 TB');
			expect(formatFileSize(2_000_000_000_000_000, precision, phrases)).toBe('1819 TB');
		});

		test('filesize format precision', () => {
			expect(formatFileSize(2560, 0, phrases)).toBe('3 KB');
			expect(formatFileSize(2560, 1, phrases)).toBe('2.5 KB');

			expect(formatFileSize(2561, 2, phrases)).toBe('2.5 KB');
			expect(formatFileSize(2561, 3, phrases)).toBe('2.501 KB');
		});
	});
})();
