import { Loc } from 'main.core';
import { Base } from './base.js';

/**
 * @memberOf BX.Crm.AI.Call
 */
export class Transcription extends Base
{
	initDefaultOptions(): void
	{
		this.id = 'crm-copilot-transcript';
		this.aiJobResultAndCallRecordAction = 'crm.timeline.ai.getCopilotTranscriptAndCallRecord';

		this.sliderTitle = Loc.getMessage('CRM_COMMON_COPILOT');
		this.sliderWidth = 730;

		this.textboxTitle = Loc.getMessage('CRM_COPILOT_CALL_TRANSCRIPT_TITLE');
	}

	getNotAccuratePhraseCode(): string
	{
		return 'CRM_COPILOT_CALL_TRANSCRIPT_NOT_BE_ACCURATE';
	}

	prepareAiJobResult(response: Object): string
	{
		return response.data.aiJobResult.transcription;
	}
}
