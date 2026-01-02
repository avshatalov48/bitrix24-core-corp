import { ajax } from 'main.core';

export class ApiClient
{
	#baseUrl: string;
	#contentType: string;

	constructor(baseUrl = 'tasks.v2.', contentType = 'json')
	{
		this.#baseUrl = baseUrl;
		this.#contentType = contentType;
	}

	async get(endpoint, params = {}): Promise<any>
	{
		const url = this.buildUrl(endpoint);
		const response = await ajax.runAction(url, {
			[this.#contentType]: { method: 'GET', ...params },
		});

		return this.handleResponse(response);
	}

	async post(endpoint, data): Promise<any>
	{
		const url = this.buildUrl(endpoint);
		const response = await ajax.runAction(url, {
			[this.#contentType]: data,
			navigation: data?.navigation,
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
			[this.#contentType]: data,
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
		let url = `${this.#baseUrl}${endpoint}`;
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

export const apiClient = new ApiClient();
