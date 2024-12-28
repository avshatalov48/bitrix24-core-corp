import { Runtime, Text } from 'main.core';
import type { AnalyticsOptions } from 'ui.analytics';

type RolesDialogAnalyticsOptions = {
	cSection: string;
}

const RolesDialogAnalyticsEvent = Object.freeze({
	OPEN: 'open',
	CLOSE: 'close',
	SAVE: 'save',
	SEARCH: 'search',
	FEEDBACK: 'feedback',
	SELECT: 'save',
});

const RolesDialogAnalyticsEventStatus = Object.freeze({
	SUCCESS: 'success',
	ERROR: 'error',
});

export class RolesDialogAnalytics
{
	#cSection: string;

	constructor(options: RolesDialogAnalyticsOptions)
	{
		this.#cSection = this.#formatCSectionParam(options.cSection);
	}

	sendOpenLabel(isSuccess: boolean, role?: string): void
	{
		const status = isSuccess ? RolesDialogAnalyticsEventStatus.SUCCESS : RolesDialogAnalyticsEventStatus.ERROR;
		const extraParams = role ? { p1: { name: 'role', value: role } } : {};

		this.#sendLabel({
			status,
			extraParams,
			event: RolesDialogAnalyticsEvent.OPEN,
		});
	}

	sendCloseLabel(role?: string): void
	{
		const extraParams = role ? { p1: { name: 'role', value: role } } : {};

		this.#sendLabel({
			extraParams,
			event: RolesDialogAnalyticsEvent.CLOSE,
		});
	}

	sendSelectLabel(role: string): void
	{
		const extraParams = role ? { p1: { name: 'role', value: role } } : {};

		this.#sendLabel({
			extraParams,
			event: RolesDialogAnalyticsEvent.SELECT,
		});
	}

	sendSearchLabel(search: string): void
	{
		const extraParams: SendLabelExtraParams = search
			? {
				p1: {
					name: 'search',
					value: search,
				},
			} : {};

		this.#sendLabel({
			extraParams,
			event: RolesDialogAnalyticsEvent.SEARCH,
		});
	}

	sendFeedBackLabel(): void
	{
		this.#sendLabel({
			event: RolesDialogAnalyticsEvent.FEEDBACK,
		});
	}

	async #sendLabel(params: SendLabelParams): void
	{
		const status = params.status || RolesDialogAnalyticsEventStatus.SUCCESS;
		const event = params.event;
		const extraParams: SendLabelExtraParams = params.extraParams || {};

		try
		{
			const { sendData } = await Runtime.loadExtension('ui.analytics');

			const sendDataOptions: AnalyticsOptions = {
				event,
				status,
				...this.#getCommonParameters(),
				...this.#getFormattedExtraParams(extraParams),
			};

			sendData(sendDataOptions);
		}
		catch (e)
		{
			console.error('AI: RolesDialog: Can\'t send analytics', e);
		}
	}

	#getCommonParameters(): CommonParameters
	{
		return {
			tool: 'ai',
			category: 'roles_picker',
			c_section: this.#cSection,
		};
	}

	#getFormattedExtraParams(extraParams: SendLabelExtraParams): SendLabelExtraParams
	{
		const formattedExtraParams = {};

		Object.entries(extraParams).forEach(([paramKey, param]) => {
			formattedExtraParams[paramKey] = `${Text.toCamelCase(param.name)}_${Text.toCamelCase(param.value)}`;
		});

		return formattedExtraParams;
	}

	#formatCSectionParam(cSection: string): string
	{
		return cSection
			.replaceAll('-', '_')
			.split('_')
			.map((stringPart: string) => {
				if (Number.isNaN(parseInt(stringPart, 10)))
				{
					return stringPart;
				}

				return '';
			})
			.filter((stringPart: string) => stringPart)
			.join('_');
	}
}

type CommonParameters = {
	tool: string;
	category: string;
	c_section: string;
}

type SendLabelParams = {
	status?: string;
	event: string;
	extraParams?: SendLabelExtraParams;
}

type SendLabelExtraParams = {
	p1?: ExtraParam;
	p2?: ExtraParam;
	p3?: ExtraParam;
	p4?: ExtraParam;
	p5?: ExtraParam;
}

type ExtraParam = {
	name: string;
	value: string;
}
