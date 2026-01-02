import { BasePopup } from 'humanresources.company-structure.structure-components';
import type { PopupOptions } from 'main.popup';
import { NodeColorsSettingsDict } from 'humanresources.company-structure.utils';

// @vue/component
export const TeamColorPicker = {
	name: 'TeamColorPicker',

	components: {
		BasePopup,
	},

	props: {
		/** @type NodeColorSettingsType */
		modelValue: {
			type: Object,
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
	},

	emits: ['action', 'close', 'update:modelValue'],

	computed:
	{
		popupConfig(): PopupOptions
		{
			const popupWidth = 164;
			const pickerWidth = 41;
			const initialPopupOffset = 39;
			const angleWidth = 33;

			return {
				width: popupWidth,
				height: 116,
				bindElement: this.bindElement,
				borderRadius: 12,
				contentNoPaddings: true,
				contentPadding: 0,
				padding: 0,
				offsetTop: 4,
				offsetLeft: pickerWidth / 2 - popupWidth / 2 + initialPopupOffset,
				angleOffset: popupWidth / 2 - angleWidth / 2,
			};
		},
		nodeColorsSettingsDict(): typeof NodeColorsSettingsDict => NodeColorsSettingsDict
		{
			return NodeColorsSettingsDict;
		},
	},

	methods:
	{
		close(): void
		{
			this.$emit('close');
		},
	},

	template: `
		<BasePopup
			:config="popupConfig"
			:id="'chart_wizard__department__color-picker_popup'"
			@close="close"
		>
			<div
				class="chart_wizard__department__color-picker_popup-container"
				:data-test-id="'wizard-department-color-picker-popup'"
			>
				<div v-for="item in nodeColorsSettingsDict" 
					 class="chart_wizard__department__color-picker_color-item"
					 :class=" {'--active': item.name === modelValue.name }"
					 @click="$emit('update:modelValue', item)"
					 :data-test-id="'wizard-department-color-picker-item-'+item.name"
				>
					<div class="chart_wizard__department__color-picker_color-item_inner"
						 :style="{ 'background-color': item.pickerColor }"
					></div>
				</div>
			</div>
		</BasePopup>
	`,
};
