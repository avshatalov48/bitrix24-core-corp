import 'ui.label';
import { hint } from 'ui.vue3.directives.hint';

export const Label = {
	name: 'ResourceNotificationCardLabel',
	directives: { hint },
	props: {
		text: {
			type: String,
			required: true,
		},
		isChecked: {
			type: Boolean,
			required: true,
		},
	},
	computed: {
		soonHint(): Object
		{
			return {
				text: this.loc('BRCW_SOON_HINT'),
				popupOptions: {
					offsetLeft: 60,
				},
			};
		},
		labelStyles(): string
		{
			return (this.isChecked)
				? 'ui-label ui-label-tag-secondary ui-label-fill notification-label'
				: 'ui-label ui-label-tag-light ui-label-fill notification-label'
			;
		},
	},
	methods: {
		click(): void {},
	},
	template: `
		<div 
			:class="[labelStyles]" 
			v-hint="soonHint" 
			@click="click"
		>
			<div class="ui-label-status"></div>
			<span class="ui-label-inner">{{ text }}</span>
		</div>
	`,
};
