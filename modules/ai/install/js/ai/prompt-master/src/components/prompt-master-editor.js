import '../css/prompt-master-editor.css';
import { bindOnce, Type, Text, Browser, Dom } from 'main.core';
import { getCursorPosition, setCursorPosition } from '../helpers/content-editable-cursor';

type PromptMasterEditorData = {
	selection: Selection;
}
export const PromptMasterEditor = {
	props: {
		text: {
			type: String,
			required: false,
			default: '',
		},
		placeholder: {
			Type: String,
			required: false,
			default: '',
		},
		maxSymbolsCount: {
			type: Number,
			required: true,
			default: 2500,
		},
		useClarification: {
			type: Boolean,
			required: false,
			default: false,
		},
		isShown: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data(): PromptMasterEditorData {
		return {
			selection: null,
			symbolsCount: this.text.length,
			editorInFocus: false,
			cursorPosition: 0,
		};
	},
	computed: {
		textContent(): string {
			return this.convertTextToHtml(this.text);
		},
		symbolCounterClassname(): Object {
			return {
				'ai__prompt-master-editor_symbols-counter': true,
				'--error': this.symbolsCount > this.maxSymbolsCount,
			};
		},
		clarificationBtnClassname(): Object {
			return {
				'ai__prompt-master-editor_clarification-btn': true,
				'--disabled': this.editorInFocus === false,
			};
		},
	},
	methods: {
		formatEditorContent(): void
		{
			this.$refs.editor.innerHTML = this.textContent;
		},
		convertTextToHtml(str: string): string {
			if (Type.isStringFilled(str) === false)
			{
				return '';
			}

			const stringWithoutDoubleLineBreaks = Text.encode(str);

			const lines = stringWithoutDoubleLineBreaks.split('\n');

			if (lines.every((line) => line === ''))
			{
				lines.shift();
			}

			let resultHtmlString = lines.map((line: string, index: number): string => {
				if (index === 0 && line !== '')
				{
					return line;
				}

				return `<div>${line === '' ? '<br>' : line}</div>`;
			}).join('');

			if (this.useClarification)
			{
				resultHtmlString = resultHtmlString
					.replaceAll('<strong>', '')
					.replaceAll('</strong>', '')
					.replaceAll('[', '<strong>[')
					.replaceAll(']', ']</strong>')
				;
			}

			return resultHtmlString;
		},
		checkSelection(): void {
			requestAnimationFrame(() => {
				if (window.getSelection().toString())
				{
					this.selection = window.getSelection();
				}
				else
				{
					this.selection = null;
				}
			});
		},
		addClarification(): void {
			if (this.editorInFocus === false)
			{
				return;
			}

			const selection = window.getSelection();
			if (!selection.rangeCount)
			{
				return;
			}

			const cursorPosition = this.getEditorCursorPosition();

			const range = selection.getRangeAt(0);
			const strong = document.createElement('strong');
			strong.textContent = `[ ${this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_CLARIFICATION')} ]`;

			if (range.startContainer.nodeName === 'BR')
			{
				const parent: HTMLElement = range.startContainer.parentNode;
				parent.children[0].remove();
				range.setStart(parent, 0);
				range.setEnd(parent, 0);
			}

			range.deleteContents();
			range.insertNode(strong);

			bindOnce(window, 'mouseup', () => {
				this.focusEditor();
				this.setEditorCursorPosition(cursorPosition + strong.textContent.length);
			});

			this.$emit('input-text', this.getEditorContent());
		},
		handleInput(e: InputEvent): boolean {
			// hack for :empty with dynamic content in Safari
			Dom.style(this.$refs.placeholder, 'display', 'initial');
			Dom.style(this.$refs.placeholder, 'display', null);

			if (e.inputType !== 'insertCompositionText')
			{
				this.$emit('input-text', this.getEditorContent());
			}

			return true;
		},
		getEditorContent(): string {
			if (Browser.isSafari())
			{
				return this.$refs.editor.innerText.trimEnd();
			}

			return this.$refs.editor.innerText
				.replaceAll('\r\n', '\n\n')
				.replaceAll('\n\n', '\n')
			;
		},
		focusEditor(): void {
			this.setEditorCursorPosition(999);
			this.cursorPosition = 999;
			this.$refs.editor.focus();
		},
		handlePaste(event): boolean
		{
			event.preventDefault();

			let text = (event.clipboardData || window.clipboardData).getData('text/plain');

			text = text
				.replaceAll('\r\n', '\n')
				.replaceAll('\n', '\n\n')
			;

			const selection = window.getSelection();
			if (!selection.rangeCount)
			{
				return false;
			}

			selection.deleteFromDocument();

			const textNode = document.createTextNode(text);

			const range = selection.getRangeAt(0);

			if (range.startContainer.nodeName === 'BR')
			{
				const parent: HTMLElement = range.startContainer.parentNode;
				parent.children[0].remove();
				range.setStart(parent, 0);
				range.setEnd(parent, 0);
			}

			range.insertNode(textNode);

			range.setStartAfter(textNode);
			range.setEndAfter(textNode);
			selection.removeAllRanges();
			selection.addRange(range);

			this.cursorPosition = this.getEditorCursorPosition();

			this.$emit('input-text', this.getEditorContent());

			return true;
		},
		updateSymbolsCounter(): void
		{
			this.symbolsCount = this.getEditorContent().length;
		},
		handleFocus(): void
		{
			this.editorInFocus = true;
		},
		handleBlur(): void
		{
			this.editorInFocus = false;
		},
		handleKeyDown(e: KeyboardEvent): boolean
		{
			if (e.shiftKey && e.code === 'Enter')
			{
				e.preventDefault();

				return false;
			}

			if (e.metaKey || e.ctrlKey)
			{
				const allowedKeysWithCtrlAndMeta = ['KeyA', 'KeyZ', 'KeyC', 'KeyV', 'KeyR', 'KeyX', 'KeyR', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Backspace'];
				const key = e.code;
				if (allowedKeysWithCtrlAndMeta.includes(key) === false)
				{
					e.preventDefault();

					return false;
				}
			}

			return true;
		},
		handleCompositionEndEvent(): void
		{
			this.$emit('input-text', this.getEditorContent());
		},
		getEditorCursorPosition(): number
		{
			return getCursorPosition(this.$refs.editor);
		},
		setEditorCursorPosition(position: number): void
		{
			return setCursorPosition(this.$refs.editor, position);
		},
	},
	watch: {
		text() {
			this.cursorPosition = this.getEditorCursorPosition();
			this.formatEditorContent();
			if (this.editorInFocus === false)
			{
				this.focusEditor();
			}

			this.setEditorCursorPosition(this.cursorPosition);
			this.updateSymbolsCounter();
		},
		isShown(newValue: boolean, oldValue: boolean): void {
			if (newValue === true && oldValue === false)
			{
				requestAnimationFrame(() => {
					this.formatEditorContent();
					this.focusEditor();
				});
			}
		},
	},
	mounted() {
		this.focusEditor();
	},
	template: `
		<div
			class="ai__prompt-master-editor-wrapper"
		>
			<div class="ai__prompt-master-editor-inner">
				<div
					v-once
					@mouseup="checkSelection"
					class="ai__prompt-master-editor"
					contenteditable="true"
					ref="editor"
					@input="handleInput"
					@compositionend="handleCompositionEndEvent"
					@paste="handlePaste"
					@focus="handleFocus"
					@blur="handleBlur"
					@keydown="handleKeyDown"
					v-html="textContent"
				>
				</div>
				<span ref="placeholder" class="ai__prompt-master-editor-inner_placeholder">
					{{ placeholder ? placeholder : $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_PLACEHOLDER') }}
				</span>
				<div
					v-if="useClarification"
					:aria-disabled="editorInFocus"
					@mousedown="addClarification"
					:class="clarificationBtnClassname"
				>
					<span class="ai__prompt-master-editor_clarification-btn-icon"></span>
					<span class="ai__prompt-master-editor_clarification-btn-text">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_ADD_CLARIFICATION') }}
				</span>
				</div>
			</div>

			<div :class="symbolCounterClassname">
				<span class="ai__prompt-master-editor_symbols-counter-current">
					{{ symbolsCount }}
				</span>
				/
				<span class="ai__prompt-master-editor_symbols-counter-total">
					{{ maxSymbolsCount }}
				</span>
			</div>
		</div>
	`,
};
