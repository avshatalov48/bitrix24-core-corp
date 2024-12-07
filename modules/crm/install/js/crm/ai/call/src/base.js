import { Tag, Loc, Type, Text } from 'main.core';
import { UI } from 'ui.notification';
import { Slider } from 'crm.ai.slider';
import { Textbox, Attention, AttentionPresets } from 'crm.ai.textbox';
import { AudioPlayer } from 'crm.audio-player';

declare type aiCallData = {
	activityId: number,
	ownerTypeId: number,
	ownerId: number,
	languageTitle ?: string,
};

export class Base
{
	activityId: number;
	ownerTypeId: number;
	ownerId: number;
	languageTitle: ?string = null;

	id: string;
	sliderTitle: string;
	sliderWidth: number;
	textboxTitle: string;
	aiJobResultAndCallRecordAction: string;

	wrapperSlider: Slider;
	audioPlayerApp: AudioPlayer;
	textbox: Textbox;

	constructor(data: aiCallData)
	{
		this.initDefaultOptions();

		this.activityId = data.activityId;
		this.ownerTypeId = data.ownerTypeId;
		this.ownerId = data.ownerId;
		this.languageTitle = data.languageTitle ?? null;

		const audioPlayerNode = Tag.render`<div id="crm-textbox-audio-player"></div>`;

		this.audioPlayerApp = new AudioPlayer({
			rootNode: audioPlayerNode,
		});

		this.textbox = new Textbox({
			title: this.textboxTitle,
			previousTextContent: audioPlayerNode,
			attentions: this.getTextboxAttentions(),
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

	getTextboxAttentions(): Array
	{
		const attentions = [this.getNotAccurateAttention()];

		const jobLanguageAttention = this.getJobLanguageAttention();
		if (jobLanguageAttention !== null)
		{
			attentions.push(jobLanguageAttention);
		}

		return attentions;
	}

	getNotAccurateAttention(): Attention
	{
		const helpdeskCode = '20412666';

		const content = Loc.getMessage(this.getNotAccuratePhraseCode(), {
			'[helpdesklink]': `<a href="##" onclick="top.BX.Helper.show('redirect=detail&code=${helpdeskCode}');">`,
			'[/helpdesklink]': '</a>',
		});

		return new Attention({
			content,
		});
	}

	getJobLanguageAttention(): ?Attention
	{
		if (!Type.isStringFilled(this.languageTitle))
		{
			return null;
		}

		const helpdeskCode = '20423978';
		const content = Loc.getMessage('CRM_COPILOT_CALL_JOB_LANGUAGE_ATTENTION', {
			'#LANGUAGE_TITLE#': `<span style="text-transform: lowercase">${Text.encode(this.languageTitle)}</span>`,
			'[helpdesklink]': `<a href="##" onclick="top.BX.Helper.show('redirect=detail&code=${helpdeskCode}');">`,
			'[/helpdesklink]': '</a>',
		});

		return new Attention({
			preset: AttentionPresets.COPILOT,
			content,
		});
	}

	getNotAccuratePhraseCode(): string
	{
		return '';
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
