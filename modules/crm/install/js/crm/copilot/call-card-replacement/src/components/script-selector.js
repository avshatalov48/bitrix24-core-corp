import { CallAssessmentSelector } from 'crm.copilot.call-assessment-selector';
import { mapGetters } from 'ui.vue3.vuex';
import { PULL } from 'pull.client';

const onBeforeSelectItem = 'Item:onBeforeSelect';

export const ScriptSelector = {
	name: 'ScriptSelector',

	computed: mapGetters(['callAssessment', 'isScriptSelected']),

	beforeMount(): void {
		this.selector = new CallAssessmentSelector({
			currentCallAssessment: this.callAssessment,
			additionalSelectorOptions: {
				dialog: {
					events: {
						onLoad: (): void => {
							this.$store.commit('setCallAssessmentFromSelector', this.selector);
						},
						[onBeforeSelectItem]: (): void => {
							this.$store.commit('setCallAssessmentFromSelector', this.selector);
							this.$store.dispatch('attachCallAssessment');
						},
					},
				},
			},
		});

		this.subscribePull();
	},

	mounted(): void {
		const selectorContainer = this.selector.getContainer();
		this.$refs.selector.append(selectorContainer);
	},

	methods: {
		subscribePull(): void {
			if (!PULL)
			{
				return;
			}

			PULL.subscribe({
				moduleId: 'crm',
				command: 'update_attached_call_assessment_id',
				callback: (eventData: Object): void => {
					this.$store.dispatch('onPullUpdateCallAssessmentId', {
						selector: this.selector,
						eventData,
					});
				},
			});
		},
	},

	template: `
		<div ref="selector" class="crm-copilot__call-card-replacement-selector"></div>
	`,
};
