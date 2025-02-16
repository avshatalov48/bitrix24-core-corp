import { Type } from 'main.core';
import { PULL } from 'pull.client';
import { QueueManager } from 'pull.queuemanager';

const CALL_SCORING_ADD_COMMAND = 'call_scoring_add';
const CALL_ASSESSMENT_UPDATE_COMMAND = 'call_assessment_update';

export class Pull
{
	#callScoringCallback: Function;
	#callAssessmentCallback: Function;
	#unsubscribeFromCallScoring: ?Function = null;
	#unsubscribeFromCallAssessment: ?Function = null;

	constructor(callScoringCallback: Function, callAssessmentCallback: Function)
	{
		this.#callScoringCallback = callScoringCallback;
		this.#callAssessmentCallback = callAssessmentCallback;
	}

	init(): void
	{
		if (!PULL)
		{
			console.error('pull is not initialized');

			return;
		}

		// @todo use only one subscribe with many actions in callback
		this.#unsubscribeFromCallScoring = PULL.subscribe({
			moduleId: 'crm',
			command: CALL_SCORING_ADD_COMMAND,
			callback: (params) => {
				if (Type.isStringFilled(params.eventId) && QueueManager.eventIds.has(params.eventId))
				{
					return;
				}

				this.#callScoringCallback(params);
			},
		});

		this.#unsubscribeFromCallAssessment = PULL.subscribe({
			moduleId: 'crm',
			command: CALL_ASSESSMENT_UPDATE_COMMAND,
			callback: (params) => {
				if (Type.isStringFilled(params.eventId) && QueueManager.eventIds.has(params.eventId))
				{
					return;
				}

				this.#callAssessmentCallback(params);
			},
		});

		PULL.extendWatch(CALL_SCORING_ADD_COMMAND);
		PULL.extendWatch(CALL_ASSESSMENT_UPDATE_COMMAND);
	}

	unsubscribe(): void
	{
		this.#unsubscribeFromCallScoring();
		this.#unsubscribeFromCallAssessment();
	}
}
