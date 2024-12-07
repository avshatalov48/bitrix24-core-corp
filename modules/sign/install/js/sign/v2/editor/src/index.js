import { Tag, Reflection, Loc, Dom } from 'main.core';
import { Hint } from 'sign.v2.helper';
import { BlocksManager } from './blocks/blocksManager';
import { EventEmitter } from 'main.core.events';
import { MemberRole, type Role } from 'sign.v2.api';
import './style.css';

const buttonClassList = [
	'ui-btn',
	'ui-btn-sm',
	'ui-btn-round',
	'ui-btn-light-border',
];

export const SectionType = Object.freeze({
	General: 0,
	FirstParty: 1,
	SecondParty: 2,
});

type EditorOptions = {
	languages: {[key: string]: { NAME: string; IS_BETA: boolean; }},
}

export class Editor extends EventEmitter
{
	#sidePanel: BX.SidePanel.Slider;
	#closeSidePanel: Function;
	#resolvePages: Function;
	#dom: HTMLElement;
	#documentLayout: HTMLElement;
	#blocksManager: BlocksManager;
	#wizardType: string;
	#documentData;
	#urls: string[];
	#totalPages: number;

	#sectionElementByType: Map<number, HTMLElement> = new Map();
	#disabledSections: Set<number> = new Set();

	#needSaveBlocksOnSidePanelClose: boolean = true;
	#needToLockSidePanelClose: boolean = true;

	constructor(wizardType: string, options: EditorOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.Editor');
		const { Instance } = Reflection.getClass('BX.SidePanel');
		this.#sidePanel = Instance;
		this.#dom = Tag.render`<div class="sign-wizard__scope sign-editor"></div>`;
		this.#documentLayout = Tag.render`
			<div class="sign-editor__document"></div>
		`;
		this.#blocksManager = new BlocksManager({
			documentLayout: this.#documentLayout,
			disableEdit: false,
			languages: options.languages,
		});
		this.#wizardType = wizardType;
		this.#urls = [];
		this.#totalPages = 0;
	}

	#isB2e(): boolean
	{
		return this.#wizardType === 'b2e';
	}

	#getArticleCode(): string
	{
		return this.#isB2e() ? '' : '16571388';
	}

	setUrls(urls: string[], totalPages: number)
	{
		if (urls.length === 0)
		{
			this.#urls = [];
			this.#totalPages = 0;

			return;
		}

		this.#urls = [
			...this.#urls,
			...urls,
		];
		this.#totalPages = totalPages;
		if (this.#urls.length === totalPages)
		{
			this.#resolvePages?.();
		}
	}

	set documentData(documentData)
	{
		this.#documentData = documentData;
		const { uid = '', isTemplate } = documentData;
		if (isTemplate)
		{
			this.#toggleEditMode(false);
		}
		else
		{
			this.#toggleEditMode(true);
		}

		this.#blocksManager.setDocumentUid(uid);
	}

	set entityData(entityData)
	{
		const forbiddenRoles = new Set([
			MemberRole.editor,
			MemberRole.reviewer,
		]);

		const members = Object.values(entityData)
			.filter((member) => !forbiddenRoles.has(member.role))
			.map((member) => {
				const {
					id: cid,
					title: name,
					part,
					presetId,
					entityTypeId,
					uid,
					entityId,
					role,
				} = member;

				return {
					cid,
					name,
					part: this.#covertRoleToBlockParty(part, role),
					presetId,
					entityTypeId,
					uid,
					entityId,
				};
			});

		this.#blocksManager.addMembers(members);
	}

	#covertRoleToBlockParty(part: number, role: ?Role): number
	{
		switch (role)
		{
			case MemberRole.assignee:
				return 1;
			case MemberRole.signer:
				return 2;
			default:
				return part;
		}
	}

	async renderDocument()
	{
		Dom.clean(this.#documentLayout);
		const { promises, fragment } = this.#urls.reduce((acc, url) => {
			// eslint-disable-next-line no-shadow
			const { fragment, promises } = acc;
			const promise = new Promise((resolve) => {
				const page = Tag.render`
					<div class="sign-editor__document_page">
						<img src="${url}" onload="${resolve}"  alt=""/>
					</div>
				`;
				Dom.append(page, fragment);
			});
			promises.push(promise);

			return acc;
		}, {
			fragment: new DocumentFragment(),
			promises: [],
		});
		Dom.append(fragment, this.#documentLayout);
		const { resizeArea } = this.#blocksManager;
		Dom.append(resizeArea.getLayout(), this.#documentLayout);
		await Promise.all(promises);
		EventEmitter.unsubscribeAll('SidePanel.Slider:onOpenComplete');
		EventEmitter.subscribeOnce('SidePanel.Slider:onOpenComplete', () => {
			this.#blocksManager.initPagesRect();
			this.#blocksManager.initBlocks(
				this.#documentData.blocks.filter((block) => !this.#disabledSections.has(block.party)),
			);
		});
	}

	show(): Promise
	{
		return new Promise((resolve) => {
			this.#closeSidePanel = resolve;
			this.#sidePanel.open('editor', {
				contentCallback: () => {
					this.#render();

					return this.#dom;
				},
				events: {
					onClose: (e) => this.#onSidePanelCloseStart(e),
					onCloseComplete: () => this.#closeSidePanel(),
				},
			});
		});
	}

	waitForPagesUrls(): Promise<void>
	{
		return new Promise((resolve) => {
			if (this.#urls.length === this.#totalPages)
			{
				resolve();

				return;
			}

			this.#resolvePages = resolve;
		});
	}

	#render()
	{
		Dom.clean(this.#dom);
		Dom.append(this.#createHeader(), this.#dom);
		Dom.append(this.#createContent(), this.#dom);
	}

	#createHeader(): HTMLElement
	{
		const editButtonClassName = [
			'sign-editor__header_edit-btn',
			...buttonClassList,
		].join(' ');
		const saveButtonClassName = [
			'ui-btn-success',
			...buttonClassList.slice(0, -1),
		].join(' ');
		const editButtonTitle = Loc.getMessage('SIGN_EDITOR_EDIT');
		const saveButtonTitle = Loc.getMessage('SIGN_EDITOR_SAVE');

		let helpArticleElement = '';
		if (this.#getArticleCode())
		{
			helpArticleElement = Tag.render`
				<span
								onclick="${() => {
					const Helper = Reflection.getClass('BX.Helper');
					Helper.show(`redirect=detail&code=${this.#getArticleCode()}`);
				}}"
								class="sign-editor__header_help"
								></span>
			`;
		}
		const headTitleNode = Tag.render`
			<p class="sign-editor__header_title">
				<span>${Loc.getMessage('SIGN_EDITOR_EDITING')}</span>
				<span
					data-hint="${Loc.getMessage('SIGN_EDITOR_EDITING_HINT')}"
				></span>
			</p>
		`;
		Hint.create(headTitleNode);

		return Tag.render`
			<div class="sign-editor__header">
				${headTitleNode}
				<div class="sign-editor__header_right">
					${helpArticleElement}
					<span
						class="${editButtonClassName}"
						title="${editButtonTitle}"
						onclick="${() => {
							this.#toggleEditMode(true);
						}}"
					>
						${editButtonTitle}
					</span>
					<span
						class="${saveButtonClassName}"
						title="${saveButtonTitle}"
						onclick="${async ({ target }) => {
							Dom.addClass(target, 'ui-btn-wait');
							const blocks = await this.#blocksManager.save();
							Dom.removeClass(target, 'ui-btn-wait');
							if (!blocks)
							{
								return;
							}
							EventEmitter.subscribeOnce('SidePanel.Slider:onCloseComplete', () => {
								this.emit('save', { blocks });
							});
							this.#needSaveBlocksOnSidePanelClose = false;
							this.#sidePanel.close();
							this.#needSaveBlocksOnSidePanelClose = true;
							this.#toggleEditMode(false);
						}}"
					>
						${saveButtonTitle}
					</span>
				</div>
			</div>
		`;
	}

	#createContent(): HTMLElement
	{
		const sections = this.#createSections();
		const editorContent = Tag.render`
			<div class="sign-editor__content">
				<div class="sign-editor__document-container">
					${this.#documentLayout}
				</div>
				${sections}
			</div>
		`;
		Hint.create(sections, {
			bindOptions: { position: 'top' },
			angle: { position: 'bottom' },
			targetContainer: editorContent,
		});
		this.#blocksManager.setEditorContent(editorContent);

		return editorContent;
	}

	#createSections(): HTMLElement
	{
		const sections = this.#getSectionsData();
		const sectionsNodes = sections.map((section) => {
			const entries = Object.entries(section.blocks);
			const blocks = entries.map(([code, block]) => {
				const { title, hint } = block;

				return Tag.render`
					<div
						class="sign-editor__section_block"
						data-code="${code}"
						data-part="${section.part}"
					>
						<div>
							<span>${Loc.getMessage(title)}</span>
							<span data-hint="${Loc.getMessage(hint)}"></span>
						</div>
						<span class="sign-editor__section_add-block-btn">
							${Loc.getMessage('SIGN_EDITOR_BLOCK_ADD_TO_DOCUMENT')}
						</span>
					</div>
				`;
			});
			this.#blocksManager.initRepository(blocks);

			const sectionElement = Tag.render`
				<div class="sign-editor__section">
					<p class="sign-editor__section_title">
						${Loc.getMessage(section.title)}
					</p>
					${blocks}
				</div>
			`;
			this.#sectionElementByType.set(section.part, sectionElement);

			if (this.#disabledSections.has(section.part))
			{
				// eslint-disable-next-line @bitrix24/bitrix24-rules/no-style
				sectionElement.style.display = 'none';
			}

			return sectionElement;
		});

		return Tag.render`
			<div class="sign-editor__sections">
				${sectionsNodes}
			</div>
		`;
	}

	setSectionVisibilityByType(type: number, visibility: boolean): void
	{
		const sectionElement = this.#sectionElementByType.get(type);
		if (sectionElement)
		{
			// eslint-disable-next-line @bitrix24/bitrix24-rules/no-style
			sectionElement.style.display = visibility === true ? 'block' : 'none';
		}

		if (visibility)
		{
			this.#disabledSections.delete(type);
		}
		else
		{
			this.#disabledSections.add(type);
		}
	}

	#toggleEditMode(isEdit: boolean)
	{
		if (isEdit)
		{
			Dom.addClass(this.#dom, '--editable');

			return;
		}

		Dom.removeClass(this.#dom, '--editable');
	}

	// eslint-disable-next-line max-lines-per-function,flowtype/require-return-type
	#getSectionsData()
	{
		const firstPartyBlocks = {};
		const partnerBlocks = {};
		const generalBlocks = {
			text: {
				title: 'SIGN_EDITOR_BLOCK_TEXT',
				hint: 'SIGN_EDITOR_BLOCK_TEXT_HINT',
			},
		};
		if (!this.#isB2e())
		{
			generalBlocks.date = {
				title: 'SIGN_EDITOR_BLOCK_DATE',
				hint: 'SIGN_EDITOR_BLOCK_DATE_HINT',
			};
		}
		generalBlocks.number = {
			title: 'SIGN_EDITOR_BLOCK_NUMBER',
			hint: 'SIGN_EDITOR_BLOCK_NUMBER_HINT',
		};

		let titles = {
			firstParty: 'SIGN_EDITOR_BLOCKS_FIRST_PARTY',
			partner: 'SIGN_EDITOR_BLOCKS_PARTNER',
		};

		if (this.#wizardType === 'b2e')
		{
			Object.assign(firstPartyBlocks, {
				myb2ereference: {
					title: 'SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE',
					hint: 'SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_HINT',
				},
				myrequisites: {
					title: 'SIGN_EDITOR_BLOCK_REQUISITES',
					hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_REQUISITES_HINT',
				},
			});
			Object.assign(partnerBlocks, {
				b2ereference: {
					title: 'SIGN_EDITOR_BLOCK_B2E_REFERENCE',
					hint: 'SIGN_EDITOR_BLOCK_B2E_REFERENCE_HINT',
				},
			});

			titles = {
				firstParty: 'SIGN_EDITOR_BLOCKS_FIRST_PARTY_B2E',
				partner: 'SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E',
			};
		}
		else
		{
			Object.assign(firstPartyBlocks, {
				myreference: {
					title: 'SIGN_EDITOR_BLOCK_CRM',
					hint: 'SIGN_EDITOR_BLOCK_CRM_HINT',
				},
				myrequisites: {
					title: 'SIGN_EDITOR_BLOCK_REQUISITES',
					hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_REQUISITES_HINT',
				},
				mysign: {
					title: 'SIGN_EDITOR_BLOCK_SIGNATURE',
					hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_SIGNATURE_HINT',
				},
				mystamp: {
					title: 'SIGN_EDITOR_BLOCK_STAMP_MSGVER_1',
					hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_STAMP_HINT',
				},
			});
			Object.assign(partnerBlocks, {
				reference: { ...firstPartyBlocks.myreference },
				requisites: {
					...firstPartyBlocks.myrequisites,
					hint: 'SIGN_EDITOR_BLOCK_PARTNER_REQUISITES_HINT',
				},
				sign: {
					...firstPartyBlocks.mysign,
					hint: 'SIGN_EDITOR_BLOCK_PARTNER_SIGNATURE_HINT',
				},
				stamp: {
					...firstPartyBlocks.mystamp,
					hint: 'SIGN_EDITOR_BLOCK_PARTNER_STAMP_HINT',
				},
			});
		}

		return [
			{
				title: titles.firstParty,
				blocks: firstPartyBlocks,
				part: 1,
			},
			{
				title: titles.partner,
				blocks: partnerBlocks,
				part: 2,
			},
			{
				title: 'SIGN_EDITOR_BLOCKS_GENERAL',
				blocks: generalBlocks,
				part: 0,
			},
		];
	}

	#onSidePanelCloseStart(event: BX.SidePanel.Event): Promise<void>
	{
		if (!this.#needSaveBlocksOnSidePanelClose)
		{
			return;
		}

		if (!this.#needToLockSidePanelClose)
		{
			return;
		}

		event.denyAction();
		void this.#blocksManager.save().then((blocks) => {
			if (!blocks)
			{
				return;
			}
			this.#needToLockSidePanelClose = false;
			this.#sidePanel.close();
			this.#needToLockSidePanelClose = true;
			this.emit('save', { blocks });
		});
	}
}
