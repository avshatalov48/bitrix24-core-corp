(() => {
	const require = (ext) => jn.require(ext);
	const { FormatterFactory, FormatterTypes } = require('layout/ui/friendly-date/formatter-factory');
	const { HumanDateFormatter } = require('layout/ui/friendly-date/human-date-formatter');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { describe, test, expect } = require('testing');

	describe('FormatterFactory', () => {
		const testProps = { option: 'value' };

		test('should create HumanDateFormatter for HUMAN_DATE type', () => {
			const formatter = FormatterFactory.create(FormatterTypes.HUMAN_DATE);
			expect(formatter).toBeInstanceOf(HumanDateFormatter);
		});

		test('should create TimeAgoFormat for DEFAULT type', () => {
			const formatter = FormatterFactory.create(FormatterTypes.DEFAULT);
			expect(formatter).toBeInstanceOf(TimeAgoFormat);
		});

		test('should create TimeAgoFormat instance in method createDefault()', () => {
			const formatter = FormatterFactory.createDefault();
			expect(formatter).toBeInstanceOf(TimeAgoFormat);
		});

		test('should pass props to TimeAgoFormat in method createDefault()', () => {
			const formatter = FormatterFactory.createDefault(testProps);
			expect(formatter.props).toEqual(testProps);
		});

		test('should handle empty props in method createDefault()', () => {
			const formatter = FormatterFactory.createDefault();
			expect(formatter.props).toEqual({});
		});

		test('should handle empty props gracefully', () => {
			expect(() => {
				FormatterFactory.create(FormatterTypes.HUMAN_DATE, null);
				FormatterFactory.create(FormatterTypes.DEFAULT, undefined);
			}).not.toThrow();
		});

		test('should throw error for unknown types', () => {
			expect(() => FormatterFactory.create('UNKNOWN_TYPE')).toThrow();
		});
	});

	describe('FormatterTypes', () => {
		test('should have correct type values', () => {
			expect(FormatterTypes.HUMAN_DATE).toBe('humanDate');
			expect(FormatterTypes.DEFAULT).toBe('timeAgo');
		});
	});
})();
