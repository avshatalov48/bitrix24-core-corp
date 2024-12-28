import { Type } from 'main.core';
import type { ProviderCodeType } from 'sign.v2.api';
import { Api } from 'sign.v2.api';
import { ProviderCode } from 'sign.v2.b2e.company-selector';
import { type AnalyticsOptions, sendData } from 'ui.analytics';
import { Context } from './context';

export { Context };
export type { AnalyticsOptions };

type AnalyticOptionsWithoutToolKey = Omit<AnalyticsOptions, 'tool'>;

export class Analytics
{
	#context: Context;
	#api: Api = new Api();

	constructor(options: { contextOptions?: Partial<AnalyticsOptions> } = {})
	{
		this.#context = new Context(options.contextOptions ?? {});
	}

	send(options: AnalyticOptionsWithoutToolKey): void
	{
		sendData({ ...this.#context.getOptions(), ...options, tool: 'sign' });
	}

	setContext(context: Context): void
	{
		this.#context = context;
	}

	getContext(): Context
	{
		return this.#context;
	}

	sendWithProviderTypeAndDocId(
		options: AnalyticOptionsWithoutToolKey,
		documentUidOrId: number | string,
	): void
	{
		void this.#sendWithProviderType(options, documentUidOrId);
	}

	sendWithDocId(options: AnalyticOptionsWithoutToolKey, documentUidOrId: string | number): void
	{
		if (Type.isNumber(documentUidOrId))
		{
			this.send({
				...options,
				p5: `docId_${documentUidOrId}`,
			});

			return;
		}

		(async () => {
			const document = await this.#api.loadDocument(documentUidOrId);
			this.send({
				...options,
				p5: `docId_${document.id}`,
			});
		})()
	}

	async #sendWithProviderType(options: AnalyticOptionsWithoutToolKey, documentUidOrId: number | string): Promise<void>
	{
		const document = Type.isString(documentUidOrId)
			? await this.#api.loadDocument(documentUidOrId)
			: await this.#api.loadDocumentById(documentUidOrId)
		;
		if (!document)
		{
			console.warn('Document not found by identifier', documentUidOrId);

			return;
		}
		this.send({
			...options,
			p1: this.#convertProviderCodeToP1IntegrationType(document.providerCode),
			p5: `docId_${document.id}`,
		});
	}

	#convertProviderCodeToP1IntegrationType(providerType?: ProviderCodeType): string
	{
		switch (providerType)
		{
			case ProviderCode.sesRu:
			case ProviderCode.sesCom:
				return 'integration_bitrix24KEDO';
			case ProviderCode.goskey:
				return 'integration_Goskluch';
			case ProviderCode.external:
				return 'integration_external';
			default:
				return 'integration_N';
		}
	}
}
