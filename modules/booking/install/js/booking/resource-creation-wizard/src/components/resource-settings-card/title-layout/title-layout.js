import { BIcon as Icon } from 'ui.icon-set.api.vue';
import './title-layout.css';

export const TitleLayout = {
	name: 'ResourceSettingsCardTitleLayout',
	props: {
		title: {
			type: String,
			required: true,
		},
		iconType: {
			type: String,
			required: true,
		},
	},
	components: {
		Icon,
	},
	template: `
		<div class="resource-creation-wizard__form-settings-title-row">
			<Icon
				:name="iconType"
				:color="'var(--ui-color-primary)'"
				:size="24"
			/>
			<div class="resource-creation-wizard__form-settings-title">
				{{ title }}
			</div>
		</div>
	`,
};
