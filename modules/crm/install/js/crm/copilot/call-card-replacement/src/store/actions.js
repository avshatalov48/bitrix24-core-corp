import { Controller } from '../service/controller';
import { ErrorHandler } from '../service/error-handler';
import { prepareCallAssessment } from './functions';
import { type CallAssessmentSelector } from 'crm.copilot.call-assessment-selector';

declare type PullUpdateParameters = {
	selector: CallAssessmentSelector,
	eventData: {
		callId: string,
		callAssessment: ?Object,
		guid: ?string,
	},
};

export default {
	attachCallAssessment({ getters }): void
	{
		if (!getters.isAttachAssessment || !getters.isScriptSelected)
		{
			return;
		}

		const callId = getters.callId;
		const callAssessmentId = getters.callAssessment.id;

		(new Controller())
			.attachCallAssessment(callAssessmentId, callId, getters.guid)
			.catch((response) => (new ErrorHandler()).handleAttachError(response))
		;
	},

	onPullUpdateCallAssessmentId({ getters, commit }, parameters: PullUpdateParameters): void
	{
		const { selector, eventData } = parameters;
		if (eventData.callId !== getters.callId)
		{
			return;
		}

		const { guid } = eventData;
		if (guid === getters.guid)
		{
			return;
		}

		const callAssessment = prepareCallAssessment(eventData.callAssessment);
		if (!callAssessment || getters.callAssessment.id === callAssessment.id)
		{
			return;
		}

		commit('setAttachCallAssessmentEnabled', false);

		commit('setCallAssessment', callAssessment);
		selector.setCurrentCallAssessment(callAssessment);

		commit('setAttachCallAssessmentEnabled', true);
	},
};
