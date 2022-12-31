import {ButtonState} from "../../enums/button-state";
import {BitrixVue} from 'ui.vue3';
import {BaseButton} from '../baseButton';
import {Loader} from 'main.loader';

export const AdditionalButtonIcon = Object.freeze({
	NOTE: 'note',
	SCRIPT: 'script',
	PRINT: 'print',
	DOTS: 'dots',
});

export const AdditionalButtonColor = Object.freeze({
	DEFAULT: 'default',
	PRIMARY: 'primary',
});

export const AdditionalButton = BitrixVue.cloneComponent(BaseButton, {
	props: {
		iconName: {
			type: String,
			required: false,
			default: '',
			validator(value: string): boolean {
				return Object.values(AdditionalButtonIcon).indexOf(value) > -1;
			},
		},
		color: {
			type: String,
			required: false,
			default: AdditionalButtonColor.DEFAULT,
			validator(value: string)
			{
				return Object.values(AdditionalButtonColor).indexOf(value) > -1;
			},
		},
	},

	computed: {
		className(): Array {
			return [
				'crm-timeline__card_add-button', {
					[`--icon-${this.iconName}`]: this.iconName,
					[`--color-${this.color}`]: this.color,
					[`--state-${this.currentState}`]: this.currentState,
				},
			]
		},

		ButtonState(): ButtonState {
			return ButtonState;
		},

		loaderHtml(): string {
			const loader = new Loader({
				mode: 'inline',
				size: 20,
			});

			loader.show();
			return loader.layout.outerHTML;
		},
	},

	template: `
		<transition name="crm-timeline__card_add-button-fade" mode="out-in">
			<div
				v-if="currentState === ButtonState.LOADING"
				v-html="loaderHtml"
				class="crm-timeline__card_add-button"
			></div>
			<div
				v-else
				:title="title"
				@click="executeAction"
				:class="className">
			</div>
		</transition>
	`
});