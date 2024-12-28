/* eslint no-param-reassign: off */
import type { ApplicationState } from '../store';
import type { CallAssessmentSelector } from 'crm.copilot.call-assessment-selector';
import type { ReplacementOptions } from '../app';
import { Type } from 'main.core';
import { prepareCallAssessment } from './functions';

export default {
	initializeApplicationState(state: ApplicationState, options: ReplacementOptions): void
	{
		if (!Type.isStringFilled(options.callId))
		{
			throw new TypeError('options.callId must be filled');
		}

		const callAssessment = options.callAssessment ?? {};

		state.callId = options.callId;
		state.assessment = prepareCallAssessment(callAssessment);
		state.hasAvailableSelectorItems = options.hasAvailableSelectorItems ?? true;
		state.isAttachCallAssessment = true;
	},

	setCallAssessmentFromSelector(state: ApplicationState, selector: CallAssessmentSelector): void
	{
		const item = selector.getCurrentCallAssessmentItem();
		if (item === null)
		{
			return;
		}

		state.assessment = prepareCallAssessment(item);
	},

	setCallAssessment(state: ApplicationState, assessment: Object): void
	{
		state.assessment = prepareCallAssessment(assessment);
	},

	setAttachCallAssessmentEnabled(state: ApplicationState, isAttach: boolean): void
	{
		state.isAttachCallAssessment = isAttach;
	},
};
