import { AnalyticsOptions } from 'ui.analytics';
import { Runtime } from 'main.core';
export class Controller
{
	static async sendAnalytics(category: string, url)
	{
		try
		{
			const { sendData } = await Runtime.loadExtension('ui.analytics');

			const sendDataOptions: AnalyticsOptions = {
				event: 'open_list',
				status: 'success',
				tool: 'ai',
				category,
				c_section: 'list',
			};
			sendData(sendDataOptions);
		}
		catch (e)
		{
			console.error('AI: RolesDialog: Can\'t send analytics', e);
		}
		finally
		{
			window.location.href = url;
		}
	}
}
