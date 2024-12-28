import type { ApplicationState } from '../store';

export default {
	callId(state: ApplicationState): string
	{
		return state.callId;
	},

	callAssessment(state: ApplicationState): { id: ?number, title: ?string, prompt: ?string }
	{
		return state.assessment;
	},

	callAssessmentPrompt(state: ApplicationState): ?string
	{
		return state.assessment.prompt;
	},

	isScriptSelected(state: ApplicationState): boolean
	{
		return state.assessment.id !== null;
	},

	isAttachAssessment(state: ApplicationState): boolean
	{
		return state.isAttachCallAssessment;
	},

	hasAvailableSelectorItems(state: ApplicationState): boolean
	{
		return state.hasAvailableSelectorItems;
	},

	guid(state: ApplicationState): string
	{
		return state.guid;
	},
};
