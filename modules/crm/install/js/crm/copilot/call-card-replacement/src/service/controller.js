import { ajax as Ajax } from 'main.core';

export class Controller
{
	#methods = {
		attach: 'crm.copilot.CallAssessment.CallCardPlacement.attachCallAssessment',
		resolve: 'crm.copilot.CallAssessment.CallCardPlacement.resolveCallAssessment',
	};

	async attachCallAssessment(callAssessmentId: number, callId: string): Promise
	{
		const data = {
			data: {
				id: callAssessmentId,
				callId,
			},
		};

		return Ajax.runAction(this.#methods.attach, data);
	}

	async resolveCallAssessment(callId: string): Promise
	{
		const data = {
			data: {
				callId,
			},
		};

		return Ajax.runAction(this.#methods.resolve, data);
	}
}
