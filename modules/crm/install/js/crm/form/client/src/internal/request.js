import {ajax, Type} from 'main.core';

export type RequestOptions = {
	action: string,
	data: {[key: string]: any},
};

export type RequestErrorItem = {
	code: string,
	message: string,
	customData: any,
};

export type RequestError = Array<RequestErrorItem>;

export default function request<T>(options: RequestOptions): Promise<T, RequestError>
{
	const action = options.action.replace('crm.api.form.', '');
	const data = Type.isPlainObject(options.data) ? options.data : {};

	return new Promise((resolve, reject) => {
		ajax
			.runAction(`crm.api.form.${action}`, {json: data})
			.then((response) => {
				resolve(response.data);
			})
			.catch((error) => {
				reject(error.errors);
			});
	});
}