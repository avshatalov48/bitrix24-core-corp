import { ajax } from 'main.core';

export class ApiClient
{
	constructor(baseUrl = 'booking.api_v1.')
	{
		this.baseUrl = baseUrl;
	}

	async get(endpoint, params = {}): Promise<any>
	{
		const url = this.buildUrl(endpoint);
		const response = await ajax.runAction(url, {
			json: { method: 'GET', ...params },
		});

		return this.handleResponse(response);
	}

	async post(endpoint, data): Promise<any>
	{
		const url = this.buildUrl(endpoint);
		const response = await ajax.runAction(url, {
			json: data,
		});

		return this.handleResponse(response);
	}

	async put(endpoint, data): Promise<any>
	{
		const url = this.buildUrl(endpoint);
		const response = await ajax.runAction(url, {
			method: 'PUT',
			headers: {
				'Content-Type': 'application/json',
			},
			json: data,
		});

		return this.handleResponse(response);
	}

	async delete(endpoint, params = {}): Promise<any>
	{
		const url = this.buildUrl(endpoint, params);
		const response = await ajax.runAction(url, {
			method: 'DELETE',
		});

		return this.handleResponse(response);
	}

	buildUrl(endpoint, params = {}): string
	{
		let url = `${this.baseUrl}${endpoint}`;
		if (Object.keys(params).length > 0)
		{
			url += `?${new URLSearchParams(params).toString()}`;
		}

		return url;
	}

	async handleResponse(response): Promise<any>
	{
		const { data, error } = response;
		if (error)
		{
			throw error;
		}

		return data;
	}
}
