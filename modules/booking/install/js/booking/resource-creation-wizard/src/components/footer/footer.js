import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { NextButton } from './next-button';
import { BackButton } from './back-button';
import { ChooseResourceStep, ResourceNotificationStep, ResourceSettingsStep } from '../../presenter';
import './footer.css';

export const ResourceCreationWizardFooter = {
	name: 'ResourceCreationWizardFooter',
	props: {
		step: {
			type: Number,
			required: true,
		},
		disabled: Boolean,
	},
	data(): Object {
		return {
			steps: [
				new ChooseResourceStep(),
				new ResourceSettingsStep(),
				new ResourceNotificationStep(),
			],
		};
	},
	computed: mapGetters({
		isSaving: `${Model.ResourceCreationWizard}/isSaving`,
	}),
	components: {
		BackButton,
		NextButton,
	},
	template: `
		<div v-if="!steps[step - 1].hidden" class="resource-creation-wizard__footer">
			<NextButton :step :steps :disabled :waiting="isSaving"/>
			<BackButton :step :steps :disabled="isSaving"/>
		</div>
	`,
};
