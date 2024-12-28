import type { SpeechConverterErrorEventData, SpeechConverterResultEventData } from 'ai.speech-converter';
import { SpeechConverter, speechConverterEvents } from 'ai.speech-converter';
import { BaseEvent } from 'main.core.events';
import { Set as IconSet } from 'ui.icon-set.api.core';
import { BIcon } from 'ui.icon-set.api.vue';
import { toRaw } from 'ui.vue3';

import 'ui.icon-set.main';

export const CopilotChatVoiceInputBtn = {
	components: {
		BIcon,
	},
	emits: ['input', 'start', 'stop'],
	data(): { converter: ?SpeechConverter } {
		return {
			converter: null,
			isVoiceRecording: false,
		};
	},
	computed: {
		IconSet(): IconSet {
			return IconSet;
		},
		isVoiceInputDisabled(): boolean {
			return SpeechConverter.isBrowserSupport() === false;
		},
	},
	methods: {
		handleClickOnStartVoiceInputBtn(): void {
			if (this.isVoiceRecording)
			{
				this.stopVoiceInput();
			}
			else
			{
				this.startVoiceInput();
			}
		},
		startVoiceInput() {
			const converter: SpeechConverter = toRaw(this.converter);
			converter.start();
		},
		stopVoiceInput(): void {
			const converter: SpeechConverter = toRaw(this.converter);
			converter.stop();
		},
		handleSpeechConverterStartEvent(): void {
			this.$emit('start');
			this.isVoiceRecording = true;
		},
		handleSpeechConverterStopEvent(): void {
			this.isVoiceRecording = false;
			this.$emit('stop');
		},
		handleSpeechConverterResultEvent(e: BaseEvent<SpeechConverterResultEventData>): void {
			this.$emit('input', e.getData().text);
		},
		handleSpeechConverterErrorEvent(e: BaseEvent<SpeechConverterErrorEventData>) {
			console.error(e);
			this.isVoiceRecording = false;
		},
	},
	mounted() {
		if (this.isVoiceInputDisabled === false)
		{
			this.converter = new SpeechConverter({});

			const converter: SpeechConverter = toRaw(this.converter);

			converter.subscribe(speechConverterEvents.start, this.handleSpeechConverterStartEvent);
			converter.subscribe(speechConverterEvents.stop, this.handleSpeechConverterStopEvent);
			converter.subscribe(speechConverterEvents.result, this.handleSpeechConverterResultEvent.bind(this));
			converter.subscribe(speechConverterEvents.error, this.handleSpeechConverterErrorEvent);
		}
	},
	unmounted() {
		const converter: SpeechConverter = toRaw(this.converter);

		converter.unsubscribe(speechConverterEvents.start, this.handleSpeechConverterStartEvent);
		converter.unsubscribe(speechConverterEvents.stop, this.handleSpeechConverterStopEvent);
		converter.unsubscribe(speechConverterEvents.result, this.handleSpeechConverterResultEvent.bind(this));
		converter.unsubscribe(speechConverterEvents.error, this.handleSpeechConverterErrorEvent);
	},
	template: `
		<button
			:disabled="isVoiceInputDisabled"
			@click="handleClickOnStartVoiceInputBtn"
			class="ai__copilot-chat-input_voice-input"
		>
			<span
				v-if="isVoiceRecording === false"
				class="ai__copilot-chat-input_voice-input-no-record-icon-wrapper"
			>
				<BIcon
					:size="24"
					:name="IconSet.MICROPHONE_ON"
				/>
			</span>
			<span
				v-else
				class="ai__copilot-chat-input_voice-input-record-icon-wrapper"
			>
				<span class="ai__copilot-chat-input_voice-input-record-icon"></span>
			</span>
		</button>
	`,
};
