import { Builder, Dictionary } from 'crm.integration.analytics';
import { sendData as sendAnalyticsData } from 'ui.analytics';

export function createSaveAnalyticsBuilder(store): Builder.Automation.AutomatedSolution.CreateEvent
	| Builder.Automation.AutomatedSolution.EditEvent
{
	const builder = store.getters.isNew
		? new Builder.Automation.AutomatedSolution.CreateEvent()
		: new Builder.Automation.AutomatedSolution.EditEvent()
	;

	return builder
		.setId(store.state.automatedSolution.id)
		.setTypeIds(store.state.automatedSolution.typeIds)
	;
}

export function wrapPromiseInAnalytics(promise: Promise, builder): Promise
{
	sendAnalyticsData(
		builder
			.setStatus(Dictionary.STATUS_ATTEMPT)
			.buildData()
		,
	);

	return promise
		.then((thenResult) => {
			sendAnalyticsData(
				builder
					.setStatus(Dictionary.STATUS_SUCCESS)
					.buildData()
				,
			);

			return thenResult;
		})
		.catch((error) => {
			sendAnalyticsData(
				builder
					.setStatus(Dictionary.STATUS_ERROR)
					.buildData()
				,
			);

			throw error;
		})
	;
}
