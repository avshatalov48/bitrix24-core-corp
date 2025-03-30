import { ajax } from 'main.core';
import { Analytics as AnalyticsType, AnalyticsSourceType } from './analytics';
import { memberRoles } from './member-roles';
import { UI } from 'ui.notification';
import { sendData as analyticsSendData } from 'ui.analytics';

const request = async (method: string, endPoint: string, data: Object = {}, analytics: AnalyticsType = {}) => {
	const config = { method };
	if (method === 'POST')
	{
		Object.assign(config, { data }, {
			headers: [{
				name: 'Content-Type',
				value: 'application/json',
			}],
		});
	}

	let response = null;

	try
	{
		if (method === 'POST')
		{
			response = await ajax.runAction(endPoint, config);
		}
		else
		{
			const getConfig = { data };

			response = await ajax.runAction(endPoint, getConfig);
		}
	}
	catch (ex)
	{
		handleResponseError(ex);

		return null;
	}

	if (analytics?.event?.length > 0)
	{
		analyticsSendData(analytics);
	}

	return response.data;
};

const handleResponseError = (response: Error) => {
	if (response.errors?.length > 0)
	{
		const [error] = response.errors;
		if (error.code !== 'STRUCTURE_ACCESS_DENIED')
		{
			throw error;
		}

		UI.Notification.Center.notify({
			content: error.message,
			autoHideDelay: 4000,
		});
	}
};

const getData = (endPoint: string, data: ?Object, analytics: ?AnalyticsType) => request('GET', endPoint, data ?? {}, analytics ?? {});

const postData = (endPoint: string, data: Object, analytics: ?AnalyticsType) => request('POST', endPoint, data, analytics ?? {});

export { getData, postData, memberRoles, AnalyticsSourceType };
