import { Controller } from '../service/controller';
import { ErrorHandler } from '../service/error-handler';

export default {
	attachCallAssessment({ getters }): void
	{
		if (!getters.isScriptSelected)
		{
			return;
		}

		const callId = getters.callId;
		const callAssessmentId = getters.callAssessment.id;

		(new Controller())
			.attachCallAssessment(callAssessmentId, callId)
			.catch((response) => (new ErrorHandler()).handleAttachError(response))
		;
	},
};
