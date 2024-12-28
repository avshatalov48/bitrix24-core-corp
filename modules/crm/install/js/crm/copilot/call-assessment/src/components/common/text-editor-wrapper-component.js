import { Runtime, Type } from 'main.core';
import { TextEditor, TextEditorComponent } from 'ui.text-editor';

export const TextEditorWrapperComponent = {
	components: {
		TextEditorComponent,
	},

	emits: [
		'change',
	],

	props: {
		textEditor: TextEditor,
	},

	data(): Object
	{
		return {
			textEditorEvents: {
				onChange: this.emitChangeData,
			},
		};
	},

	mounted(): void
	{
		const editorOffsetTop = this.$el.parentNode.offsetTop;

		const navigationClassName = '.crm-copilot__call-assessment_navigation-container';
		const navigationOffsetTop = document.querySelector(navigationClassName)?.offsetTop ?? 0;

		const textEditorContainerBottomPadding = 20;
		const availableHeight = navigationOffsetTop - editorOffsetTop - textEditorContainerBottomPadding;
		const minHeight = Math.round(availableHeight * 0.5);
		const maxHeight = Math.round(availableHeight * 0.8);

		if (minHeight < 200)
		{
			this.textEditor?.setMinHeight(maxHeight);
		}
		else
		{
			this.textEditor?.setMinHeight(minHeight);
		}

		this.textEditor?.setMaxHeight(maxHeight);
	},

	methods: {
		emitChangeData(): void
		{
			if (!Type.isFunction(this.onChangeDebounce))
			{
				this.onChangeDebounce = Runtime.debounce(this.onChange, 100, this);
			}

			this.onChangeDebounce();
		},
		onChange(): void
		{
			this.$emit('change', {
				prompt: this.textEditor.getText(),
			});
		},
	},

	template: `
		<TextEditorComponent 
			:events="textEditorEvents"
			:editor-instance="textEditor"
		/>
	`,
};
