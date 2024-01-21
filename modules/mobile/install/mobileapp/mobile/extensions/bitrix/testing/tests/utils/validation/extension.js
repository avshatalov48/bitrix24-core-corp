(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');

	describe('validation utilities', () => {
		const { PropTypes, PropTypesPolyfill } = require('utils/validation');

		const assertions = (typesObject) => {
			expect(typesObject.any).toBeDefined();
			expect(typesObject.any.isRequired).toBeDefined();
			expect(typesObject.oneOf).toBeDefined();
			expect(typesObject.validate).toBeDefined();

			const rules = {
				id: typesObject.number,
				name: typesObject.string,
				value: typesObject.oneOf(['foo', 'bar']).isRequired,
			};

			const testObj = { id: 123, name: 'foo', value: 'bar' };

			const result = typesObject.validate(rules, testObj, 'TestComponent');

			expect(result).not.toBeDefined();
		};

		test('PropTypes polyfill', () => {
			assertions(PropTypes);
			assertions(PropTypesPolyfill);
		});
	});
})();
