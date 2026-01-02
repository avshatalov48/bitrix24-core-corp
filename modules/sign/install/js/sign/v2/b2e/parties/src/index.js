import { Dom, Loc, Tag, Type, Extension } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { CompanySelector, type ProviderSelectedEvent, type CompanySelectedEvent } from 'sign.v2.b2e.company-selector';
import { DocumentValidation, maxReviewersCount } from 'sign.v2.b2e.document-validation';
import { RepresentativeSelector } from 'sign.v2.b2e.representative-selector';
import type { BlankSelectorConfig } from 'sign.v2.blank-selector';
import type { Provider, SetupMember } from 'sign.v2.api';
import type { DocumentInitiatedType, MemberRoleType, DocumentModeType } from 'sign.type';
import { DocumentMode, MemberRole, EntityType } from 'sign.type';
import { Helpdesk, Hint } from 'sign.v2.helper';
import { isTemplateMode } from 'sign.v2.sign-settings';

import './style.css';

const blockWarningClass = 'sign-document-b2e-parties__item_content--warning';

export type PartiesData = { entityType: string, entityId: ?number, role?: MemberRoleType };
type Options = BlankSelectorConfig & { documentInitiatedType?: DocumentInitiatedType, documentMode?: DocumentModeType };

const currentUserId = Extension.getSettings('sign.v2.b2e.parties').get('currentUserId');
const reviewerSelectorContainerListId = 'reviewer-selector-list-container';

const HelpdeskCodes = Object.freeze({
	ReviewerRoleDetails: '20801214',
});

export class Parties extends EventEmitter
{
	#companySelector: CompanySelector = null;
	#representativeSelector: RepresentativeSelector = null;
	#documentValidation: DocumentValidation = null;
	#addReviewerButton: ?HTMLElement = null;
	#ui = {
		container: HTMLDivElement = null,
		blocks: {
			companyContent: HTMLDivElement = null,
			representativeContent: HTMLDivElement = null,
			validationEditorLayout: HTMLDivElement = null,
		},
	};

	constructor(blankSelectorConfig: Options, hcmLinkAvailable: boolean)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.Parties');

		const { region, documentInitiatedType, documentMode } = blankSelectorConfig;
		const isTemplate = isTemplateMode(documentMode || DocumentMode.document);

		this.#representativeSelector = new RepresentativeSelector({
			roleEnabled: isTemplate,
			context: `sign_b2e_representative_selector_assignee_${currentUserId}`,
		});
		this.#companySelector = new CompanySelector({
			region,
			documentInitiatedType,
			isHcmLinkAvailable: hcmLinkAvailable,
			needOpenCrmSaveAndEditCompanySliders: isTemplate,
		});

		this.#companySelector.subscribe('onSelect', (event: CompanySelectedEvent) => {
			this.emit('onCompanySelect', event);
		});

		this.#companySelector.subscribe('onProviderSelect', (event: ProviderSelectedEvent) => {
			this.emit('onProviderSelect', event);
		});

		this.#documentValidation = new DocumentValidation(
			isTemplate,
			() => this.#checkAddReviewerLimit(),
		);
	}

	setEntityId(entityId: number): void
	{
		this.#companySelector.setOptions({ entityId });
	}

	setInitiatedByType(initiatedByType: string): void
	{
		this.#companySelector.setInitiatedByType(initiatedByType);
	}

	async reloadCompanyProviders(selectFirst: boolean): void
	{
		await this.#companySelector.reloadCompanyProviders(selectFirst);
	}

	setEditorAvailability(isAvailable: boolean): void
	{
		if (isAvailable)
		{
			this.#addEditorLayout();

			return;
		}

		this.#removeEditorLayout();
		this.#documentValidation.editorRepresentativeSelector.onSelectorItemDeselectedHandler();
	}

	loadCompany(companyUid: string, entityId: number | null): void
	{
		this.#companySelector.load(companyUid, entityId);
	}

	loadFirstCompany(): void
	{
		this.#companySelector.loadFirstCompany();
	}

	loadRepresentative(representativeId: number, entityType: string = EntityType.USER): void
	{
		this.#representativeSelector.load(representativeId, entityType);
	}

	loadFirstRepresentative(): void
	{
		this.#representativeSelector.loadFistRepresentative();
	}

	loadValidator(members: Array<SetupMember>): void
	{
		const reviewers = members.filter(
			(member: SetupMember): boolean => member.role === MemberRole.reviewer,
		);
		this.#documentValidation.loadReviewers(reviewers);

		const editors = members.filter(
			(member: SetupMember): boolean => member.role === MemberRole.editor,
		);
		this.#documentValidation.loadEditors(editors);
	}

	getLayout(): HTMLElement
	{
		this.#ui.blocks.companyContent = Tag.render`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					<span>${Loc.getMessage('SIGN_PARTIES_ITEM_COMPANY')}</span>
					<span
						data-hint="${Loc.getMessage('SIGN_PARTIES_ITEM_COMPANY_HINT')}"
					></span>
				</p>
				${this.#companySelector.getLayout()}
			</div>
		`;
		Hint.create(this.#ui.blocks.companyContent);
		this.#ui.blocks.representativeContent = Tag.render`
			<div class="sign-b2e-settings__item --representative">
				<p class="sign-b2e-settings__item_title">
					${Loc.getMessage('SIGN_PARTIES_ITEM_REPRESENTATIVE')}
				</p>
				${this.#representativeSelector.getLayout()}
			</div>
		`;
		const providerLayout = Tag.render`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					${Loc.getMessage('SIGN_PARTIES_ITEM_PROVIDER')}
				</p>
				${this.#companySelector.getProviderLayout()}
			</div>
		`;

		const validationReviewerLayout = Tag.render`
			<div class="sign-b2e-settings__item --reviewer">
				<p class="sign-b2e-settings__item_title">
					${Loc.getMessage('SIGN_PARTIES_ITEM_VALIDATION_REVIEWER')}
				</p>
				<div id="${reviewerSelectorContainerListId}">
					${this.#documentValidation.getReviewerLayoutList()}
				</div>
				${this.#createAddReviewerButton()}
				<p class="sign-wizard__notice">
				${Helpdesk.replaceLink(
					Loc.getMessage('SIGN_PARTIES_ADD_REVIEWER_BUTTON_HINT', { '#LIMIT#': maxReviewersCount }),
					HelpdeskCodes.ReviewerRoleDetails,
				)}
				</p>
			</div>
		`;

		this.#ui.blocks.validationEditorLayout = Tag.render`
			<div class="sign-b2e-settings__item --editor">
				<p class="sign-b2e-settings__item_title">
					${Loc.getMessage('SIGN_PARTIES_ITEM_VALIDATION_EDITOR')}
				</p>
				${this.#documentValidation.getEditorLayout()}
			</div>
		`;

		this.#ui.container = Tag.render`
			<div>
				<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_PARTIES_HEADER')}</h1>
				${this.#ui.blocks.companyContent}
				${providerLayout}
				${this.#ui.blocks.representativeContent}
				${validationReviewerLayout}
				${this.#ui.blocks.validationEditorLayout}
			</div>
		`;

		return this.#ui.container;
	}

	#createAddReviewerButton(): HTMLElement
	{
		this.#addReviewerButton = Tag.render`
			<button type="button" class="sign-b2e-document-setup__add-button">
				<span class="sign-b2e-document-setup__add-button_text">
					${Loc.getMessage('SIGN_PARTIES_ADD_REVIEWER_BUTTON_TITLE')}
				</span>
				<span class="sign-b2e-document-setup__add-button_disabled_hint" data-hint="${Loc.getMessage('SIGN_PARTIES_ADD_REVIEWER_BUTTON_DISABLED_HINT', { '#LIMIT#': maxReviewersCount })}"></span>
			</button>
		`;
		Hint.create(this.#addReviewerButton);

		BX.bind(this.#addReviewerButton, 'click', (): void => {
			const container = document.getElementById(reviewerSelectorContainerListId);
			if (container === null)
			{
				return;
			}

			const selector = this.#documentValidation.addReviewerRepresentativeSelector();
			Dom.append(selector, container);

			this.#checkAddReviewerLimit();
		});

		this.#checkAddReviewerLimit();

		return this.#addReviewerButton;
	}

	#checkAddReviewerLimit(): void
	{
		if (this.#addReviewerButton === null)
		{
			return;
		}

		this.#addReviewerButton.disabled = this.#documentValidation.getReviewerRepresentativeSelectorCount() >= maxReviewersCount;
	}

	#validate(): boolean
	{
		return this.#companySelector.validate() && this.#representativeSelector.validate();
	}

	async save(documentId: string)
	{
		this.#removeWarningFromBlocks();
		if (!this.#validate())
		{
			throw new Error('Validation failed');
		}

		try
		{
			await this.#companySelector.save(documentId);
		}
		catch (e)
		{
			this.#setWarning(this.#ui.blocks.companyContent);
			throw e;
		}
	}

	getSelectedProvider(): Provider | null
	{
		return this.#companySelector.getSelectedCompanyProvider();
	}

	isProviderSelected(): boolean
	{
		return Boolean(this.#companySelector.getSelectedCompanyProvider());
	}

	isRepresentativeSelected(): boolean
	{
		return Boolean(this.#representativeSelector.getRepresentativeId());
	}

	getParties(): Record<string, PartiesData> & { validation: Array<PartiesData> }
	{
		return {
			representative: {
				entityType: this.#representativeSelector.getRepresentativeItemType(),
				entityId: this.#representativeSelector.getRepresentativeId(),
			},
			company: {
				entityType: 'company',
				entityId: this.#companySelector.getCompanyId(),
			},
			validation: this.#documentValidation.getValidationData(),
		};
	}

	getSelectedCompanyId(): number
	{
		return this.#companySelector.getCompanyId();
	}

	#setWarning(block: HTMLDivElement): void
	{
		if (Type.isNull(block) || Type.isUndefined(block))
		{
			return;
		}

		Dom.addClass(block, blockWarningClass);
	}

	#removeWarningFromBlocks(): void
	{
		for (const [key, block] of Object.entries(this.#ui.blocks))
		{
			if (Type.isNull(block))
			{
				return;
			}

			Dom.removeClass(block, blockWarningClass);
		}
	}

	#addEditorLayout(): void
	{
		Dom.append(this.#ui.blocks.validationEditorLayout, this.#ui.container);
	}

	#removeEditorLayout(): void
	{
		Dom.remove(this.#ui.blocks.validationEditorLayout);
	}
}
