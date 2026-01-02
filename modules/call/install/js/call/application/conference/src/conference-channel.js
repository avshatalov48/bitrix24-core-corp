import { DesktopApi } from 'im.v2.lib.desktop-api';
import { CallMultiChannel } from 'call.core';

class ConferenceChannel
{
	static #instance;
	constructor()
	{
		this.callMultiBroadcastClient = new CallMultiChannel('call_conf_controller_multi_channel');
	}

	static getInstance(): ConferenceChannel
	{
		if (!this.#instance)
		{
			this.#instance = new this();
		}

		return this.#instance;
	}

	async sendRequest(conferenceCode: string): Promise<boolean[]>
	{
		if (!DesktopApi.isDesktop())
		{
			return Promise.resolve([]);
		}

		return await this.callMultiBroadcastClient.broadcastRequest(conferenceCode, { timeout: 100 });
	}

	setExecuter(handle) {
		this.callMultiBroadcastClient.executer(handle);
	}
}

export { ConferenceChannel };