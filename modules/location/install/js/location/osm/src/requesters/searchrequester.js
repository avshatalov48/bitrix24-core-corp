
import BaseRequester from './baserequester';

export default class SearchRequester extends BaseRequester
{
	createUrl(params: Array): string
	{
		const limit = 5;

		return `${this.serviceUrl}/?
			action=osmgateway.location.search
			&params[q]=${params.query}
			&params[format]=json
			&params[limit]=${limit}
			&params[accept-language]=${this.languageId}`;
	}
}