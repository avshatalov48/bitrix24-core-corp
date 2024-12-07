import { Tag, Dom, Event, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BlankSelector, type BlankSelectorConfig } from 'sign.v2.blank-selector';
import { Api } from 'sign.v2.api';
import { Button } from 'ui.buttons';
import { Alert } from 'ui.alerts';
import type { DocumentData } from './type';
import './style.css';

export type { DocumentData };
export type DocumentInitiatedType = 'employee' | 'company';
export const DocumentInitiated: Readonly<Record<string, DocumentInitiatedType>> = Object.freeze({
	employee: 'employee',
	company: 'company',
});

export class DocumentSetup extends EventEmitter
{
	blankSelector: BlankSelector;
	setupData: null | { uid: string, initiatedByType: DocumentInitiatedType, templateUid: ?string, ...};
	layout: HTMLElement;
	#notificationContainer: HTMLElement;
	#changeDomainWarningContainer: ?HTMLElement;
	#scenarioType: BlankSelectorConfig['type'];
	#api: Api;
	#uids: Map<number, string>;

	constructor(blankSelectorConfig: BlankSelectorConfig)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.DocumentSetup');
		this.blankSelector = new BlankSelector({
			...blankSelectorConfig,
			events: {
				toggleSelection: ({ data }) => {
					this.emit('toggleActivity', { ...data, blankSelector: this.blankSelector });
				},
				addFile: ({ data }) => {
					this.emit('addFile', { ready: this.blankSelector.isFilesReadyForUpload() });
				},
				removeFile: () => this.emit('removeFile', { ready: this.blankSelector.isFilesReadyForUpload() }),
				clearFiles: () => this.emit('clearFiles'),
			},
		});
		const { type, portalConfig } = blankSelectorConfig;
		this.setupData = null;
		this.#scenarioType = type;
		this.#api = new Api();
		this.#uids = new Map();
		this.initLayout();
		this.#initNotifications(portalConfig);
	}

	#initNotifications(portalConfig: BlankSelectorConfig['portalConfig'])
	{
		this.#notificationContainer = Tag.render`<div></div>`;
		const buttonsContainer = this.layout.querySelector('.sign-blank-selector__tile-widget');
		Dom.insertBefore(this.#notificationContainer, buttonsContainer);
		const { isDomainChanged, isEdoRegion, isUnsecuredScheme } = portalConfig;
		if (isDomainChanged)
		{
			this.#appendChangeDomainWarningContainer();
		}

		if (isUnsecuredScheme)
		{
			this.#appendUnsecuredSchemeWarningContainer();
		}

		if (isEdoRegion && this.#scenarioType !== 'b2e')
		{
			this.#appendEdoWarningContainer();
		}
	}

	async #register(blankId: string, isTemplateMode: boolean = false): Promise<{
		uid: string,
		templateUid: string | null
	}>
	{
		const data = await this.#api.register(blankId, this.#scenarioType, isTemplateMode);

		return data ?? {};
	}

	async #change(uid: string, blankId: number): Promise<string>
	{
		const data = await this.#api.changeDocument(uid, blankId);

		return data?.uid ?? '';
	}

	async #getPages(uid: string): Promise<{url: string;}[]>
	{
		const data = await this.#api.getPages(uid);

		return data.pages ?? [];
	}

	async #convertToBase64(pages): Promise<string[]>
	{
		const promises = pages.map(async (page) => {
			const data = await fetch(page.url);
			const blob = await data.blob();
			const fileReader = new FileReader();
			await new Promise((resolve) => {
				Event.bindOnce(fileReader, 'loadend', resolve);
				fileReader.readAsDataURL(blob);
			});

			return fileReader.result;
		});

		return Promise.all(promises);
	}

	#setDocumentData(setupData)
	{
		this.setupData = setupData;
		this.blankSelector.clearFiles({ removeFromServer: false });
	}

	async #processPages(urls: string[], cb: () => void)
	{
		let startIndex = 0;
		const pagesCount = 3;
		while (startIndex < urls.length)
		{
			const sliced = urls.slice(startIndex, startIndex + pagesCount);
			const convertedPages = await this.#convertToBase64(sliced);
			this.emit('toggleActivity', { selected: true });
			startIndex += pagesCount;
			cb(convertedPages, urls.length);
		}
	}

	#appendUnsecuredSchemeWarningContainer()
	{
		const text = `<div>${Loc.getMessage('SIGN_DOCUMENT_SETUP_USE_UNSECURED_SCHEME_WARNING')}</div>`;
		const warning = this.#getWarning();
		warning.setText(text);
		Dom.append(warning.getContainer(), this.#notificationContainer);
	}

	#appendChangeDomainWarningContainer()
	{
		const domainChangeButton = new Button({
			text: Loc.getMessage('SIGN_DOCUMENT_SETUP_REFRESH_DOMAIN_BUTTON_TEXT'),
			color: Button.Color.LINK,
			onclick: () => this.#api.changeDomain().then(() => this.#removeChangeDomainWarningContainer()),
			className: 'sign-document-setup__change-domain-button',
			size: Button.Size.EXTRA_SMALL,
		});
		const text = `<p>${Loc.getMessage('SIGN_DOCUMENT_SETUP_CHANGE_DOMAIN_WARNING')}</p>`;
		const warning = this.#getWarning();
		warning.setText(text);
		this.#changeDomainWarningContainer = warning.getContainer();
		Dom.append(domainChangeButton.getContainer(), this.#changeDomainWarningContainer);
		Dom.append(this.#changeDomainWarningContainer, this.#notificationContainer);
	}

	#getWarning(): Alert
	{
		return new Alert({
			size: Alert.Size.MD,
			color: Alert.Color.WARNING,
			icon: Alert.Icon.DANGER,
			customClass: 'sign-document-setup__change-domain-wrapper',
		});
	}

	#removeChangeDomainWarningContainer()
	{
		Dom.remove(this.#changeDomainWarningContainer);
	}

	#appendEdoWarningContainer()
	{
		const text = Loc.getMessage('SIGN_DOCUMENT_SETUP_EDO_TEXT', {
			'[helpdesklink]': '<a class="sign-document-setup__helpdesk-article" href="javascript:top.BX.Helper.show(\'redirect=detail&code=18453372\');">',
			'[/helpdesklink]': '</a>',
		});
		const alert = this.#getWarning();
		alert.setColor(Alert.Color.PRIMARY);
		alert.setIcon(Alert.Icon.INFO);
		alert.setText(text);
		Dom.append(alert.getContainer(), this.#notificationContainer);
	}

	initLayout()
	{
		this.layout = Tag.render`
			<div class="sign-document-setup">
				<p class="sign-document-setup__add-title">
					${Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TITLE')}
				</p>
				${this.blankSelector.getLayout()}
			</div>
		`;
	}

	isSameBlankSelected(): boolean
	{
		const { selectedBlankId } = this.blankSelector;
		const { blankId: lastBlankId } = this.setupData ?? {};

		return selectedBlankId > 0 && lastBlankId === selectedBlankId;
	}

	handleError(blankId: number)
	{
		this.#setDocumentData(null);
		this.blankSelector.resetSelectedBlank();
		this.blankSelector.deleteBlank(blankId);
	}

	loadBlocks(uid: string): Promise<DocumentData['blocks']>
	{
		return this.#api.loadBlocksByDocument(uid);
	}

	async setup(uid: ?string, isTemplateMode: boolean = false): Promise<void>
	{
		if (this.isSameBlankSelected())
		{
			this.setupData = {
				...this.setupData,
				isTemplate: true,
			};

			return;
		}

		const { selectedBlankId } = this.blankSelector;
		let blankId = 0;
		try
		{
			if (uid)
			{
				const [loadedData, blocks] = await Promise.all([
					this.#api.loadDocument(uid),
					this.loadBlocks(uid),
				]);
				this.#setDocumentData({ ...loadedData, blocks, isTemplate: true });
				const { blankId } = loadedData;
				if (!this.blankSelector.hasBlank(blankId))
				{
					await this.blankSelector.loadBlankById(blankId);
				}

				this.blankSelector.selectBlank(blankId);
			}
			else
			{
				this.ready = false;
				blankId = selectedBlankId || await this.blankSelector.createBlank();
				const isRegistered = this.#uids.has(blankId);

				const { uid, templateUid } = isRegistered
					? await this.#change(this.#uids.get(blankId), blankId)
					: await this.#register(blankId, isTemplateMode);

				this.#uids.set(blankId, uid);
				await this.#api.upload(uid);
				const [loadedData, blocks] = await Promise.all([
					this.#api.loadDocument(uid),
					this.#api.loadBlocksByDocument(uid),
				]);
				const isTemplate = Boolean(selectedBlankId);
				this.#setDocumentData({ ...loadedData, blocks, isTemplate, templateUid });
			}
		}
		catch
		{
			this.handleError(blankId);
		}

		this.ready = true;
	}

	async waitForPagesList(cb: (urls: string[]) => void, preparedPages: boolean = false)
	{
		let interval = 0;
		let isPagesReady = false;
		const requestTime = 10 * 1000;
		const uid = this.setupData.uid;
		const { blankId, isTemplate } = this.setupData;
		if (!isTemplate)
		{
			this.blankSelector.selectBlank(blankId);
		}

		this.emit('toggleActivity', { selected: false });

		const promises = [
			new Promise((resolve) => {
				BX.PULL?.subscribe({
					moduleId: 'sign',
					command: 'blankIsReady',
					callback: (result) => {
						if (!isPagesReady && result?.pages && result?.uid === uid)
						{
							resolve(result?.pages);
						}
					},
				});
			}),
			new Promise((resolve) => {
				interval = setInterval(async () => {
					if (isPagesReady)
					{
						clearInterval(interval);

						return;
					}

					const urls = await this.#getPages(uid);
					if (urls.length > 0)
					{
						resolve(urls);
					}
				}, requestTime);
			}),
		];

		if (preparedPages)
		{
			promises.push(new Promise((resolve) => {
				this.#getPages(uid)
					.then((urls) => {
						if (urls.length > 0)
						{
							resolve(urls);
						}
					});
			}));
		}

		const urls = await Promise.race(promises);
		if (!isTemplate)
		{
			const blank = this.blankSelector.getBlank(blankId);
			blank.setPreview(urls[0].url);
		}

		clearInterval(interval);
		isPagesReady = true;
		this.#processPages(urls, cb);
	}

	set ready(isReady: boolean)
	{
		if (isReady)
		{
			Dom.removeClass(this.layout, '--pending');
		}
		else
		{
			Dom.addClass(this.layout, '--pending');
		}
	}
}
