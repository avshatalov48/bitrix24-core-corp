import { Dom, Runtime, Type, Extension } from 'main.core';
import { prepareBaasContext } from './helpers';

export type HandleGenerateErrorParams = {
	errorCode: string;
	baasBindElement: HTMLElement;
	baasContext: string;
	baasOptions: HandleGenerateErrorParamsBaasOptions;
};

type HandleGenerateErrorParamsBaasOptions = {
	context: string;
	bindElement?: HTMLElement;
	useAngle?: boolean;
	useSlider?: boolean;
}

const ErrorCode = {
	MONTHLY_LIMIT: 'LIMIT_IS_EXCEEDED_MONTHLY',
	DAILY_LIMIT: 'LIMIT_IS_EXCEEDED_DAILY',
	TARIFF_LIMIT: 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF',
	BAAS_LIMIT: 'LIMIT_IS_EXCEEDED_BAAS',
	OTHER: 'AI_ENGINE_ERROR_OTHER',
	PROVIDER: 'AI_ENGINE_ERROR_PROVIDER',
};

export class AjaxErrorHandler
{
	static #boxLimitSliderCode = 'limit_copilot_requests_box';

	static #isCloud(): boolean
	{
		return Extension.getSettings('ai.ajax-error-handler').isCloud;
	}

	static handleTextGenerateError(handleGenerateErrorParams: HandleGenerateErrorParams): string
	{
		AjaxErrorHandler.#validateHandleGenerateErrorParams(handleGenerateErrorParams);

		const code = handleGenerateErrorParams.errorCode;

		switch (code)
		{
			case ErrorCode.MONTHLY_LIMIT:
			{
				return this.#handleMonthlyLimitError();
			}

			case ErrorCode.DAILY_LIMIT:
			{
				return this.#handleDailyLimitError();
			}

			case ErrorCode.TARIFF_LIMIT:
			{
				return this.#handleTariffLimitError();
			}

			case ErrorCode.BAAS_LIMIT:
			{
				return this.#handleBaasLimitError(handleGenerateErrorParams.baasOptions);
			}

			case ErrorCode.OTHER:
			{
				return this.#handleOtherError();
			}

			case ErrorCode.PROVIDER:
			{
				return this.#handleProviderError();
			}

			default:
			{
				return this.#handleUndefinedError();
			}
		}
	}

	static #validateHandleGenerateErrorParams(params: HandleGenerateErrorParams): void
	{
		const code = params.errorCode;
		const baasOptions = params.baasOptions;
		const baasBindElement = params.baasOptions?.bindElement;
		const baasContext = params?.baasOptions.context;

		if (Type.isStringFilled(code) === false)
		{
			throw new Error('AI.AjaxErrorHandler: errorCode option is required and must be a string');
		}

		if (Type.isPlainObject(baasOptions) === false)
		{
			throw new TypeError('AI.AjaxErrorHandler: baasOptions option is required and must be a Object with context and bindElement properties');
		}

		if (baasBindElement && Type.isElementNode(baasBindElement) === false)
		{
			throw new Error('AI.AjaxErrorHandler: baasOptions.bindElement option must be an element node');
		}

		if (Type.isStringFilled(baasContext) === false)
		{
			throw new Error('AI.AjaxErrorHandler: baasOptions.context option is required and must be a string');
		}
	}

	static #handleDailyLimitError(): void
	{
		AjaxErrorHandler.#showInfoHelper(
			AjaxErrorHandler.#replaceSliderCodeWithBoxLimitCodeIfBox('limit_copilot_max_number_daily_requests'),
		);
	}

	static #handleMonthlyLimitError(): void
	{
		AjaxErrorHandler.#showInfoHelper(
			AjaxErrorHandler.#replaceSliderCodeWithBoxLimitCodeIfBox('limit_copilot_requests'),
		);
	}

	// eslint-disable-next-line sonarjs/no-identical-functions
	static async #handleTariffLimitError(): void
	{
		AjaxErrorHandler.#showInfoHelper(
			AjaxErrorHandler.#replaceSliderCodeWithBoxLimitCodeIfBox('limit_copilot_requests'),
		);
	}

	static #handleOtherError(): void
	{
		return undefined;
	}

	static #handleProviderError(): void
	{
		return undefined;
	}

	static #handleUndefinedError(): void
	{
		return undefined;
	}

	static #handleBaasLimitError(baasOptions: HandleGenerateErrorParamsBaasOptions): void
	{
		const { bindElement, context, useAngle = true, useSlider = false } = baasOptions;

		if (AjaxErrorHandler.#isCloud() === false)
		{
			AjaxErrorHandler.#showInfoHelper(AjaxErrorHandler.#boxLimitSliderCode);

			return;
		}

		if (useSlider)
		{
			Runtime.loadExtension('ui.info-helper')
				.then(({ InfoHelper }) => {
					InfoHelper.show('limit_boost_copilot');
				})
				.catch((err) => {
					console.error(err);
				});

			return;
		}

		Runtime.loadExtension('baas.store')
			.then(({ ServiceWidget }) => {
				if (ServiceWidget)
				{
					const preparedContext = prepareBaasContext(context);

					const serviceWidget = ServiceWidget.getInstanceByCode('ai_copilot_token').bind(bindElement, preparedContext);
					serviceWidget.getPopup().adjustPosition({
						forceTop: true,
					});
					serviceWidget.show();
					if (useAngle === false)
					{
						Dom.style(serviceWidget.getPopup()?.getPopupContainer().querySelector('.popup-window-angly'), 'opacity', 0);
					}
					else
					{
						Dom.style(serviceWidget.getPopup()?.getPopupContainer().querySelector('.popup-window-angly'), 'opacity', 1);
					}
				}
			})
			.catch((e) => {
				console.error(e);
			});
	}

	static handleImageGenerateError(handleGenerateErrorParams: HandleGenerateErrorParams): void
	{
		AjaxErrorHandler.#validateHandleGenerateErrorParams(handleGenerateErrorParams);

		const code = handleGenerateErrorParams.errorCode;

		switch (code)
		{
			case ErrorCode.MONTHLY_LIMIT:
			{
				return this.#handleMonthlyLimitError();
			}

			case ErrorCode.DAILY_LIMIT:
			{
				return this.#handleDailyLimitError();
			}

			case ErrorCode.TARIFF_LIMIT:
			{
				return this.#handleImageTariffLimitError();
			}

			case ErrorCode.BAAS_LIMIT:
			{
				return this.#handleBaasLimitError(handleGenerateErrorParams.baasOptions);
			}

			case ErrorCode.OTHER:
			{
				return this.#handleOtherError();
			}

			case ErrorCode.PROVIDER:
			{
				return this.#handleProviderError();
			}

			default:
			{
				return this.#handleUndefinedError();
			}
		}
	}

	static #handleImageTariffLimitError()
	{
		this.#showInfoHelper('limit_sites_ImageAssistant_AI');
	}

	static async #showInfoHelper(code: string): void
	{
		const { InfoHelper } = await Runtime.loadExtension('ui.info-helper');

		InfoHelper.show(code);
	}

	static #replaceSliderCodeWithBoxLimitCodeIfBox(code: string): string
	{
		return AjaxErrorHandler.#isCloud() ? code : AjaxErrorHandler.#boxLimitSliderCode;
	}
}
