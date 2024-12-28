import { ajax, Http, Loc, addCustomEvent, type AjaxResponse } from 'main.core';

import { Base as PayloadBase } from './payload/basepayload';
import { Text as PayloadText } from './payload/textpayload';

export const Base: PayloadBase = PayloadBase;
export const Text: PayloadText = PayloadText;

export class Engine
{
	static textCompletionsUrl 				= '/bitrix/services/main/ajax.php?action=ai.api.text.completions';
	static imageCompletionsUrl 				= '/bitrix/services/main/ajax.php?action=ai.api.image.completions';
	static textAcceptationUrl				= '/bitrix/services/main/ajax.php?action=ai.api.text.acceptation';
	static textFeedbackDataUrl				= '/bitrix/services/main/ajax.php?action=ai.api.text.getFeedbackData';
	static imageAcceptationUrl				= '/bitrix/services/main/ajax.php?action=ai.api.image.acceptation';
	static saveImageUrl						= '/bitrix/services/main/ajax.php?action=ai.api.image.save';
	static getToolingUrl					= '/bitrix/services/main/ajax.php?action=ai.api.tooling.get';
	static getImageToolingUrl				= '/bitrix/services/main/ajax.php?action=ai.api.image.getTooling';
	static getImageParamsUrl				= '/bitrix/services/main/ajax.php?action=ai.api.image.getParams';
	static installKitUrl					= '/bitrix/services/main/ajax.php?action=ai.api.tooling.installKit';
	static getRolesListUrl					= '/bitrix/services/main/ajax.php?action=ai.api.role.list';
	static getRolesDialogDataUrl			= '/bitrix/services/main/ajax.php?action=ai.api.role.picker';
	static addRoleToFavouriteListUrl		= '/bitrix/services/main/ajax.php?action=ai.api.role.addfavorite';
	static removeRoleFromFavouriteListUrl	= '/bitrix/services/main/ajax.php?action=ai.api.role.removefavorite';
	static acceptAgreementUrl				= '/bitrix/services/main/ajax.php?action=ai.api.agreement.accept';
	static checkAgreementUrl				= '/bitrix/services/main/ajax.php?action=ai.api.agreement.check';
	static setBannerLaunchedUrl				= '/bitrix/services/main/ajax.php?action=ai.api.tooling.setLaunched';

	#moduleId: string;
	#contextId: string;
	#contextParameters: any;
	#payload: PayloadBase;
	#historyState: boolean = false;
	/**
	 * -1 - no grouped, 0 - first item of group
	 * @type {?number}
	 */
	#historyGroupId: ?number = -1;
	#parameters: {[key: string]: string} = {};
	#analyticParameters: { [key: string]: string } = {};

	/**
	 * Sets Payload for Engine.
	 *
	 * @param {PayloadBase} payload
	 * @return {Engine}
	 */
	setPayload(payload: PayloadBase): this
	{
		this.#payload = payload;

		return this;
	}

	getPayload(): PayloadBase | undefined
	{
		return this.#payload;
	}

	/**
	 * Sets allowed (by core) parameters for Engine.
	 *
	 * @param {{[key: string]: string}} parameters
	 * @return {Engine}
	 */
	setParameters(parameters: {[key: string]: string}): this
	{
		this.#parameters = parameters;

		return this;
	}

	setAnalyticParameters(parameters: {[key: string]: string}): this
	{
		this.#analyticParameters = parameters;

		return this;
	}

	/**
	 * Sets current module id. Its should be Bitrix's module.
	 *
	 * @param {string} moduleId
	 * @return {Engine}
	 */
	setModuleId(moduleId: string): this
	{
		this.#moduleId = moduleId;

		return this;
	}

	getModuleId(): string
	{
		return this.#moduleId;
	}

	/**
	 * Sets current context id. Its may be just a string unique within the moduleId.
	 *
	 * @param {string} contextId
	 * @return {Engine}
	 */
	setContextId(contextId: string): this
	{
		this.#contextId = contextId;

		return this;
	}

	getContextId(): string
	{
		return this.#contextId;
	}

	setContextParameters(contextParameters: any): this
	{
		this.#contextParameters = contextParameters;

		return this;
	}

	/**
	 * Write or not history, in depend on $state.
	 *
	 * @param {boolean} state
	 * @return {Engine}
	 */
	setHistoryState(state: boolean): this
	{
		this.#historyState = state;

		return this;
	}

	/**
	 * Set group ID for save history.
	 * -1 - no grouped, 0 - first item of group
	 * @param id
	 * @return {Engine}
	 */
	setHistoryGroupId(id: ?number): this
	{
		this.#historyGroupId = id;

		return this;
	}

	setBannerLaunched(): Promise<void>
	{
		this.#addSystemParameters();

		return this.#send(Engine.setBannerLaunchedUrl, {
			parameters: this.#parameters,
		});
	}

	async checkAgreement(): Promise<AjaxResponse<boolean>>
	{
		this.#addSystemParameters();

		return this.#send(Engine.checkAgreementUrl, {
			parameters: this.#parameters,
			agreementCode: 'AI_BOX_AGREEMENT',
		});
	}

	acceptAgreement(): Promise<AjaxResponse<boolean>>
	{
		this.#addSystemParameters();

		return this.#send(Engine.acceptAgreementUrl, {
			parameters: this.#parameters,
			agreementCode: 'AI_BOX_AGREEMENT',
		});
	}

	/**
	 * Makes request for text completions.
	 *
	 * @return {Promise}
	 */
	textCompletions(): Promise<CompletionsResponse>
	{
		this.#addSystemParameters();

		return this.#send(Engine.textCompletionsUrl, {
			prompt: this.#payload.getRawData().prompt,
			engineCode: this.#payload.getRawData().engineCode,
			roleCode: this.#payload.getRawData()?.roleCode,
			markers: this.#payload.getMarkers(),
			parameters: this.#parameters,
		});
	}

	/**
	 * Makes request for image completions.
	 *
	 * @return {Promise}
	 */
	imageCompletions(): Promise<CompletionsResponse>
	{
		this.#addSystemParameters();

		return this.#send(Engine.imageCompletionsUrl, {
			prompt: this.#payload.getRawData().prompt,
			engineCode: this.#payload.getRawData().engineCode,
			markers: this.#payload.getMarkers(),
			parameters: this.#parameters,
		});
	}

	getTooling(category: string): Promise<GetToolingResponse>
	{
		this.#addSystemParameters();

		this.#parameters.category = this.#parameters.promptCategory;

		const data = {
			parameters: this.#parameters,
			category: this.#parameters.promptCategory,
			moduleId: this.#moduleId,
			context: this.#contextId,
		};

		return ajax.runAction('ai.prompt.getPromptsForUser', {
			data: Http.Data.convertObjectToFormData(data),
			method: 'POST',
			start: false,
			preparePost: false,
		});
	}

	getImagePickerTooling(): Promise<AjaxResponse>
	{
		this.#addSystemParameters();

		const data = {
			parameters: this.#parameters,
			category: 'image',
		};

		return new Promise((resolve, reject) => {
			const fd = Http.Data.convertObjectToFormData(data);
			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				url: Engine.getToolingUrl,
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (response) => {
					if (response.status === 'error')
					{
						reject(response);
					}
					else
					{
						resolve(response);
					}
				},
				onfailure: reject,
			});

			xhr.send(fd);
		});
	}

	getImageCopilotTooling(): Promise<AjaxResponse<GetImageCopilotToolingResponseData>>
	{
		this.#addSystemParameters();

		const data = {
			parameters: this.#parameters,
		};

		return new Promise((resolve, reject) => {
			const fd = Http.Data.convertObjectToFormData(data);
			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				url: Engine.getImageToolingUrl,
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (response) => {
					if (response.status === 'error')
					{
						reject(response);
					}
					else
					{
						resolve(response);
					}
				},
				onfailure: reject,
			});

			xhr.send(fd);
		});
	}

	getImageEngineParams(engineCode: string): Promise<AjaxResponse<ImageCopilotParams>>
	{
		this.#addSystemParameters();

		const data = {
			engineCode,
			parameters: this.#parameters,
		};

		return new Promise((resolve, reject) => {
			const fd = Http.Data.convertObjectToFormData(data);
			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				url: Engine.getImageParamsUrl,
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (response) => {
					if (response.status === 'error')
					{
						reject(response);
					}
					else
					{
						resolve(response);
					}
				},
				onfailure: reject,
			});

			xhr.send(fd);
		});
	}

	async getRolesDialogData(): Promise<AjaxResponse<GetRolesDialogDataResponseData>>
	{
		this.#addSystemParameters();

		const data = {
			parameters: this.#parameters,
		};

		return this.#send(Engine.getRolesDialogDataUrl, data);
	}

	async getRoles(): Promise<AjaxResponse<RoleIndustryItems>>
	{
		this.#addSystemParameters();

		const data = {
			parameters: this.#parameters,
		};

		return this.#send(Engine.getRolesListUrl, data);
	}

	async addRoleToFavouriteList(roleCode: string): Promise<AjaxResponse<RoleIndustryItems>>
	{
		this.#addSystemParameters();

		const data = {
			roleCode,
			parameters: this.#parameters,
		};

		return this.#send(Engine.addRoleToFavouriteListUrl, data);
	}

	async removeRoleFromFavouriteList(roleCode: string): Promise<AjaxResponse<RoleIndustryItems>>
	{
		this.#addSystemParameters();

		const data = {
			roleCode,
			parameters: this.#parameters,
		};

		return this.#send(Engine.removeRoleFromFavouriteListUrl, data);
	}

	installKit(code: string): Promise<void>
	{
		this.#addSystemParameters();

		const data = {
			code,
			parameters: this.#parameters,
		};

		return new Promise((resolve, reject) => {
			const fd = Http.Data.convertObjectToFormData(data);
			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				url: Engine.installKitUrl,
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (response) => {
					if (response.status === 'error')
					{
						reject(response);
					}
					else
					{
						resolve(response);
					}
				},
				onfailure: reject,
			});

			xhr.send(fd);
		});
	}

	/**
	 * Send user's acceptation of agreement.
	 *
	 * @return {Promise<string>}
	 */
	acceptImageAgreement(engineCode: string): Promise<EngineRequestResponse<boolean>>
	{
		this.#addSystemParameters();

		const data = {
			engineCode,
			sessid: Loc.getMessage('bitrix_sessid'),
			parameters: this.#parameters,
		};

		return new Promise((resolve, reject) => {
			const fd = Http.Data.convertObjectToFormData(data);
			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				url: Engine.imageAcceptationUrl,
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (response) => {
					if (response.status === 'error')
					{
						reject(response);
					}
					else
					{
						resolve(response);
					}
				},
				onfailure: reject,
			});

			xhr.send(fd);
		});
	}

	acceptTextAgreement(engineCode: string): Promise<EngineRequestResponse<boolean>>
	{
		this.#addSystemParameters();

		const data = {
			engineCode,
			sessid: Loc.getMessage('bitrix_sessid'),
			parameters: this.#parameters,
		};

		return new Promise((resolve, reject) => {
			const fd = Http.Data.convertObjectToFormData(data);
			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				url: Engine.textAcceptationUrl,
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (response) => {
					if (response.status === 'error')
					{
						reject(response);
					}
					else
					{
						resolve(response);
					}
				},
				onfailure: reject,
			});

			xhr.send(fd);
		});
	}

	saveImage(imageUrl: string): Promise<EngineRequestResponse<number>>
	{
		this.#addSystemParameters();

		const data = {
			pictureUrl: imageUrl,
			parameters: this.#parameters,
		};

		return this.#send(Engine.saveImageUrl, data);
	}

	getFeedbackData(): Promise<GetFeedbackContextDataResponse>
	{
		const data = {
			parameters: this.#parameters,
		};

		return this.#send(Engine.textFeedbackDataUrl, data);
	}

	/**
	 * Adds additional system parameters.
	 */
	#addSystemParameters(): void
	{
		this.#parameters.bx_module = this.#moduleId;
		this.#parameters.bx_context = this.#contextId;
		this.#parameters.bx_context_parameters = this.#contextParameters;
		this.#parameters.bx_history = this.#historyState;
		this.#parameters.bx_history_group_id = this.#historyGroupId;
		this.#parameters.bx_analytic = this.#analyticParameters;
	}

	/**
	 * Registers pull listener if response from the Controller is a queue's hash.
	 *
	 * @param {string} queueHash
	 * @param {() => {}} resolve
	 * @param {() => {}} reject
	 */
	#registerPullListener(queueHash: string, resolve: () => {}, reject: () => {}): void
	{
		addCustomEvent('onPullEvent-ai', (command, params) => {
			const { hash, data, error } = params;
			if (command === 'onQueueJobExecute' && hash === queueHash)
			{
				resolve({ data });
			}
			else if (command === 'onQueueJobFail' && hash === queueHash)
			{
				reject({ errors: [error] });
			}
		});
	}

	/**
	 * Makes request to the Controller.
	 *
	 * @param {string} url
	 * @param {Object} data
	 * @return {Promise}
	 */
	#send(url: string, data: Object): Promise
	{
		if (this.#isOffline())
		{
			return Promise.reject(new Error(Loc.getMessage('AI_ENGINE_INTERNET_PROBLEM')));
		}

		return new Promise((resolve, reject) => {
			const fd = Http.Data.convertObjectToFormData(data);
			const xhr = ajax({
				method: 'POST',
				dataType: 'json',
				url,
				data: fd,
				start: false,
				preparePost: false,
				onsuccess: (response) => {
					if (response.status === 'error')
					{
						reject(response);
					}
					else
					{
						const queueHash = response.data?.queue;
						if (queueHash)
						{
							this.#registerPullListener(queueHash, resolve, reject);
						}
						else
						{
							resolve(response);
						}
					}
				},
				onfailure: (res, resData) => {
					if (res === 'processing' && resData?.bProactive === true)
					{
						reject(resData.data);
					}

					reject(res);
				},
			});

			xhr.send(fd);
		});
	}

	#isOffline(): boolean
	{
		return !window.navigator.onLine;
	}
}

export type GetToolingResponse = EngineRequestResponse<GetToolingResultData>;
export type CompletionsResponse = EngineRequestResponse<CompletionsResultData>;
export type GetFeedbackContextDataResponse = EngineRequestResponse<FeedbackContextResultData>;

type EngineRequestResponse<T> = {
	data: T;
	status: 'success' | 'error';
	errors: [];
}

export type GetToolingResultData = {
	engines: EngineInfo[];
	history: History;
	promptsFavorite: Prompt[] | null;
	promptsOther: Prompt[] | null;
	promptsSystem: Prompt[] | null;
	kits: Kit[];
	first_launch: boolean;
	permissions: Permissions;
	role: Role;
}

type Permissions = {
	can_edit_settings: boolean;
}

type Kit = {
	code: string;
	installed: boolean;
	install_started: boolean;
}

export type EngineInfo = {
	agreement: EngineInfoAgreement;
	code: string;
	title: string;
	expired: boolean;
	inTariff: boolean;
	partner: boolean;
	queue: boolean;
	selected: boolean;
}

type EngineInfoAgreement = {
	accepted: boolean;
	text: string;
	title: string;
}

type History = {
	capacity: number;
	items: HistoryItem[];
}

type HistoryItem = {
	data: string;
	date: string;
	engineCode: string;
	id: number;
	payload: string;
};

export type Prompt = PromptCommand | PromptCommandSeparator;

type PromptCommandSeparator = {
	section: string;
	separator: true;
	title: string;
}

type PromptCommand = {
	icon: string;
	code: string;
	title: string;
	required: PromptCommandRequired;
	children: Prompt;
	text: string;
	type: PromptCommandType;
	isFavorite: boolean;
}

type PromptCommandType = null | 'default' | 'simpleTemplate';

type PromptCommandRequired = {
	context_message: boolean;
	user_message: boolean;
}

type CompletionsResultData = {
	last: HistoryItem;
	queue: boolean | null;
	result: string;
};

type FeedbackContextResultData = {
	context_messages: Array;
	original_message: string | null;
}

export type GetRolesDialogDataResponseData = {
	items: RoleIndustry[];
	recents: Role[];
	favorites: Role[];
	universalRole: Role[];
}

export type RoleIndustryItems = {
	items: RoleIndustry[];
}

export type RoleIndustry = {
	code: string;
	name: string;
	roles: Role[];
	isNew: boolean;
}

export type Role = {
	avatar: RoleAvatar;
	code: string;
	industryCode: string;
	name: string;
	description: string;
	isRecommended: boolean;
	isNew: boolean;
}

type RoleAvatar = {
	small: string;
	medium: string;
	large: string;
}

export type GetImageCopilotToolingResponseData = {
	engines: EngineInfo[];
	first_launch: boolean;
	params: ImageCopilotParams;
	portal_zone: string;
}

export type ImageCopilotParams = {
	formats: ImageCopilotFormat[];
	styles: ImageCopilotStyle[];
}

export type ImageCopilotFormat = {
	code: string;
	name: string;
}

export type ImageCopilotStyle = {
	code: string;
	name: string;
	preview: string;
}
