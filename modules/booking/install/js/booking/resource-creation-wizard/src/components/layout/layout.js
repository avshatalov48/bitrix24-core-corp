import { UiLoader } from './ui-loader';
import './resource-creation-wizard-layout.css';

export const ResourceCreationWizardLayout = {
	name: 'ResourceCreationWizardLayout',
	props: {
		step: {
			type: Number,
			required: true,
		},
		title: {
			type: String,
			default: '',
		},
		loading: Boolean,
	},
	watch: {
		step(): void
		{
			this.$refs.wrapper?.scrollTo(0, 0);
		},
	},
	components: {
		UiLoader,
	},
	template: `
		<div class="resource-creation-wizard-layout">
			<div ref="wrapper" class="resource-creation-wizard__wrapper">
				<div class="resource-creation-wizard__header">
					<slot name="header">
						<h4 class="resource-creation-wizard__header-title">
							{{ title }}
						</h4>
					</slot>
				</div>
				<div v-show="!loading" class="resource-creation-wizard__content">
					<slot/>
				</div>
				<UiLoader v-if="loading"/>
			</div>
			<slot name="footer"/>
		</div>
	`,
};
