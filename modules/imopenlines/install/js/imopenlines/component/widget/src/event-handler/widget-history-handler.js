export class WidgetHistoryHandler
{
	store: Object = null;
	application: Object = null;

	constructor($Bitrix)
	{
		this.store = $Bitrix.Data.get('controller').store;
		this.application = $Bitrix.Application.get();
	}

	getHtmlHistory()
	{
		const chatId = this.getChatId();
		if (chatId <= 0)
		{
			console.error('WidgetHistoryHandler: Incorrect chatId value');
		}

		const config = { chatId: this.getChatId() };
		this.requestControllerAction('imopenlines.widget.history.download', config)
			.then(this.handleRequest.bind(this))
			.then(this.downloadHistory.bind(this))
			.catch((error) => console.error('WidgetHistoryHandler: fetch error.', error));
	}

	requestControllerAction(action, config)
	{
		const host = this.application.host ? this.application.host : '';
		const ajaxEndpoint = '/bitrix/services/main/ajax.php';

		const url = new URL(ajaxEndpoint, host);
		url.searchParams.set('action', action);

		const formData = new FormData();
		for (const key in config)
		{
			if (config.hasOwnProperty(key))
			{
				formData.append(key, config[key]);
			}
		}

		return fetch(url, {
			method: 'POST',
			headers: {
				'Livechat-Auth-Id': this.getUserHash()
			},
			body: formData
		});
	}

	handleRequest(response)
	{
		const contentType = response.headers.get('Content-Type');
		if (contentType.startsWith('application/json'))
		{
			return response.json();
		}

		return response.blob();
	}

	downloadHistory(result)
	{
		if (result instanceof Blob)
		{
			const url = window.URL.createObjectURL(result);
			const a = document.createElement('a');
			a.href = url;
			a.download = `${this.getChatId()}.html`;
			document.body.append(a);
			a.click();
			a.remove();
		}
		else if (result.hasOwnProperty('errors'))
		{
			console.error(`WidgetHistoryHandler: ${result.errors[0]}`);
		}
		else
		{
			console.error('WidgetHistoryHandler: unknown error.');
		}
	}

	getChatId()
	{
		return this.store.state.application.dialog.chatId;
	}

	getUserHash()
	{
		return this.store.state.widget.user.hash;
	}

	destroy()
	{
		//
	}
}