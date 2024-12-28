import { Tag, Loc, Dom, Reflection, Text, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import type { AnalyticsOptions } from 'sign.v2.analytics';
import { Wizard, type WizardOptions, type Metadata } from 'ui.wizard';
import { type DocumentData } from 'sign.v2.document-setup';
import { Preview } from 'sign.v2.preview';
import { Analytics, Context } from 'sign.v2.analytics';
import type { SignOptions, SignOptionsConfig } from './types';
import type { Editor } from 'sign.v2.editor';
import './style.css';
import { decorateResultBeforeCompletion, isTemplateMode } from './functions';

export type { SignOptions, SignOptionsConfig };

export type DocumentModeType = 'document' | 'template';
export const DocumentMode: Readonly<Record<string, DocumentModeType>> = Object.freeze({
	document: 'document',
	template: 'template',
});

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
	#isEditMode: boolean = false;

	constructor(containerId: string, signOptions: SignOptions = {}, wizardOptions: WizardOptions = {})
	{
		this.#containerId = containerId;
		this.#preview = new Preview();
		this.#wizardOptions = wizardOptions;
		const { type = '', config = {}, documentMode, initiatedByType } = signOptions;
		this.documentMode = documentMode;
		this.#type = type;
		const { languages } = config.documentSendConfig ?? {};
		const EditorConstructor = Reflection.getClass('top.BX.Sign.V2.Editor');
		this.editor = new EditorConstructor(
			type,
			{ languages, isTemplateMode: this.isTemplateMode(), documentInitiatedByType: initiatedByType },
		);
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
				${this.#previewLayout}
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
		Notification.Center.notify({
			content: Text.encode(Loc.getMessage('SIGN_SETTINGS_COMPLETE_NOTIFICATION_TEXT')),
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
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		if (!urlParams.has('noRedirect'))
		{
			const { entityTypeId, entityId } = this.documentSetup.setupData;
			const detailsUrl = `/crm/type/${entityTypeId}/details/${entityId}/`;
			BX.SidePanel.Instance.open(detailsUrl);
		}
	}

	#renderPages(blocks: DocumentData['blocks'], preparedPages: boolean = false): void
	{
		this.#preview.ready = false;
		this.#preview.setBlocks(blocks);
		this.wizard.toggleBtnActiveState('back', true);
		const handler = (urls, totalPages): void => {
			this.#preview.ready = true;
			this.#preview.urls = urls;
			this.editor.setUrls(urls, totalPages);
			this.wizard.toggleBtnActiveState('back', false);
		};

		this.documentSetup.waitForPagesList(handler, preparedPages);
	}

	#subscribeOnEditorEvents(): void
	{
		this.editor.subscribe('save', ({ data }) => {
			const blocks = data.blocks;
			this.#preview.setBlocks(blocks);
			this.documentSetup.setupData = {
				...this.documentSetup.setupData,
				blocks,
			};
			this.documentSend.documentData = { ...this.documentSend.documentData };
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
				method: () => this.editor.show(),
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

	async setupDocument(uid?: string, preparedPages: boolean = false): Promise<DocumentData> | null
	{
		if (this.documentSetup.isSameBlankSelected())
		{
			void await this.documentSetup.setup(uid);

			return this.documentSetup.setupData;
		}

		this.#preview.urls = [];
		this.editor.setUrls([], 0);
		this.#preview.setBlocks();
		await this.documentSetup.setup(uid);
		const { setupData } = this.documentSetup;
		if (!setupData)
		{
			return null;
		}

		const { blocks } = setupData;
		await this.#renderPages(blocks, preparedPages);

		return setupData;
	}

	async init(uid: ?string, templateUid?: string): void
	{
		this.#isEditMode = Type.isStringFilled(uid) || Type.isStringFilled(templateUid);
		const metadata = this.getStepsMetadata(this, uid, templateUid);
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
	{}

	#render(uid): void
	{
		const container = document.getElementById(this.#containerId);
		Dom.append(this.#getOverlayContainer(), container);
		Dom.append(this.#getLayout(), container);
		const step = this.documentSetup.setupData ? 1 : 0;

		const isDraft = Type.isStringFilled(uid);
		this.wizard.toggleBtnActiveState('next', !isDraft);
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
}
