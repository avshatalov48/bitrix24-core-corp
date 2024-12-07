import { Tag, Loc } from 'main.core';
import { RepresentativeSelector } from 'sign.v2.b2e.representative-selector';
import { type Role, MemberRole } from 'sign.v2.api';
import './style.css';
import { Helpdesk } from 'sign.v2.helper';

const HelpdeskCodes = Object.freeze({
	EditorRoleDetails: '19740766',
	ReviewerRoleDetails: '20801214',
});

export class DocumentValidation
{
	#reviewerRepresentativeSelector: RepresentativeSelector;
	#editorRepresentativeSelector: RepresentativeSelector;

	constructor()
	{
		this.#reviewerRepresentativeSelector = new RepresentativeSelector({
			description: `
				<span>
					${Helpdesk.replaceLink(Loc.getMessage('SIGN_B2E_DOCUMENT_VALIDATION_HINT_REVIEWER'), HelpdeskCodes.ReviewerRoleDetails)}
				</span>
			`,
		});
		this.#editorRepresentativeSelector = new RepresentativeSelector({
			description: `
				<span>
					${Helpdesk.replaceLink(Loc.getMessage('SIGN_B2E_DOCUMENT_VALIDATION_HINT_EDITOR'), HelpdeskCodes.EditorRoleDetails)}
				</span>
			`,
		});
	}

	#getRepresentativeLayout(role: Role): HTMLElement
	{
		const representativeSelector = role === MemberRole.reviewer
			? this.#reviewerRepresentativeSelector
			: this.#editorRepresentativeSelector;
		const representativeLayout = representativeSelector.getLayout();
		representativeSelector.formatSelectButton('ui-btn-xs ui-btn-round ui-btn-light-border');

		return Tag.render`
			<div>
				${representativeLayout}
			</div>
		`;
	}

	getReviewerLayout(): HTMLElement
	{
		return this.#getRepresentativeLayout(MemberRole.reviewer);
	}

	getEditorLayout(): HTMLElement
	{
		return this.#getRepresentativeLayout(MemberRole.editor);
	}

	getValidationData(): { [key: Role]: number; }
	{
		const validationData = {};
		const reviewerId = this.#reviewerRepresentativeSelector.getRepresentativeId();
		const editorId = this.#editorRepresentativeSelector.getRepresentativeId();
		if (reviewerId)
		{
			validationData.reviewer = reviewerId;
		}

		if (editorId)
		{
			validationData.editor = editorId;
		}

		return validationData;
	}

	load(memberId: number, role: Role): void
	{
		const representativeSelector = role === MemberRole.reviewer
			? this.#reviewerRepresentativeSelector
			: this.#editorRepresentativeSelector;
		representativeSelector.load(memberId);
	}
}
