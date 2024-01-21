import { Loc } from 'main.core';
import { Base } from './base.js';

export class Summary extends Base
{
	initDefaultOptions(): void
	{
		this.id = 'crm-copilot-summary';
		this.aiJobResultAndCallRecordAction = 'crm.timeline.ai.getCopilotSummaryAndCallRecord';

		this.sliderTitle = Loc.getMessage('CRM_COMMON_COPILOT');
		this.sliderWidth = 520;

		this.textboxTitle = Loc.getMessage('CRM_COPILOT_CALL_SUMMARY_TITLE');
	}

	prepareAiJobResult(response: Object): string
	{
		return response.data.aiJobResult.summary;
	}
}
