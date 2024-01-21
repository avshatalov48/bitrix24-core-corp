import { mapGetters } from 'ui.vue3.vuex';
import '../css/entity-editor-wrapper.css';

export const EntityEditorWrapper = {
	name: 'EntityEditorWrapper',
	computed: {
		...mapGetters([
			'mergeUuid',
		]),
		entityEditorContainerId(): string {
			return `crm-ai-merge-fields__container__${this.mergeUuid}_container`;
		},
	},
	template: '<div v-bind:id="entityEditorContainerId"></div>',
};
