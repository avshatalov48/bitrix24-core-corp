(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { CollapsibleText } = require('layout/ui/collapsible-text');

	describe('CollapsibleText methods test', () => {
		test('getCropPosition should return position of second newLine by maxNewLineCount = 2', () => {
			const collapsibleText = new CollapsibleText({
				value: '0123\n5678\n0123',
				maxNewLineCount: 2,
			});

			expect(collapsibleText.getCropPosition()).toBe(9);
		});

		test('getCropPosition should return position 7 by maxLettersCount = 7', () => {
			const collapsibleText = new CollapsibleText({
				value: '0123\n5678\n0123',
				maxLettersCount: 7,
			});

			expect(collapsibleText.getCropPosition()).toBe(7);
		});

		test('getCropPosition should return the smaller position of the maxNewLineCount and maxLettersCount (pos maxLettersCount < pos maxNewLineCount)', () => {
			const collapsibleText = new CollapsibleText({
				value: '0123\n5678\n0123',
				maxNewLineCount: 2,
				maxLettersCount: 7,
			});

			expect(collapsibleText.getCropPosition()).toBe(7);
		});

		test('getCropPosition should return the smaller position of the maxNewLineCount and maxLettersCount (pos maxLettersCount > pos maxNewLineCount)', () => {
			const collapsibleText = new CollapsibleText({
				value: '0123\n5678\n0123',
				maxNewLineCount: 2,
				maxLettersCount: 11,
			});

			expect(collapsibleText.getCropPosition()).toBe(9);
		});

		test('getCropPosition ', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n 012345678 01234567 0123 1234567890 \n\n\n\n\n',
				maxLettersCount: 10,
			});

			expect(collapsibleText.getCropPosition()).toBe(10);
		});

		test('getCropPosition should return position of second newLine by maxNewLineCount = 2 ignoring newlines and spaces at the beginning', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n     0123456789\n123456789\n123456789    \n\n\n\n\n',
				maxNewLineCount: 2,
			});

			expect(collapsibleText.getCropPosition()).toBe(20);
		});

		test('getCropPosition should return last text position ignoring newlines and spaces at the beginning and ending', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n     0123456789\n123456789\n123456789    \n\n\n\n\n',
				maxNewLineCount: 3,
			});

			expect(collapsibleText.getCropPosition()).toBe(30);
		});

		test('getCroppedValue should return correct croppedValue', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n     0123456789\n123456789\n123456789    \n\n\n\n\n',
				maxNewLineCount: 3,
			});

			expect(collapsibleText.getCroppedValue()).toBe('0123456789\n123456789\n123456789');
		});

		test('getCroppedBBCodeValue should return correct croppedValue', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n     0123456789\n123456789\n123456789    \nqqq\n\n\n\n',
				maxNewLineCount: 3,
			});

			expect(collapsibleText.getCroppedBBCodeValue()).toBe('0123456789\n123456789\n123456789    ');
		});

		test('getCroppedBBCodeValue should return correct croppedValue with bbcode', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n     0123456789\n123456[b]789\n123456789    \nqqq[/b]\n\n\n\n',
				maxNewLineCount: 3,
			});

			expect(collapsibleText.getCroppedBBCodeValue()).toBe('0123456789\n123456[b]789\n123456789    [/b]');
		});

		test('isExpandable should return false if getCropPosition < length of text, ignoring newlines and spaces at the beginning and ending', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n     0123456789\n123456789\n123456789    \n\n\n\n\n',
				maxNewLineCount: 3,
			});

			expect(collapsibleText.isExpandable()).toBe(false);
		});

		test('isExpandable should return true if getCropPosition >= length of text, ignoring newlines and spaces at the beginning and ending', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n     0123456789\n123456789\n123456789    \nqqq\n\n\n\n',
				maxNewLineCount: 1,
			});

			expect(collapsibleText.isExpandable()).toBe(true);
		});

		test('isExpandable should return true if getCropPosition < length of text, ignoring newlines and spaces at the beginning and ending', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n0123456789\n123456789\n123456789\n\n\n\n',
				maxLettersCount: 29,
			});

			expect(collapsibleText.isExpandable()).toBe(true);
		});

		test('isExpandable should return false if getCropPosition >= length of text, ignoring newlines and spaces at the beginning and ending', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n0123456789\n123456789\n123456789\n\n\n\n',
				maxLettersCount: 30,
			});

			expect(collapsibleText.isExpandable()).toBe(false);
		});

		test('isExpandable should return false if getCropPosition >= length of text, ignoring newlines and spaces at the beginning and ending', () => {
			const collapsibleText = new CollapsibleText({
				value: '\n\n\n\n\n0123456789\n123456789\n123456789\n\n\n\n',
				maxLettersCount: 50,
			});

			expect(collapsibleText.isExpandable()).toBe(false);
		});

		test('isExpandable should return true if maxLettersCount < length of text', () => {
			let collapsibleText = new CollapsibleText({
				value: 'hello world',
			});

			expect(collapsibleText.isExpandable()).toBe(false);

			collapsibleText = new CollapsibleText({
				value: '0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567891',
			});

			expect(collapsibleText.isExpandable()).toBe(true);

			collapsibleText = new CollapsibleText({
				value: '01234567890123456789',
				maxLettersCount: 10,
			});

			expect(collapsibleText.isExpandable()).toBe(true);

			collapsibleText = new CollapsibleText({
				value: '01234567890123456789',
				maxLettersCount: 20,
			});

			expect(collapsibleText.isExpandable()).toBe(false);
		});

		test('isExpandable should return true if pos of maxNewLineCount < length of text', () => {
			let collapsibleText = new CollapsibleText({
				value: 'hello world',
			});

			expect(collapsibleText.isExpandable()).toBe(false);

			collapsibleText = new CollapsibleText({
				value: '0123456789\n0123456789\n0123456789',
				maxNewLineCount: 3,
			});

			expect(collapsibleText.isExpandable()).toBe(false);

			collapsibleText = new CollapsibleText({
				value: '0123456789\n0123456789',
				maxNewLineCount: 1,
			});

			expect(collapsibleText.isExpandable()).toBe(true);

			collapsibleText = new CollapsibleText({
				value: '0123456789\n0123456789\n0123456789\n0123456789\n0123456789',
			});

			expect(collapsibleText.isExpandable()).toBe(true);
		});
	});
})();
