import { Loc } from 'main.core';
import { DocumentSend } from 'sign.v2.b2b.document-send';
import { Requisites } from 'sign.v2.b2b.requisites';
import { DocumentSetup } from 'sign.v2.document-setup';
import { decorateResultBeforeCompletion, type SignOptions, SignSettings } from 'sign.v2.sign-settings';
import type { Metadata } from 'ui.wizard';

export class B2BSignSettings extends SignSettings
{
	#requisites: Requisites;

	constructor(containerId: string, signOptions: SignOptions)
	{
		super(containerId, signOptions);
		const { config, chatId = 0 } = signOptions;
		const { blankSelectorConfig, documentSendConfig } = config;
		blankSelectorConfig.chatId = chatId;
		this.documentSetup = new DocumentSetup(blankSelectorConfig);
		this.documentSend = new DocumentSend(documentSendConfig);
		this.#requisites = new Requisites();
		this.isB2bSignMaster = true;
		this.subscribeOnEvents();
	}

	async applyDocumentData(uid: string): Promise<boolean>
	{
		const applied = Boolean(await this.setupDocument(uid));
		if (!applied)
		{
			return false;
		}

		const { setupData } = this.documentSetup;
		this.#requisites.documentData = setupData;
		this.editor.documentData = setupData;
		this.#sendAnalyticsOnDocumentApply(setupData.id);

		this.documentsGroup.set(setupData.uid, setupData);

		return true;
	}

	getStepsMetadata(signSettings: B2BSignSettings, documentUid: ?string): Metadata
	{
		this.#sendAnalyticsOnStart(documentUid);
		const steps = {
			setup: {
				get content(): HTMLElement {
					return signSettings.documentSetup.layout;
				},
				title: Loc.getMessage('SIGN_SETTINGS_B2B_LOAD_DOCUMENT'),
				beforeCompletion: async () => {
					const setupData = await this.setupDocument();
					if (!setupData)
					{
						return false;
					}
					this.setSingleDocument(setupData);

					const { uid, entityId, initiator } = setupData;
					this.#requisites.documentData = { uid, entityId, initiator };

					return true;
				},
			},
			requisites: {
				get content(): HTMLElement {
					return signSettings.#requisites.getLayout();
				},
				title: Loc.getMessage('SIGN_SETTINGS_B2B_PREPARING_DOCUMENT'),
				beforeCompletion: async () => {
					const { uid, isTemplate, title, initiator, initiatedByType } = this.documentSetup.setupData;
					const valid = this.#requisites.checkInitiator(initiator);
					if (!valid)
					{
						return false;
					}

					const entityData = await this.#requisites.processMembers();
					if (!entityData)
					{
						return false;
					}

					const blocks = await this.documentSetup.loadBlocks(uid);
					this.editor.documentData = { isTemplate, uid, blocks };
					this.editor.entityData = entityData;
					this.editor.setSenderType(initiatedByType);
					this.documentSend.documentData = { uid, title, blocks, initiator };
					this.documentSend.entityData = entityData;
					await this.editor.waitForPagesUrls();
					await this.editor.renderDocument();
					this.wizard.toggleBtnLoadingState('next', false);
					await this.editor.show();

					return true;
				},
			},
			send: {
				get content(): HTMLElement {
					return signSettings.documentSend.getLayout();
				},
				title: Loc.getMessage('SIGN_SETTINGS_SEND_DOCUMENT'),
				beforeCompletion: () => {
					return this.documentSend.sendForSign();
				},
			},
		};

		this.#decorateStepsBeforeCompletionWithAnalytics(steps);

		return steps;
	}

	subscribeOnEvents()
	{
		super.subscribeOnEvents();
		this.#requisites.subscribe('changeInitiator', ({ data }) => {
			this.documentSetup.setupData = {
				...this.documentSetup.setupData,
				initiator: data.initiator,
			};
		});
	}

	#decorateStepsBeforeCompletionWithAnalytics(steps: Metadata): Metadata
	{
		const analytics = this.getAnalytics();
		steps.send.beforeCompletion = decorateResultBeforeCompletion(
			steps.send.beforeCompletion,
			() => {
				analytics.sendWithDocId(
					{
						event: 'sent_document_to_sign',
						status: 'success',
					},
					this.documentSend.documentData.uid,
				);
			},
			() => {
				analytics.send({
					event: 'sent_document_to_sign',
					status: 'error',
				});
			},
		);
	}

	#sendAnalyticsOnStart(): void
	{
		const analytics = this.getAnalytics();

		if (!this.isEditMode())
		{
			analytics.send({
				event: 'click_create_document',
			});
		}
	}

	#sendAnalyticsOnDocumentApply(documentId: number): void
	{
		this.getAnalytics().sendWithDocId(
			{
				event: 'click_create_document',
			},
			documentId,
		);
	}
}
