import { BIcon as Icon, Outline } from 'ui.icon-set.api.vue';
import { Label, LabelColor, LabelSize } from 'ui.label';

// @vue/component
export const ExtraResourcesInfoPopupItem = {
	name: 'ExtraResourcesInfoPopupItem',
	components: {
		Icon,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		overbooking: {
			type: Boolean,
			required: true,
		},
	},
	setup(): ExtraResourcesInfoPopupItemData
	{
		const iconColor = 'var(--ui-color-accent-main-primary)';
		const iconSize = 16;

		return {
			Outline,
			iconColor,
			iconSize,
		};
	},
	computed: {
		labelHTML(): string {
			const label = new Label({
				color: LabelColor.WARNING,
				size: LabelSize.SM,
				text: this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_OVERBOOKING'),
				fill: true,
			});

			return label.render().outerHTML;
		},
	},
	template: `
		<div class="booking--extra-resources-info_element">
			<div class="booking--extra-resources-info_element-icon —ui-context-content-light">
				<Icon :name="Outline.PRODUCT" :size="iconSize" :color="iconColor"/>
			</div>

			<div class="booking--extra-resources-info_element-text" :title="title">
				{{ title }}
			</div>

			<div 
				v-if="overbooking"
				class="booking--extra-resources-info_element-overbooking"
				 v-html="labelHTML"
			></div>
		</div>
	`,
};

type ExtraResourcesInfoPopupItemData = {
	iconColor: string;
	iconSize: number;
};
