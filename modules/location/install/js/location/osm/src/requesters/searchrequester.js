
import BaseRequester from './baserequester';

export default class SearchRequester extends BaseRequester
{
	createUrl(params: Array): string
	{
		const limit = 5;

		let result = `${this.serviceUrl}/?
			action=osmgateway.location.search
			&params[q]=${encodeURIComponent(params.query)}
			&params[format]=json
			&params[limit]=${limit}
			&params[accept-language]=${this.languageId}`;

		if(params.viewbox)
		{
			result += `&params[viewbox]=${params.viewbox}`;
		}

		return result;
	}
}