const { test } = require('@playwright/test');
const {
	focusEditor,
	initializeTest,
	assertHTML,
	assertSelection,
	keyDownCtrlOrAlt,
	keyUpCtrlOrAlt,
} = require('./utils');

const { paragraph, text, br, bold, emoji } = require('./html');

const { toggleBold, moveLeft, selectAll, selectCharacters, moveToEditorEnd } = require('./keyboard');

test.describe.parallel('TextEntry', () => {
	test.beforeEach(async ({ page }) => initializeTest({ page }));

	test('Can type \'Hello Lexical\' in the editor', async ({ page }) => {
		await focusEditor(page);
		const targetText = 'Hello Lexical';
		await focusEditor(page);
		await page.keyboard.type(targetText);
		await assertHTML(
			page,
			paragraph('Hello Lexical'),
		);
		await assertSelection(page, {
			anchorOffset: targetText.length,
			anchorPath: [0, 0, 0],
			focusOffset: targetText.length,
			focusPath: [0, 0, 0],
		});
	});

	test('Can insert text and replace it', async ({ page }) => {
		await focusEditor(page);
		await page.locator('[data-lexical-editor]').fill('Front');
		await page.locator('[data-lexical-editor]').fill('Front updated');
		await assertHTML(
			page,
			paragraph('Front updated'),
		);
		await assertSelection(page, {
			anchorOffset: 13,
			anchorPath: [0, 0, 0],
			focusOffset: 13,
			focusPath: [0, 0, 0],
		});
	});

	test('Can insert a paragraph between two text nodes', async ({ page }) => {
		await focusEditor(page);
		await page.keyboard.type('Hello ');
		await toggleBold(page);
		await page.keyboard.type('world');
		await moveLeft(page, 5);
		await page.keyboard.press('Enter');

		await assertHTML(
			page,
			paragraph(text('Hello ') + br() + bold('world')),
		);

		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0, 2, 0],
			focusOffset: 0,
			focusPath: [0, 2, 0],
		});
	});

	test('Can type \'Hello Lexical\' in the editor and replace it with foo', async ({ page }) => {
		await focusEditor(page);

		const targetText = 'Hello Lexical';
		await focusEditor(page);
		await page.keyboard.type(targetText);

		// Select all the text
		await selectAll(page);

		await page.keyboard.type('Foo');

		await assertHTML(
			page,
			paragraph('Foo'),
		);
		await assertSelection(page, {
			anchorOffset: 3,
			anchorPath: [0, 0, 0],
			focusOffset: 3,
			focusPath: [0, 0, 0],
		});
	});

	test('Can type \'Hello Lexical\' in the editor and replace it with an empty space', async ({ page }) => {
		await focusEditor(page);

		const targetText = 'Hello Lexical';
		await page.keyboard.type(targetText);

		// Select all the text
		await selectAll(page);

		await page.keyboard.type(' ');

		await assertHTML(
			page,
			paragraph(' '),
		);
		await assertSelection(page, {
			anchorOffset: 1,
			anchorPath: [0, 0, 0],
			focusOffset: 1,
			focusPath: [0, 0, 0],
		});
	});

	test('Paragraphed text entry and selection', async ({ page }) => {
		await focusEditor(page);
		await page.keyboard.type('Hello World.');
		await page.keyboard.press('Enter');
		await page.keyboard.press('Enter');
		await page.keyboard.type('This is another block.');
		await page.keyboard.down('Shift');
		await moveLeft(page, 6);
		await assertSelection(page, {
			anchorOffset: 22,
			anchorPath: [1, 0, 0],
			focusOffset: 16,
			focusPath: [1, 0, 0],
		});

		await page.keyboard.up('Shift');
		await page.keyboard.type('paragraph.');
		await page.keyboard.type(' :)');

		await assertHTML(
			page,
			paragraph('Hello World.') + paragraph(text('This is another paragraph. ') + emoji() + br()),
			{ ignoreInlineStyles: true },
		);
	});

	test('Can delete characters after they\'re typed', async ({ page }) => {
		await focusEditor(page);
		const text = 'Delete some of these characters.';
		const backspacedText = 'Delete some of these characte';
		await page.keyboard.type(text);
		await page.keyboard.press('Backspace');
		await page.keyboard.press('Backspace');
		await page.keyboard.press('Backspace');

		await assertHTML(
			page,
			paragraph('Delete some of these characte'),
		);
		await assertSelection(page, {
			anchorOffset: backspacedText.length,
			anchorPath: [0, 0, 0],
			focusOffset: backspacedText.length,
			focusPath: [0, 0, 0],
		});
	});

	test('Can type characters, and select and replace a part', async ({ page }) => {
		await focusEditor(page);
		await page.keyboard.type('Hello foobar.');

		await assertHTML(
			page,
			paragraph('Hello foobar.'),
		);

		await moveLeft(page, 7);

		await assertSelection(page, {
			anchorOffset: 6,
			anchorPath: [0, 0, 0],
			focusOffset: 6,
			focusPath: [0, 0, 0],
		});

		await selectCharacters(page, 'right', 3);

		await assertSelection(page, {
			anchorOffset: 6,
			anchorPath: [0, 0, 0],
			focusOffset: 9,
			focusPath: [0, 0, 0],
		});

		await page.keyboard.type('lol');

		await assertHTML(
			page,
			paragraph('Hello lolbar.'),
		);
		await assertSelection(page, {
			anchorOffset: 9,
			anchorPath: [0, 0, 0],
			focusOffset: 9,
			focusPath: [0, 0, 0],
		});
	});

	test('Can select and delete a word', async ({ page, browserName }) => {
		await focusEditor(page);
		const text = 'Delete some of these characters.';
		const backspacedText = 'Delete some of these ';
		await page.keyboard.type(text);
		await keyDownCtrlOrAlt(page);
		await page.keyboard.down('Shift');
		// Chrome stops words on punctuation, so we need to trigger
		// the left arrow key one more time.
		await moveLeft(page, browserName === 'chromium' ? 2 : 1);
		await page.keyboard.up('Shift');
		await keyUpCtrlOrAlt(page);
		// Ensure the selection is now covering the whole word and period.
		await assertSelection(page, {
			anchorOffset: text.length,
			anchorPath: [0, 0, 0],
			focusOffset: backspacedText.length,
			focusPath: [0, 0, 0],
		});

		await page.keyboard.press('Backspace');

		await assertHTML(
			page,
			paragraph('Delete some of these '),
		);
		await assertSelection(page, {
			anchorOffset: backspacedText.length,
			anchorPath: [0, 0, 0],
			focusOffset: backspacedText.length,
			focusPath: [0, 0, 0],
		});
	});

	test('First paragraph backspace handling', async ({ page }) => {
		await focusEditor(page);

		// Add some trimmable text
		await page.keyboard.type('  ');

		// Add paragraph
		await page.keyboard.press('Enter');
		await page.keyboard.press('Enter');

		await assertHTML(
			page,
			paragraph('  ') + paragraph(),
		);
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [1],
			focusOffset: 0,
			focusPath: [1],
		});

		// Move to previous paragraph and press backspace
		await page.keyboard.press('ArrowUp');
		await page.keyboard.press('Backspace');

		await assertHTML(
			page,
			paragraph(),
		);
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0],
			focusOffset: 0,
			focusPath: [0],
		});
	});

	test('Mix of paragraphs and break points', async ({ page }) => {
		await focusEditor(page);

		// Add some line breaks
		await page.keyboard.down('Shift');
		await page.keyboard.press('Enter');
		await page.keyboard.press('Enter');
		await page.keyboard.press('Enter');
		await page.keyboard.up('Shift');

		await assertHTML(
			page,
			paragraph(br(4)),
		);
		await assertSelection(page, {
			anchorOffset: 3,
			anchorPath: [0],
			focusOffset: 3,
			focusPath: [0],
		});

		// Move to top
		await page.keyboard.press('ArrowUp');
		await page.keyboard.press('ArrowUp');
		await page.keyboard.press('ArrowUp');
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0],
			focusOffset: 0,
			focusPath: [0],
		});

		await moveToEditorEnd(page);

		// Add paragraph
		await page.keyboard.press('Enter');

		await assertHTML(
			page,
			paragraph(br(3)) + paragraph(),
		);

		await page.keyboard.press('ArrowUp');
		await page.keyboard.type('هَ');

		await assertHTML(
			page,
			paragraph(br(2) + text('هَ')) + paragraph(),
		);
	});

	test('Empty paragraph and new line node selection', async ({ page }) => {
		await focusEditor(page);

		// Add paragraph
		await page.keyboard.press('Enter');
		await page.keyboard.press('Enter');
		await assertHTML(
			page,
			paragraph() + paragraph(),
		);
		await page.pause();
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [1],
			focusOffset: 0,
			focusPath: [1],
		});

		await page.keyboard.press('ArrowLeft');
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0],
			focusOffset: 0,
			focusPath: [0],
		});

		await page.keyboard.press('ArrowRight');
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [1],
			focusOffset: 0,
			focusPath: [1],
		});

		await page.keyboard.press('ArrowLeft');
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0],
			focusOffset: 0,
			focusPath: [0],
		});

		// Remove paragraph
		await page.keyboard.press('Delete');
		await assertHTML(page, paragraph());
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0],
			focusOffset: 0,
			focusPath: [0],
		});

		// Add line break
		await page.keyboard.down('Shift');
		await page.keyboard.press('Enter');
		await page.keyboard.up('Shift');
		await assertHTML(
			page,
			paragraph(br(2)),
		);
		await assertSelection(page, {
			anchorOffset: 1,
			anchorPath: [0],
			focusOffset: 1,
			focusPath: [0],
		});

		await page.keyboard.press('ArrowLeft');
		await assertHTML(
			page,
			paragraph(br(2)),
		);
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0],
			focusOffset: 0,
			focusPath: [0],
		});

		// Remove line break
		await page.keyboard.press('Delete');
		await assertHTML(
			page,
			paragraph(),
		);
		await assertSelection(page, {
			anchorOffset: 0,
			anchorPath: [0],
			focusOffset: 0,
			focusPath: [0],
		});
	});
});
