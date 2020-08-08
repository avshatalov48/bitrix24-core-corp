/**
 * Bitrix OpenLines widget
 * Rest client (base on BX.RestClient)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

// TODO change BX.RestClient, BX.promise to import

import {Utils} from "im.lib.utils";
import {RestAuth, RestMethod} from "../const";
import {RestClient} from "rest.client";

export class WidgetRestClient
{
	constructor(params)
	{
		this.queryAuthRestore = false;

		this.setAuthId(RestAuth.guest);

		this.restClient = new RestClient({
			endpoint: params.endpoint,
			queryParams: this.queryParams,
			cors: true
		});
	}

	setAuthId(authId, customAuthId = '')
	{
		if (typeof this.queryParams !== 'object')
		{
			this.queryParams = {};
		}

		if (
			authId == RestAuth.guest
			|| typeof authId === 'string' && authId.match(/^[a-f0-9]{32}$/)
		)
		{
			this.queryParams.livechat_auth_id = authId;
		}
		else
		{
			console.error(`%LiveChatRestClient.setAuthId: auth is not correct (%c${authId}%c)`, "color: black;", "font-weight: bold; color: red", "color: black");
			return false;
		}

		if (
			authId == RestAuth.guest
			&& typeof customAuthId === 'string' && customAuthId.match(/^[a-f0-9]{32}$/)
		)
		{
			this.queryParams.livechat_custom_auth_id = customAuthId;
		}

		return true;
	}

	getAuthId()
	{
		if (typeof this.queryParams !== 'object')
		{
			this.queryParams = {};
		}

		return this.queryParams.livechat_auth_id || null;
	}

	callMethod(method, params, callback, sendCallback, logTag = null)
	{
		if (!logTag)
		{
			logTag = Utils.getLogTrackingParams({
				name: method,
			});
		}

		const promise = new BX.Promise();

		// TODO: Callbacks methods will not work!
		this.restClient.callMethod(method, params, null, sendCallback, logTag).then(result => {

			this.queryAuthRestore = false;
			promise.fulfill(result);

		}).catch(result => {

			let error = result.error();
			if (error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER')
			{
				this.setAuthId(error.ex.hash);

				if (method === RestMethod.widgetUserRegister)
				{
					console.warn(`BX.LiveChatRestClient: ${error.ex.error_description} (${error.ex.error})`);

					this.queryAuthRestore = false;
					promise.reject(result);
					return false;
				}

				if (!this.queryAuthRestore)
				{
					console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');

					this.queryAuthRestore = true;
					this.restClient.callMethod(method, params, null, sendCallback, logTag).then(result => {
						this.queryAuthRestore = false;
						promise.fulfill(result);
					}).catch(result => {
						this.queryAuthRestore = false;
						promise.reject(result);
					});

					return false;
				}
			}

			this.queryAuthRestore = false;
			promise.reject(result);
		});

		return promise;
	};

	callBatch(calls, callback, bHaltOnError, sendCallback, logTag)
	{
		let resultCallback = (result) =>
		{
			let error = null;
			for (let method in calls)
			{
				if (!calls.hasOwnProperty(method))
				{
					continue;
				}

				let error = result[method].error();
				if (error && error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER')
				{
					this.setAuthId(error.ex.hash);
					if (method === RestMethod.widgetUserRegister)
					{
						console.warn(`BX.LiveChatRestClient: ${error.ex.error_description} (${error.ex.error})`);

						this.queryAuthRestore = false;
						callback(result);
						return false;
					}

					if (!this.queryAuthRestore)
					{
						console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');

						this.queryAuthRestore = true;
						this.restClient.callBatch(calls, callback, bHaltOnError, sendCallback, logTag);

						return false;
					}
				}
			}

			this.queryAuthRestore = false;
			callback(result);

			return true;
		};

		return this.restClient.callBatch(calls, resultCallback, bHaltOnError, sendCallback, logTag);
	};
}