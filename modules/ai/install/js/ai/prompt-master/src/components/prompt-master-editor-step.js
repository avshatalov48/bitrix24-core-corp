import { Extension, Runtime } from 'main.core';
import { PromptMasterEditor } from './prompt-master-editor';
import { BIcon } from 'ui.icon-set.api.vue';
import { Actions } from 'ui.icon-set.api.core';

import '../css/prompt-master-editor-step.css';

const language = Extension.getSettings('ai.prompt-master').language ?? 'en';

export const PromptMasterEditorStep = {
	props: {
		promptText: {
			type: String,
			required: false,
			default: '',
		},
		useClarification: {
			type: Boolean,
			required: false,
			default: false,
		},
		maxSymbolsCount: {
			type: Number,
			required: true,
			default: 2500,
		},
		isShown: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	components: {
		PromptMasterEditor,
		BIcon,
	},
	data(): { isInfoSliderShown: boolean } {
		return {
			isInfoSliderShown: false,
			animation: null,
		};
	},
	computed: {
		closeInfoSliderIcon(): string {
			return Actions.CROSS_30;
		},
		instructionSecondStepText(): string {
			return this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_STEP_2', {
				'#ICON#': '<span class="ai__prompt-master_about-editor-slider-step-clarification-icon"></span>',
			});
		},
		editorPlaceholder(): string {
			return this.useClarification
				? this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_PLACEHOLDER_FOR_TEMPLATE_PROMPT')
				: this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_PLACEHOLDER_FOR_DEFAULT_PROMPT')
			;
		},
	},
	methods: {
		openInfoSlider(): void {
			this.isInfoSliderShown = true;
		},
		closeInfoSlider(): void {
			this.isInfoSliderShown = false;
		},
		handleInput(text: string): void {
			this.$emit('input-text', text);
		},
		async loadLottieAnimation(): Promise<void> {
			const { Lottie } = await Runtime.loadExtension('ui.lottie');

			const path = language.toLowerCase() === 'ru'
				? '/bitrix/js/ai/prompt-master/lottie/insert-text-guide-ru.json'
				: '/bitrix/js/ai/prompt-master/lottie/insert-text-guide-en.json';

			this.animation = Lottie.loadAnimation({
				path,
				container: this.$refs.guideContainer,
				loop: true,
				autoplay: true,
				renderer: 'svg',
				rendererSettings: {
					viewBoxOnly: true,
				},
			});
		},
	},
	watch: {
		async isInfoSliderShown(): Promise<void> {
			if (this.isInfoSliderShown)
			{
				if (this.animation)
				{
					this.animation.play();

					return;
				}

				await this.loadLottieAnimation();
			}
			else
			{
				this.animation?.stop();
			}
		},
	},
	template: `
		<div class="ai__prompt-master_prompt-step">
			<div class="ai__prompt-master_prompt-step-editor">
				<PromptMasterEditor
					:is-shown="isShown"
					:text="promptText"
					:use-clarification="useClarification"
					:max-symbols-count="maxSymbolsCount"
					:placeholder="editorPlaceholder"
					@input-text="handleInput"
				/>
			</div>
			<div
				v-if="useClarification"
				class="ai__prompt-master_prompt-step-more-details"
			>
				<span @click="openInfoSlider" class="ai__prompt-master_more-details">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_MORE') }}
				</span>
			</div>
			<transition>
				<div
					v-show="isInfoSliderShown"
					@click="closeInfoSlider"
					class="ai__prompt-master_about-editor-slider-wrapper"
				>
					<div class="ai__prompt-master_about-editor-slider">
						<header class="ai__prompt-master_about-editor-slider__header">
							<h4 class="ai__prompt-master_about-editor-slider__title">
								{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_TITLE') }}
							</h4>
							<div
								@click="closeInfoSlider"
								class="ai__prompt-master_about-editor-slider__close-icon"
							>
								<BIcon :name="closeInfoSliderIcon" :size="20"></BIcon>
							</div>
						</header>
						<main class="ai__prompt-master_about-editor-slider__main">
							<div ref="guideContainer" class="ai__prompt-master_about-editor-slider__video-wrapper"
							></div>
							<ul class="ai__prompt-master_about-editor-slider-steps">
								<li class="ai__prompt-master_about-editor-slider-step">
									{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_STEP_1') }}
								</li>
								<li class="ai__prompt-master_about-editor-slider-step">
									<span v-html="instructionSecondStepText"></span>
								</li>
								<li class="ai__prompt-master_about-editor-slider-step">
									{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_STEP_3') }}
								</li>
							</ul>
						</main>
					</div>
				</div>
			</transition>
		</div>
	`,
};
