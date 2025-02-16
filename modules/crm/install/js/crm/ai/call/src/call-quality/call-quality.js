import { Loc, Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BitrixVue } from 'ui.vue3';
import type { aiCallData } from '../base.js';
import { Base } from '../base.js';
import './style.css';
import { CallQuality as CallQualityComponent } from './components/call-quality';
import { Rating } from './rating';

import 'ui.design-tokens';

/**
 * @memberOf BX.Crm.AI.Call
 *
 */
export class CallQuality extends Base
{
	#layoutComponent: ?Object = null;
	#app: ?Object = null;
	rating: Rating;
	#jobId: ?number;
	#clientDetailUrl: ?string;
	#clientFullName: ?string;
	#activityCreated: ?number;
	#userPhotoUrl: ?string;
	#assessmentSettingsId: ?number;

	constructor(data: aiCallData)
	{
		super(data);

		this.#jobId = Type.isNumber(data.jobId) ? data.jobId : null;
		this.sliderId = `${this.id}-${this.#jobId ?? this.activityId}`;

		this.#clientDetailUrl = Type.isStringFilled(data.clientDetailUrl) ? data.clientDetailUrl : null;
		this.#clientFullName = Type.isStringFilled(data.clientFullName) ? data.clientFullName : null;
		this.#userPhotoUrl = Type.isStringFilled(data.userPhotoUrl) ? data.userPhotoUrl : null;
		this.#activityCreated = Type.isNumber(data.activityCreated) ? data.activityCreated : null;
		this.#assessmentSettingsId = Type.isNumber(data.assessmentSettingsId) ? data.assessmentSettingsId : null;

		this.rating = new Rating();
	}

	initDefaultOptions(): void
	{
		this.id = 'crm-copilot-call-quality';
		this.sliderTitle = Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SLIDER_TITLE');

		const width = Math.round(BX.SidePanel.Instance.getTopSlider().getWidth() * 0.75);
		this.sliderWidth = width > 0 ? width : Math.round(window.screen.width * 0.75);

		this.textboxTitle = Loc.getMessage('CRM_COPILOT_CALL_TRANSCRIPT_TITLE');

		this.aiJobResultAndCallRecordAction = 'crm.timeline.ai.getCopilotCallQuality';
	}

	getExtensions(): Array<string>
	{
		const extensions = super.getExtensions();

		extensions.push('crm.ai.call');

		return extensions;
	}

	getSliderContentClass(): ?string
	{
		return 'crm-copilot-call-quality-wrapper';
	}

	getSliderDesign(): ?Object
	{
		return {
			margin: 0,
		};
	}

	getSliderToolbar(): ?Function
	{
		return () => {
			return [
				this.rating.render(),
			];
		};
	}

	getSliderEvents(): Object
	{
		const events = super.getSliderEvents();

		events.onLoad = () => {
			this.#layoutComponent.showAudioPlayer();
		};

		events.onClose = () => {
			this.#layoutComponent.close();
		};

		return events;
	}

	/**
	 * @override
	 */
	open()
	{
		const content = new Promise((resolve, reject) => {
			this.getAiJobResultAndCallRecord()
				.then((response) => {
					const audioProps = this.prepareAudioProps(response);

					this.#prepareRating(response.data);

					const context = {
						activityId: this.activityId,
						ownerTypeId: this.ownerTypeId,
						ownerId: this.ownerId,
						jobId: this.#jobId,
					};

					this.#app = BitrixVue.createApp(CallQualityComponent, {
						client: {
							detailUrl: this.#clientDetailUrl,
							fullName: this.#clientFullName,
							activityCreated: this.#activityCreated,
						},
						data: response.data,
						audioProps,
						context,
					});

					const container = Tag.render`<div class="call-quality__container"></div>`;
					this.#layoutComponent = this.#app.mount(container);

					EventEmitter.subscribe('crm.ai.callQuality:doAssessment', () => {
						// @todo will the slider close?
						//this.wrapperSlider?.close();
					});

					resolve(container);
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
				jobId: this.#jobId,
				assessmentSettingsId: this.#assessmentSettingsId,
			},
		};

		return BX.ajax.runAction(this.aiJobResultAndCallRecordAction, actionData);
	}

	getNotAccuratePhraseCode(): string
	{
		return 'CRM_COPILOT_CALL_TRANSCRIPT_NOT_BE_ACCURATE';
	}

	#prepareRating({ callQuality }): void
	{
		if (!Type.isPlainObject(callQuality))
		{
			return;
		}

		const { rating } = this;

		if (callQuality)
		{
			rating.setRating(callQuality?.ASSESSMENT_AVG);
			rating.setPrevRating(callQuality?.PREV_ASSESSMENT_AVG);
		}

		if (Type.isStringFilled(this.#userPhotoUrl))
		{
			rating.setUserPhotoUrl(this.#userPhotoUrl);
		}

		rating.setSkeletonMode(false);
	}
}
