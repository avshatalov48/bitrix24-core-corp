import { Tag } from 'main.core';
import { UI } from 'ui.notification';
import { Slider } from 'crm.ai.slider';
import { Textbox } from 'crm.ai.textbox';
import { AudioPlayer } from 'crm.audio-player';

export class Base
{
	id: string;
	sliderTitle: string;
	sliderWidth: number;
	textboxTitle: string;
	aiJobResultAndCallRecordAction: string;

	wrapperSlider: Slider;
	audioPlayerApp: AudioPlayer;
	textbox: Textbox;

	constructor(data)
	{
		this.initDefaultOptions();

		this.activityId = data.activityId;
		this.ownerTypeId = data.ownerTypeId;
		this.ownerId = data.ownerId;

		const audioPlayerNode = Tag.render`<div id="crm-textbox-audio-player"></div>`;

		this.audioPlayerApp = new AudioPlayer({
			rootNode: audioPlayerNode,
		});

		this.textbox = new Textbox({
			title: this.textboxTitle,
			previousTextContent: audioPlayerNode,
		});

		this.sliderId = `${this.id}-${Math.floor(Math.random() * 1000)}`;
		this.wrapperSlider = new Slider({
			url: this.sliderId,
			sliderTitle: this.sliderTitle,
			width: this.sliderWidth,
			extensions: ['crm.ai.textbox', 'crm.audio-player'],
			events: {
				onLoad: () => {
					this.audioPlayerApp.attachTemplate();
				},

				onClose: () => {
					this.audioPlayerApp.detachTemplate();
				},
			},
		});
	}

	open()
	{
		const content = new Promise((resolve, reject) => {
			this.getAiJobResultAndCallRecord()
				.then((response) => {
					const audioProps = this.prepareAudioProps(response);
					this.audioPlayerApp.setAudioProps(audioProps);

					const aiJobResult = this.prepareAiJobResult(response);
					this.textbox.setText(aiJobResult);
					this.textbox.render();

					resolve(this.textbox.get());
				})
				.catch((response) => {
					this.showError(response);
					this.wrapperSlider.destroy();
				})
			;
		});

		this.wrapperSlider.setContent(content);
		this.wrapperSlider.open();
	}

	getAiJobResultAndCallRecord(): Promise
	{
		const actionData = {
			data: {
				activityId: this.activityId,
				ownerTypeId: this.ownerTypeId,
				ownerId: this.ownerId,
			},
		};

		return BX.ajax.runAction(this.aiJobResultAndCallRecordAction, actionData);
	}

	showError(response)
	{
		UI.Notification.Center.notify({
			content: response.errors[0].message,
			autoHideDelay: 5000,
		});
	}

	prepareAiJobResult(response: Object): string
	{
		return '';
	}

	prepareAudioProps(response: Object): Object
	{
		const callRecord = response.data.callRecord;

		return {
			id: callRecord.id,
			src: callRecord.src,
			title: callRecord.title,
			context: window.top,
		};
	}

	getSliderTitle(): string
	{
		return '';
	}

	getTextboxTitle(): string
	{
		return '';
	}

	initDefaultOptions(): void {}
}
