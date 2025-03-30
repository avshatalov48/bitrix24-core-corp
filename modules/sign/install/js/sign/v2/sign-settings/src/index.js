import { Dom, Loc, Reflection, Tag, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { FeatureStorage } from 'sign.feature-storage';
import { DocumentMode, type DocumentModeType } from 'sign.type';
import type { AnalyticsOptions } from 'sign.v2.analytics';
import { Analytics, Context } from 'sign.v2.analytics';
import type { DocumentSend } from 'sign.v2.document-send';
import { type DocumentDetails, DocumentSetup } from 'sign.v2.document-setup';
import type { Editor } from 'sign.v2.editor';
import './style.css';
import { Preview } from 'sign.v2.preview';
import { type Metadata, Wizard, type WizardOptions } from 'ui.wizard';
import { decorateResultBeforeCompletion, getFilledStringOrUndefined, isTemplateMode } from './functions';
import type { SignOptions, SignOptionsConfig } from './types';

export type { SignOptions, SignOptionsConfig };
export { decorateResultBeforeCompletion, isTemplateMode };

export class SignSettings
{
	#cache: MemoryCache<any> = new MemoryCache();
	#containerId: string;
	#preview: Preview;
	#type: string;
	#wizardOptions: WizardOptions;
	documentSetup: DocumentSetup;
	documentSend: DocumentSend;
	wizard: Wizard;
	editor: Editor;
	#previewLayout: ?HTMLElement = null;
	#container: HTMLElement | null = null;
	#overlayContainer: HTMLElement | null = null;
	#currentOverlay: HTMLElement | null = null;
	documentMode: DocumentModeType;
	documentsGroup: Map<string, DocumentDetails>;
	documentsGroupUids: Array<string>;
	#isEditMode: boolean = false;
	#isSameBlankSelected: boolean = false;
	editedDocument: DocumentDetails | null;
	isB2bSignMaster: boolean = false;
	hasPreviewUrls: boolean = false;

	constructor(containerId: string, signOptions: SignOptions = {}, wizardOptions: WizardOptions = {})
	{
		this.#containerId = containerId;
		this.#wizardOptions = wizardOptions;
		const { type = '', config = {}, documentMode, initiatedByType } = signOptions;
		this.documentMode = documentMode;
		this.#type = type;
		this.documentsGroup = new Map();
		this.documentsGroupUids = [];
		const { languages } = config.documentSendConfig ?? {};
		const EditorConstructor = Reflection.getClass('top.BX.Sign.V2.Editor');
		this.editor = new EditorConstructor(
			type,
			{ languages, isTemplateMode: this.isTemplateMode(), documentInitiatedByType: initiatedByType },
		);
		this.#preview = new Preview({ layout: { getAfterPreviewLayoutCallback: () => this.getAfterPreviewLayout() } });
	}

	#createHead(): HTMLElement
	{
		const headerTitle = this.#getHeaderTitleText();
		const headerTitleSub = this.#getHeaderTitleSubText();

		return Tag.render`
			<div class="sign-settings__head">
				<div>
					<p class="sign-settings__head_title">${headerTitle}</p>
					<p class="sign-settings__head_title --sub">
						${headerTitleSub}
					</p>
				</div>
			</div>
		`;
	}

	#getHeaderTitleSubText(): ?string
	{
		if (this.#type === 'b2b')
		{
			return Loc.getMessage('SIGN_SETTINGS_B2B_TITLE_SUB');
		}

		if (this.isTemplateMode() && this.#isEditMode)
		{
			return null;
		}

		return Loc.getMessage('SIGN_SETTINGS_B2E_TITLE_SUB');
	}

	#getHeaderTitleText(): ?string
	{
		if (this.isTemplateMode())
		{
			return this.#isEditMode
				? Loc.getMessage('SIGN_SETTINGS_TITLE_TEMPLATE_EDIT')
				: Loc.getMessage('SIGN_SETTINGS_TITLE_TEMPLATE');
		}

		return Loc.getMessage('SIGN_SETTINGS_TITLE');
	}

	isTemplateMode(): boolean
	{
		return this.documentMode === DocumentMode.template;
	}

	isDocumentMode(): boolean
	{
		return this.documentMode === DocumentMode.document;
	}

	#getLayout(): HTMLElement
	{
		const className = this.#type === 'b2e' ? 'sign-settings --b2e' : 'sign-settings';

		this.#previewLayout = this.#preview.getLayout();

		this.#container = Tag.render`
			<div class="sign-settings__scope ${className}">
				<div class="sign-settings__sidebar">
					${this.#createHead()}
					${this.wizard.getLayout()}
				</div>
				<div style="display: flex; flex-direction: column;">
					${this.#previewLayout}
				</div>
			</div>
		`;

		return this.#container;
	}

	#getOverlayContainer(): HTMLElement
	{
		if (!this.#overlayContainer)
		{
			this.#overlayContainer = Tag.render`<div class="sign-settings__overlay"></div>`;
		}
		Dom.hide(this.#overlayContainer);

		return this.#overlayContainer;
	}

	#showCompleteNotification(): void
	{
		const Notification = Reflection.getClass('top.BX.UI.Notification');
		const notificationText = this.#isGroupDocuments()
			? Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT_GROUP')
			: Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT');

		Notification.Center.notify({
			content: notificationText,
			autoHideDelay: 4000,
		});
	}

	onComplete(showNotification: boolean = true): void
	{
		BX.SidePanel.Instance.close();
		if (showNotification)
		{
			this.#showCompleteNotification();
		}

		if (this.isSingleDocument())
		{
			const queryString = window.location.search;
			const urlParams = new URLSearchParams(queryString);
			if (!urlParams.has('noRedirect'))
			{
				const { entityTypeId, entityId } = this.documentSetup.setupData;
				const detailsUrl = `/crm/type/${entityTypeId}/details/${entityId}/`;
				BX.SidePanel.Instance.open(detailsUrl);
			}
		}
	}

	isSingleDocument(): boolean
	{
		return this.documentsGroup.size === 1;
	}

	#isGroupDocuments(): boolean
	{
		return this.documentsGroup.size > 1;
	}

	async #renderPages(documentData: DocumentDetails, preparedPages: boolean = false): Promise<void>
	{
		this.#preview.urls = [];
		this.disablePreviewReady();
		this.#preview.setBlocks(documentData.blocks);

		this.wizard.toggleBtnActiveState('back', true);
		const handler = (urls, totalPages): void => {
			this.enablePreviewReady();
			this.#preview.urls = urls;
			this.editor.setUrls(urls, totalPages);
			this.wizard.toggleBtnActiveState('back', false);
		};

		this.documentSetup.waitForPagesList(documentData, handler, preparedPages);
	}

	getFirstDocumentUidFromGroup(): string
	{
		return this.documentsGroup.keys().next().value;
	}

	getFirstDocumentDataFromGroup(): DocumentDetails
	{
		return this.documentsGroup.values().next().value;
	}

	#subscribeOnEditorEvents(): void
	{
		this.editor.subscribe('save', ({ data }) => {
			const blocks = data.blocks;
			const uid = data.uid;

			const selectedDocument = this.documentsGroup.get(uid);
			selectedDocument.blocks = blocks;

			if (uid === this.getFirstDocumentUidFromGroup())
			{
				this.#preview.setBlocks(blocks);
				this.documentSetup.setupData = {
					...this.documentSetup.setupData,
					blocks,
				};
			}
		});
	}

	subscribeOnEvents(): void
	{
		const settingsEvents = [
			{
				type: 'toggleActivity',
				stage: 'setup',
				method: ({ data }) => {
					const { selected } = data;
					this.wizard.toggleBtnActiveState('next', !selected);
				},
			},
			{
				type: 'addFile',
				stage: 'setup',
				method: ({ data }) => {
					this.wizard.toggleBtnActiveState('next', !data.ready);
				},
			},
			{
				type: 'removeFile',
				stage: 'setup',
				method: ({ data }) => {
					this.wizard.toggleBtnActiveState('next', !data.ready);
				},
			},
			{
				type: 'clearFiles',
				stage: 'setup',
				method: () => this.wizard.toggleBtnActiveState('next', true),
			},
			{
				type: 'showEditor',
				stage: 'send',
				method: async (event) => {
					const { uid } = event.getData();
					if (uid && this.#isGroupDocuments())
					{
						await this.#executeEditorActionsForGroup(uid);
					}

					this.editor.show();
				},
			},
			{
				type: 'changeTitle',
				stage: 'send',
				method: ({ data }) => {
					this.documentSetup.setupData = {
						...this.documentSetup.setupData,
						title: data.title,
					};
					const { blankTitle } = data;
					if (blankTitle)
					{
						const { blankSelector, setupData } = this.documentSetup;
						blankSelector.modifyBlankTitle(setupData.blankId, blankTitle);
					}
				},
			},
			{
				type: 'close',
				stage: 'send',
				method: () => this.onComplete(false),
			},
			{
				type: 'hidePreview',
				stage: 'send',
				method: () => Dom.style(this.#previewLayout, 'display', 'none'),
			},
			{
				type: 'showPreview',
				stage: 'send',
				method: () => Dom.style(this.#previewLayout, 'display', 'flex'),
			},
			{
				type: 'appendOverlay',
				stage: 'send',
				method: (event) => this.#appendOverlay(event?.data?.overlay),
			},
			{
				type: 'showOverlay',
				stage: 'send',
				method: () => this.#showOverlay(),
			},
			{
				type: 'hideOverlay',
				stage: 'send',
				method: () => this.#hideOverlay(),
			},
		];
		settingsEvents.forEach(({ type, method, stage }) => {
			const step = stage === 'setup' ? this.documentSetup : this.documentSend;
			step.subscribe(type, method);
		});
		this.#subscribeOnEditorEvents();
	}

	async #getPagesUrls(data: DocumentDetails): Promise
	{
		const documentUrls = [];
		const handler = (urls): void => {
			const targetDocument = this.documentsGroup.get(data.uid);
			documentUrls.push(...urls);
			targetDocument.urls = documentUrls;
		};
		await this.documentSetup.waitForPagesList(data, handler);
	}

	async #executeEditorActionsForGroup(uid: string): Promise<void>
	{
		this.editor.setUrls([], 0);

		const setupData = this.documentsGroup.get(uid);

		if (!setupData.urls)
		{
			const openEditorButton = this.#container.querySelector(`span[data-id="${setupData.id}"]`);
			Dom.addClass(openEditorButton, 'ui-btn-clock');
			await this.#getPagesUrls(setupData);
			Dom.removeClass(openEditorButton, 'ui-btn-clock');
			this.documentSetup.blankSelector.disableSelectedBlank(setupData.blankId);
			this.documentSetup.resetDocument();
			this.wizard.toggleBtnActiveState('next', false);
		}

		const targetDocument = this.documentsGroup.get(uid);
		this.editor.documentData = targetDocument;
		this.editor.setUrls(targetDocument.urls, targetDocument.urls.length);

		await this.editor.waitForPagesUrls();
		await this.editor.renderDocument();
	}

	#appendOverlay(overlay: ?HTMLElement): void
	{
		if (!overlay)
		{
			return;
		}

		if (this.#currentOverlay)
		{
			Dom.remove(this.#currentOverlay);
		}

		this.#currentOverlay = overlay;

		Dom.append(this.#currentOverlay, this.#overlayContainer);
	}

	async setupDocument(uid?: string, preparedPages: boolean = false): Promise<DocumentDetails> | null
	{
		if (this.documentSetup.isSameBlankSelected())
		{
			void await this.documentSetup.setup(uid);
			this.#isSameBlankSelected = true;

			return this.documentSetup.setupData;
		}

		if (this.documentsGroup.size === 0)
		{
			this.#preview.urls = [];
			this.editor.setUrls([], 0);
			this.#preview.setBlocks();
		}

		await this.documentSetup.setup(uid);

		const { setupData } = this.documentSetup;
		if (!setupData)
		{
			return null;
		}

		if (
			this.documentsGroup.size === 0
			|| (this.editedDocument && this.isFirstDocumentSelected(this.editedDocument.uid))
			|| this.isTemplateMode()
			|| this.isB2bSignMaster
			|| !FeatureStorage.isGroupSendingEnabled()
		)
		{
			this.#renderPages(setupData, preparedPages);
		}

		if (this.#preview.hasUrls())
		{
			this.hasPreviewUrls = true;
			this.wizard.toggleBtnActiveState('next', false);
		}

		this.#isSameBlankSelected = false;

		return setupData;
	}

	async init(uid: ?string, templateUid?: string): void
	{
		this.#isEditMode = Type.isStringFilled(uid) || Type.isStringFilled(templateUid);
		const metadata = this.getStepsMetadata(
			this,
			getFilledStringOrUndefined(uid),
			getFilledStringOrUndefined(templateUid),
		);
		const { complete, ...rest } = this.#wizardOptions;

		const title = this.isTemplateMode()
			? Loc.getMessage('SIGN_SETTINGS_CREATE_TEMPLATE')
			: Loc.getMessage('SIGN_SETTINGS_SEND_FOR_SIGN');
		this.wizard = new Wizard(metadata, {
			back: { className: 'ui-btn-light-border' },
			next: { className: 'ui-btn-primary' },
			complete: {
				className: 'ui-btn-primary',
				title,
				onComplete: () => this.onComplete(),
				...complete,
			},
			...rest,
		});
		if (uid)
		{
			await this.applyDocumentData(uid);
		}

		if (templateUid)
		{
			await this.applyTemplateData(templateUid);
		}

		this.#render(uid);
	}

	async applyTemplateData(templateUid: string): Promise<void>
	// eslint-disable-next-line no-empty-function
	{}

	#render(uid: ?string): void
	{
		const container = document.getElementById(this.#containerId);
		Dom.append(this.#getOverlayContainer(), container);
		Dom.append(this.#getLayout(), container);
		const step = this.documentSetup.setupData ? 1 : 0;

		if (!this.isB2bSignMaster)
		{
			const isDraft = Type.isStringFilled(uid);
			this.wizard.toggleBtnActiveState('next', !isDraft);
		}
		this.wizard.moveOnStep(step);
	}

	getStepsMetadata(signSettings: this, documentUid?: string, templateUid?: string): Metadata
	{
		return {};
	}

	#showOverlay(): void
	{
		Dom.style(this.#container, 'display', 'none');
		Dom.show(this.#overlayContainer);
	}

	#hideOverlay(): void
	{
		Dom.style(this.#container, 'display', 'flex');
		Dom.hide(this.#overlayContainer);
	}

	setAnalyticsContext(context: Partial<AnalyticsOptions>): void
	{
		this.getAnalytics().setContext(new Context(context));
	}

	getAnalytics(): Analytics
	{
		return this.#cache.remember('analytics', () => new (top.BX?.Sign.V2.Analytics ?? Analytics)());
	}

	isEditMode(): boolean
	{
		return this.#isEditMode;
	}

	resetPreview(): void
	{
		this.#preview.urls = [];
		this.#preview.setBlocks();
	}

	disablePreviewReady(): void
	{
		this.#preview.ready = false;
	}

	enablePreviewReady(): void
	{
		this.#preview.ready = true;
	}

	setSingleDocument(setupData: DocumentDetails): void
	{
		this.documentsGroup.clear();
		this.documentsGroup.set(setupData.uid, setupData);
		this.documentsGroupUids.length = 0;
		this.documentsGroupUids.push(setupData.uid);
		this.documentSend.setDocumentsBlock(this.documentsGroup);

		if (!this.#isSameBlankSelected)
		{
			this.resetPreview();
			this.editor.setUrls([]);
			this.disablePreviewReady();
		}
	}

	isFirstDocumentSelected(uid: string)
	{
		return this.documentsGroupUids[0] === uid;
	}

	getAfterPreviewLayout(): HTMLElement | null
	{
		return null;
	}

	async applyDocumentData(uid: string): Promise<boolean>
	{}
}
