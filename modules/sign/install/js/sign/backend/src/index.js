import { ajax, Http, Loc, Type, Uri } from 'main.core';
import { Error } from 'sign.error';

type ControllerType = {
	command: string,
	module?: string,
	timeout?: number,
	postData?: any,
	getData?: {
		[key: string]: string
	}
};

export class Backend
{
	static controllerUri = '/bitrix/services/main/ajax.php';

	/**
	 * Check ajax response and generate error, if exists.
	 * @param {Object} sourceResponse
	 * @return {boolean}
	 */
	static isErrorOccurred(sourceResponse): boolean
	{
		if (sourceResponse.status === 'error')
		{
			if (Type.isArray(sourceResponse.errors))
			{
				sourceResponse.errors.map(error => {
					if (error.code === 'invalid_csrf' && sourceResponse.data.sessid)
					{
						Loc.setMessage('bitrix_sessid', sourceResponse.data.sessid);
					}

					Error.getInstance().addError(error);
				});
			}

			return true;
		}

		return false;
	}

	/**
	 * Sends request to Controller API and returns Promise on result.
	 * @param {ControllerType} options
	 * @return {Promise}
	 */
	static controller(options: ControllerType): Promise<any, any>
	{
		options.getData = options.getData || {};
		options.postData = options.postData || {};
		const {command, postData, getData} = options;

		return new Promise((resolve, reject) => {

			postData.sessid = Loc.getMessage('bitrix_sessid');
			getData.action = (options.module || 'sign') + '.api.' + command;

			const fd = postData instanceof FormData ? postData : Http.Data.convertObjectToFormData(postData);

			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				timeout: options.timeout || 60,
				url: (new Uri(Backend.controllerUri)).setQueryParams(getData).toString(),
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (sourceResponse) => {

					if (this.isErrorOccurred(sourceResponse))
					{
						reject(sourceResponse);
						return;
					}

					resolve(sourceResponse.data);
				},
				onfailure: (sourceResponse) => {
					reject(sourceResponse);
				}
			});

			xhr.send(fd);
		});
	}
}
