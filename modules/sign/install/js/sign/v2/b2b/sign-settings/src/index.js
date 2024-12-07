import { Loc } from 'main.core';
import type { Metadata } from 'ui.wizard';
import { type SignOptions, SignSettings } from 'sign.v2.sign-settings';
import { DocumentSetup } from 'sign.v2.document-setup';
import { DocumentSend } from 'sign.v2.b2b.document-send';
import { Requisites } from 'sign.v2.b2b.requisites';

export class B2BSignSettings extends SignSettings
{
	#requisites: Requisites;

	constructor(containerId: string, signOptions: SignOptions)
	{
		super(containerId, signOptions);
		const { config } = signOptions;
		const { blankSelectorConfig, documentSendConfig } = config;
		this.documentSetup = new DocumentSetup(blankSelectorConfig);
		this.documentSend = new DocumentSend(documentSendConfig);
		this.#requisites = new Requisites();
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
		this.documentSend.documentData = setupData;
		this.editor.documentData = setupData;

		return true;
	}

	getStepsMetadata(signSettings: B2BSignSettings): Metadata
	{
		return {
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
					const { uid, isTemplate, title, initiator } = this.documentSetup.setupData;
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
}
