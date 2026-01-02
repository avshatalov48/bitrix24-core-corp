import { CallAssessmentSelector } from 'crm.copilot.call-assessment-selector';
import { mapGetters } from 'ui.vue3.vuex';
import { PULL } from 'pull.client';
import { SelectorLayout } from '../service/selector-layout';

const onBeforeSelectItem = 'Item:onBeforeSelect';

export const ScriptSelector = {
	name: 'ScriptSelector',

	computed: mapGetters(['callAssessment', 'isScriptSelected', 'callId']),

	beforeMount(): void {
		this.selector = new CallAssessmentSelector({
			id: this.callId,
			currentCallAssessment: this.callAssessment,
			displayStrategy: new SelectorLayout(),
			events: {
				onCallAssessmentUpdate: (eventData: Object): void => {
					const { callAssessment } = eventData.data;
					if (this.callAssessment.id !== callAssessment?.id)
					{
						return;
					}

					this.$store.commit('setCallAssessment', callAssessment);
				},
			},
			additionalSelectorOptions: {
				dialog: {
					events: {
						onLoad: (): void => {
							this.$store.commit('setCallAssessmentFromSelector', this.selector);
						},
						[onBeforeSelectItem]: (): void => {
							this.$store.commit('setCallAssessmentFromSelector', this.selector);
							if (!this.selector.isSelectByPull())
							{
								this.$store.dispatch('attachCallAssessment');
							}
						},
					},
				},
			},
		});
	},

	mounted(): void {
		const selectorContainer = this.selector.getContainer();
		this.$refs.selector.append(selectorContainer);
	},

	template: `
		<div ref="selector" class="crm-copilot__call-card-replacement-selector"></div>
	`,
};
