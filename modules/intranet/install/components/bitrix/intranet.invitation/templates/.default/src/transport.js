import { Type } from 'main.core';

export class Transport
{
	#componentName: string;
	#signedParameters: string;

	constructor(options)
	{
		this.#componentName = options.componentName;
		this.#signedParameters = options.signedParameters;
	}

	send(request): Promise
	{
		return BX.ajax.runComponentAction(this.#componentName, request.action, {
			signedParameters: this.#signedParameters,
			mode: Type.isStringFilled(request.mode) ? request.mode : 'ajax',
			method: Type.isStringFilled(request.method) ? request.method : 'post',
			data: request.data,
			analyticsLabel: request.analyticsLabel,
		});
	}
}