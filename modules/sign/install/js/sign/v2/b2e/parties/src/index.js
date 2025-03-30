import { Dom, Loc, Tag, Type, Extension } from 'main.core';
import { CompanySelector } from 'sign.v2.b2e.company-selector';
import { DocumentValidation } from 'sign.v2.b2e.document-validation';
import { RepresentativeSelector } from 'sign.v2.b2e.representative-selector';
import type { BlankSelectorConfig } from 'sign.v2.blank-selector';
import type { Provider } from 'sign.v2.api';
import type { DocumentInitiatedType, MemberRoleType, DocumentModeType } from 'sign.type';
import { DocumentMode } from 'sign.type';
import { Hint } from 'sign.v2.helper';
import { isTemplateMode } from 'sign.v2.sign-settings';

const blockWarningClass = 'sign-document-b2e-parties__item_content--warning';

type PartiesData = { entityType: string, entityId: ?number, role?: MemberRoleType };
type Options = BlankSelectorConfig & { documentInitiatedType?: DocumentInitiatedType, documentMode?: DocumentModeType };

const currentUserId = Extension.getSettings('sign.v2.b2e.parties').get('currentUserId');

export class Parties
{
	#companySelector: CompanySelector = null;
	#representativeSelector: RepresentativeSelector = null;
	#documentValidation: DocumentValidation = null;
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
		const { region, documentInitiatedType, documentMode } = blankSelectorConfig;
		this.#representativeSelector = new RepresentativeSelector({ context: `sign_b2e_representative_selector_assignee_${currentUserId}` });
		const isTemplate = isTemplateMode(documentMode || DocumentMode.document);

		this.#companySelector = new CompanySelector({
			region,
			documentInitiatedType,
			isHcmLinkAvailable: hcmLinkAvailable,
			needOpenCrmSaveAndEditCompanySliders: isTemplate,
		});
		this.#documentValidation = new DocumentValidation();
	}

	setEntityId(entityId: number): void
	{
		this.#companySelector.setOptions({ entityId });
	}

	setInitiatedByType(initiatedByType: string): void
	{
		this.#companySelector.setInitiatedByType(initiatedByType);
	}

	setLastSavedIntegrationId(integrationId: number | null): void
	{
		this.#companySelector.setLastSavedIntegrationId(integrationId);
	}

	setIntegrationSelectorAvailability(isAvailable: boolean): void
	{
		this.#companySelector.setIntegrationSelectorAvailability(isAvailable);
	}

	async reloadCompanyProviders(): void
	{
		await this.#companySelector.reloadCompanyProviders();
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

	loadCompany(companyUid: string): void
	{
		this.#companySelector.load(companyUid);
	}

	loadRepresentative(representativeId: number): void
	{
		this.#representativeSelector.load(representativeId);
	}

	loadValidator(memberId: number, role: MemberRoleType): void
	{
		this.#documentValidation.load(memberId, role);
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
				${this.#documentValidation.getReviewerLayout()}
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

	#validate(): boolean
	{
		const validationResults = [
			this.#companySelector.validate(),
			this.#representativeSelector.validate(),
		];

		return validationResults.every((result: boolean) => result === true);
	}

	async save(documentId: string)
	{
		this.#removeWarningFromBlocks();
		if (!this.#validate())
		{
			throw new Error();
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

	getSelectedIntegrationId(): number | null
	{
		return this.#companySelector.getIntegrationId();
	}

	getSelectedProvider(): Provider | null
	{
		return this.#companySelector.getSelectedCompanyProvider();
	}

	getParties(): Record<string, PartiesData> & { validation: Array<PartiesData> }
	{
		const validationData = this.#documentValidation.getValidationData();

		return {
			representative: {
				entityType: 'user',
				entityId: this.#representativeSelector.getRepresentativeId(),
			},
			company: {
				entityType: 'company',
				entityId: this.#companySelector.getCompanyId(),
			},
			validation: Object.entries(validationData).map(([role, entityId]) => {
				return { entityType: 'user', entityId, role };
			}),
		};
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
