import { Loc, Dom } from 'main.core';
import { CopilotChatVoiceInputBtn } from './copilot-chat-voice-input-btn';
import 'ui.icon-set.main';

import '../css/copilot-chat-input.css';

export const CopilotChatInput = {
	components: {
		CopilotChatVoiceInputBtn,
	},
	emits: ['submit'],
	props: {
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		placeholder: {
			type: String,
			required: false,
			default: Loc.getMessage('AI_COPILOT_CHAT_INPUT_PLACEHOLDER'),
		},
	},
	data(): {
		userMessage: string,
		userMessageBeforeVoiceInput: string,
		isRecording: boolean,
		} {
		return {
			userMessage: '',
			userMessageBeforeVoiceInput: this.userMessage,
			isRecording: false,
		};
	},
	computed: {
		isSubmitButtonDisabled(): boolean {
			return !this.userMessage || this.userMessage.trim().length === 0 || this.isRecording;
		},
		containerClassname(): string {
			return {
				'ai__copilot-chat-input': true,
				'--disabled': this.disabled,
			};
		},
	},
	mounted() {
		setTimeout(() => {
			this.$refs.textarea.focus();
		}, 500);
	},
	methods: {
		handleSubmitButton(e: PointerEvent): void {
			e.target.blur();

			this.submitMessage();
		},
		handleEnterKeyDown(e: KeyboardEvent): boolean {
			if (e.shiftKey || e.ctrlKey)
			{
				return true;
			}

			e.preventDefault();

			if (this.userMessage?.trim())
			{
				this.submitMessage();
			}

			return false;
		},
		submitMessage(): void {
			this.$emit('submit', this.userMessage.trim());

			this.userMessage = '';
		},
		handleInput(e: InputEvent): void {
			this.userMessage = e.target.value;
		},
		handleVoiceInputText(text: string): void {
			this.userMessage = this.userMessageBeforeVoiceInput + text;

			this.updateTextareaHeight();
		},
		handleStartVoiceInput(): void {
			this.isRecording = true;
			if (this.userMessage && this.userMessage.at(-1) !== ' ')
			{
				this.userMessage += ' ';
			}

			this.userMessageBeforeVoiceInput = this.userMessage;
		},
		handleStopVoiceInput(): void {
			this.isRecording = false;
			this.$refs.textarea.focus();
		},
		updateTextareaHeight(): void {
			const textarea: HTMLElement = this.$refs.textarea;
			Dom.style(textarea, 'height', 'auto');

			Dom.style(textarea, 'height', `${textarea.scrollHeight}px`);
		},
	},
	watch: {
		userMessage(): void {
			requestAnimationFrame(() => {
				this.updateTextareaHeight();
			});
		},
		disabled(isDisabled): void {
			if (isDisabled === false)
			{
				this.$refs.textarea.focus();
			}
			else
			{
				this.$refs.textarea.blur();
			}
		},
	},
	template: `
		<div :class="containerClassname">
			<div class="ai__copilot-chat-input_textarea-wrapper">
				<textarea
					type="text" class="ai__copilot-chat-input_textarea"
					ref="textarea"
					:placeholder="placeholder"
					rows="1"
					@input="handleInput"
					@keydown.enter="handleEnterKeyDown"
					:value="userMessage"
				/>
			</div>
			<div class="ai__copilot-chat-input_actions">
				<CopilotChatVoiceInputBtn
					@start="handleStartVoiceInput"
					@input="handleVoiceInputText"
					@stop="handleStopVoiceInput"
				/>
				<button @click="handleSubmitButton" :disabled="isSubmitButtonDisabled" class="ai__copilot-chat-input_submit"></button>
			</div>
		</div>
	`,
};
