import { ajax as Ajax } from 'main.core';

export class Controller
{
	#methods = {
		attach: 'crm.copilot.CallAssessment.CallCardPlacement.attachCallAssessment',
		detach: 'crm.copilot.CallAssessment.CallCardPlacement.detachCallAssessment',
		resolve: 'crm.copilot.CallAssessment.CallCardPlacement.resolveCallAssessment',
	};

	async attachCallAssessment(callAssessmentId: number, callId: string, guid: string): Promise
	{
		const data = {
			data: {
				id: callAssessmentId,
				callId,
				guid,
			},
		};

		return Ajax.runAction(this.#methods.attach, data);
	}

	async detachCallAssessment(callId: string): Promise
	{
		const data = {
			data: {
				callId,
			},
		};

		return Ajax.runAction(this.#methods.detach, data);
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
