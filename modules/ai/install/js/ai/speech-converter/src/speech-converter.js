import { bind, Loc } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';

type SpeechConverterOptions = {

};

export type SpeechConverterResultEventData = {
	text: string;
};

export type SpeechConverterErrorEventData = {
	error: string;
	message: string;
};

export const speechConverterEvents = Object.freeze({
	result: 'result',
	start: 'start',
	stop: 'stop',
	error: 'error',
});

export class SpeechConverter extends EventEmitter
{
	#speechRecognition: webkitSpeechRecognition;
	#isRecording: boolean;

	constructor(options: SpeechConverterOptions)
	{
		if (SpeechConverter.isBrowserSupport() === false)
		{
			throw new Error('Your browser don\'t support WebSpeechAPI. Please, use last version of Chrome or Safari');
		}

		super();
		this.setEventNamespace('AI:SpeechConverter');

		this.#isRecording = false;

		this.#initSpeechRecognition();
	}

	static isBrowserSupport(): boolean
	{
		return Boolean(window.webkitSpeechRecognition || window.SpeechRecognition);
	}

	start(): void
	{
		this.#speechRecognition.start();
	}

	stop(): void
	{
		this.#speechRecognition.stop();
	}

	isRecording(): boolean
	{
		return this.#isRecording;
	}

	#initSpeechRecognition(): void
	{
		if (window.webkitSpeechRecognition)
		{
			// eslint-disable-next-line new-cap
			this.#speechRecognition = new window.webkitSpeechRecognition();
		}
		else if (window.SpeechRecognition)
		{
			this.#speechRecognition = new window.SpeechRecognition();
		}

		this.#speechRecognition.lang = Loc.getMessage('LANGUAGE_ID') || 'en';
		this.#speechRecognition.continuous = true;
		this.#speechRecognition.interimResults = true;
		this.#speechRecognition.maxAlternatives = 1;

		bind(this.#speechRecognition, 'start', this.#handleStartEvent.bind(this));
		bind(this.#speechRecognition, 'end', this.#handleEndEvent.bind(this));
		bind(this.#speechRecognition, 'error', this.#handleErrorEvent.bind(this));
		bind(this.#speechRecognition, 'result', this.#handleResultEvent.bind(this));
	}

	#handleStartEvent(): void
	{
		this.#isRecording = true;
		this.emit(speechConverterEvents.start);
	}

	#handleEndEvent(): void
	{
		this.#isRecording = false;
		this.emit(speechConverterEvents.stop);
	}

	#handleErrorEvent(e: SpeechRecognitionErrorEvent): void
	{
		const event: BaseEvent<SpeechConverterErrorEventData> = new BaseEvent({
			data: {
				error: e.error,
				message: e.message,
			},
		});

		this.emit(speechConverterEvents.error, event);
	}

	#handleResultEvent(e: SpeechRecognitionEvent): void
	{
		const event: BaseEvent<SpeechConverterResultEventData> = new BaseEvent({
			data: {
				text: this.#getTextFromResults(e.results),
			},
		});

		this.emit(speechConverterEvents.result, event);
	}

	#getTextFromResults(results: SpeechRecognitionResultList): string
	{
		return [...results].reduce((finalResultText: string, currentResult: SpeechRecognitionResult) => {
			const alternative: SpeechRecognitionAlternative = currentResult.item(0);

			return `${finalResultText + alternative.transcript} `;
		}, '');
	}
}
