import { Type } from 'main.core';
import { ProviderCode, type ProviderCodeType } from 'sign.type';
import { Api } from 'sign.v2.api';
import { type AnalyticsOptions, sendData } from 'ui.analytics';
import { Context } from './context';

export { Context };
export type { AnalyticsOptions };

type AnalyticOptionsWithoutToolKey = Omit<AnalyticsOptions, 'tool'>;

export class Analytics
{
	#documentUidToIdCache: Record<string, number> = {};
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
		providerCode?: ProviderCodeType,
	): void
	{
		void this.#sendWithProviderType(options, documentUidOrId, providerCode);
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
			const documentId = await this.#loadDocumentIdByUid(documentUidOrId);
			this.send({
				...options,
				p5: `docId_${documentId}`,
			});
		})();
	}

	async #loadDocumentIdByUid(documentUid: string): Promise<number | null>
	{
		if (Type.isNumber(this.#documentUidToIdCache[documentUid]))
		{
			return this.#documentUidToIdCache[documentUid];
		}

		const document = await this.#api.loadDocument(documentUid);
		if (document)
		{
			this.#documentUidToIdCache[documentUid] = document.id;

			return document.id;
		}

		return null;
	}

	async #sendWithProviderType(
		options: AnalyticOptionsWithoutToolKey,
		documentUidOrId: number | string,
		providerCode?: ProviderCodeType,
	): Promise<void>
	{
		let documentId: number | null = Type.isNumber(documentUidOrId)
			? documentUidOrId
			: this.#documentUidToIdCache[documentUidOrId] ?? null
		;
		let providerType = providerCode;
		if (Type.isNull(documentId) || Type.isUndefined(providerCode))
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
			documentId = document.id;
			providerType = document.providerCode;
		}

		this.send({
			...options,
			p1: this.#convertProviderCodeToP1IntegrationType(providerType),
			p5: `docId_${documentId}`,
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
