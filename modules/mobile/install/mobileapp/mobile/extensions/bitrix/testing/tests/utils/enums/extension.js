(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');

	describe('enum', () => {
		const { IndentEnum, CarEnum } = require('testing/tests/utils/enums/src/test');
		test('static forEach', () => {
			const result = [];
			IndentEnum.forEach(({ name, value }) => {
				result.push([name, value]);
			});

			expect(result).toEqual([
				['CIRCLE', 512],
				['XL2', 18],
				['XL', 18],
				['L', 12],
				['M', 8],
				['S', 6],
				['XS', 4],
			]);
		});

		test('static isDefined', () => {
			expect(IndentEnum.isDefined(512)).toBeTrue();
			expect(IndentEnum.isDefined('512')).toBeFalse();
			expect(IndentEnum.isDefined(500)).toBeFalse();
		});

		test('static getKeys', () => {
			expect(IndentEnum.getKeys()).toEqual(['CIRCLE', 'XL2', 'XL', 'L', 'M', 'S', 'XS']);
			expect(CarEnum.getKeys()).toEqual([
				'TESLA',
				'LAND_ROVER',
				'BMW',
				'VOLKSWAGEN',
				'MAZDA',
				'FORD',
				'TOYOTA',
				'XS',
			]);
			expect(IndentEnum.getKeys()).not.toEqual(['CIRCLE', 'XL2', 'XL', 'L', 'M']);
			expect(IndentEnum.getKeys()).not.toEqual([]);
		});

		test('static getValues', () => {
			expect(IndentEnum.getValues()).toEqual([512, 18, 18, 12, 8, 6, 4]);
			expect(IndentEnum.getValues()).not.toEqual(['514', '18', '18', '12', '8', '6', '4']);
			expect(CarEnum.getValues()).toEqual([700, 300, 550, 250, 200, 350, 180, 4]);
			expect(CarEnum.getValues()).not.toEqual([700, 300, 550, 250, 200, 350, 180, 8]);
			expect(IndentEnum.getValues()).not.toEqual([]);
		});

		test('static getEnums', () => {
			expect(IndentEnum.getEnums()).toEqual([
				new IndentEnum('CIRCLE', 512),
				new IndentEnum('XL2', 18),
				new IndentEnum('XL', 18),
				new IndentEnum('L', 12),
				new IndentEnum('M', 8),
				new IndentEnum('S', 6),
				new IndentEnum('XS', 4),
			]);
			expect(IndentEnum.getEnums()).not.toEqual([
				new IndentEnum('CIRCLE', 512),
				new IndentEnum('XL', 18),
				new IndentEnum('S', 6),
				new IndentEnum('XS', 4),
			]);
			expect(IndentEnum.getEnums()).not.toEqual([]);
		});

		test('static getEntries', () => {
			expect(IndentEnum.getEntries()).toEqual([
				['CIRCLE', 512],
				['XL2', 18],
				['XL', 18],
				['L', 12],
				['M', 8],
				['S', 6],
				['XS', 4],
			]);

			expect(IndentEnum.getEntries()).not.toEqual([
				['CIRCLE', 512],
				['XL', 18],
				['L', 10],
				['M', 8],
				['S', 6],
				['XS', 4],
			]);
			expect(IndentEnum.getEntries()).not.toEqual([
				['CIRCLE', 512],
				['XL', 18],
				['L', 10],
			]);
			expect(IndentEnum.getEntries()).not.toEqual([]);
		});

		test('static getEnum', () => {
			expect(IndentEnum.getEnum('XS')).toEqual(new IndentEnum('XS', 4));
			expect(IndentEnum.getEnum('XS')).not.toEqual(new IndentEnum('xs', 4));
			expect(IndentEnum.getEnum('XS')).not.toEqual(new IndentEnum('XS', '4'));
			expect(IndentEnum.getEnum('XS')).not.toEqual(new IndentEnum());
		});

		test('static resolve', () => {
			expect(IndentEnum.resolve(IndentEnum.XL)).toEqual(IndentEnum.XL);
			expect(IndentEnum.resolve(null, IndentEnum.XS)).toEqual(IndentEnum.XS);
			expect(IndentEnum.resolve(undefined, IndentEnum.XL2)).toEqual(IndentEnum.XL2);
			expect(() => IndentEnum.resolve('4', IndentEnum.XL2)).toThrow();
			expect(() => IndentEnum.resolve(4, IndentEnum.XL2)).toThrow();
			expect(() => IndentEnum.resolve(IndentEnum.XS, IndentEnum.XL2)).not.toThrow();
		});

		test('static has', () => {
			expect(IndentEnum.has(IndentEnum.XL)).toBeTrue();
			expect(IndentEnum.has(CarEnum.LAND_ROVER)).toBeFalse();
		});

		test('getValue', () => {
			expect(IndentEnum.XS.getValue()).toEqual(4);
			expect(IndentEnum.XS.getValue()).not.toEqual('4');
		});

		test('toPrimitive', () => {
			expect(IndentEnum.XS.toPrimitive()).toEqual(4);
			expect(IndentEnum.XS.toPrimitive()).not.toEqual('4');
		});

		test('toString', () => {
			expect(IndentEnum.XS.toString()).not.toEqual(4);
			expect(IndentEnum.XS.toString()).toEqual('4');
		});

		test('getName', () => {
			expect(IndentEnum.XS.getName()).toEqual('XS');
			expect(IndentEnum.XS.getName()).not.toEqual('xs');
			expect(IndentEnum.XS.getName()).not.toEqual('xS');
		});

		test('equal', () => {
			expect(IndentEnum.XL.equal(IndentEnum.XL2)).toBeTrue();
			expect(IndentEnum.XL.equal(IndentEnum.XS)).toBeFalse();
			expect(IndentEnum.XS.equal(CarEnum.XS)).toBeFalse();
		});
	});
})();
