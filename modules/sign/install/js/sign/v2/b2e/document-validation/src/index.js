import { Tag, Dom, Loc, Extension, Text } from 'main.core';
import type { SetupMember } from 'sign.v2.api';
import { RepresentativeSelector } from 'sign.v2.b2e.representative-selector';
import { MemberRole } from 'sign.type';
import { Helpdesk } from 'sign.v2.helper';

import './style.css';

const HelpdeskCodes = Object.freeze({
	EditorRoleDetails: '19740766',
});

const currentUserId = Extension.getSettings('sign.v2.b2e.document-validation').get('currentUserId');
export const maxReviewersCount = 20;

export class DocumentValidation
{
	#reviewerRepresentativeSelectorList: Object;
	#isTemplate: boolean;
	#onReviewerDelete: () => void = null;
	editorRepresentativeSelector: RepresentativeSelector;

	constructor(isTemplate: boolean = false, onReviewerDelete: () => void = null)
	{
		this.#isTemplate = isTemplate;
		this.#reviewerRepresentativeSelectorList = [];
		this.#onReviewerDelete = onReviewerDelete;
		this.addReviewerRepresentativeSelector();

		this.editorRepresentativeSelector = new RepresentativeSelector({
			context: `sign_b2e_representative_selector_editor_${currentUserId}`,
			description: `
				<span>
					${Helpdesk.replaceLink(Loc.getMessage('SIGN_B2E_DOCUMENT_VALIDATION_HINT_EDITOR'), HelpdeskCodes.EditorRoleDetails)}
				</span>
			`,
			roleEnabled: isTemplate,
		});
	}

	addReviewerRepresentativeSelector(): ?HTMLElement
	{
		if (this.getReviewerRepresentativeSelectorCount() >= maxReviewersCount)
		{
			return null;
		}

		const index = Text.getRandom();
		const excludedEntityList = this.#getSelectedReviewerEntityList();
		const selector = new RepresentativeSelector({
			cacheable: false,
			context: `sign_b2e_representative_selector_reviewer_${currentUserId}_${index}`,
			roleEnabled: this.#isTemplate,
			isDescriptionVisible: false,
			isMenuButtonVisible: Object.keys(this.#reviewerRepresentativeSelectorList).length > 0,
			onDelete: (elementId: string): void => this.#onDeleteCallback(elementId),
			onHide: (): void => this.#setExcludedIdListForRepresentativeReviewerList(),
			excludedEntityList,
		});
		selector.formatSelectButton('ui-btn-xs ui-btn-round ui-btn-light-border');
		this.#reviewerRepresentativeSelectorList[selector.getContainerId()] = selector;

		return Tag.render`
			<div class="sign_b2e_representative_selector_reviewer">
				${selector.getLayout()}
			</div>
		`;
	}

	#onDeleteCallback(elementId: string): void
	{
		if (!Object.hasOwn(this.#reviewerRepresentativeSelectorList, elementId))
		{
			return;
		}

		delete this.#reviewerRepresentativeSelectorList[elementId];
		this.#setExcludedIdListForRepresentativeReviewerList();
		this.#onReviewerDelete(elementId);
	}

	#setExcludedIdListForRepresentativeReviewerList(): void
	{
		const excludedEntityList = this.#getSelectedReviewerEntityList();
		for (const representativeSelector of Object.values(this.#reviewerRepresentativeSelectorList))
		{
			const excludedEntityListWithoutSelected = excludedEntityList.filter(
				(entity: Object): boolean => !(entity.entityId === representativeSelector.getRepresentativeId()
					&& entity.entityType === representativeSelector.getRepresentativeItemType()),
			);
			representativeSelector.setExcludedEntityList(excludedEntityListWithoutSelected);
		}
	}

	getReviewerLayoutList(): HTMLElement
	{
		const result = Tag.render`<span></span>`;
		for (const representativeSelector of Object.values(this.#reviewerRepresentativeSelectorList))
		{
			const representativeLayout = representativeSelector.getLayout();

			const block = Tag.render`
				<div class="sign_b2e_representative_selector_reviewer">
					${representativeLayout}
				</div>
			`;

			Dom.append(block, result);
		}

		return result;
	}

	getEditorLayout(): HTMLElement
	{
		const representativeLayout = this.editorRepresentativeSelector.getLayout();
		this.editorRepresentativeSelector.formatSelectButton('ui-btn-xs ui-btn-round ui-btn-light-border');

		return Tag.render`
			<div>
				${representativeLayout}
			</div>
		`;
	}

	getValidationData(): Array<Object>
	{
		const validationData = this.#getSelectedReviewerEntityList();
		const editorId = this.editorRepresentativeSelector.getRepresentativeId();
		const editorType = this.editorRepresentativeSelector.getRepresentativeItemType();

		if (editorId && editorType)
		{
			validationData.push({
				entityId: editorId,
				entityType: editorType,
				role: MemberRole.editor,
			});
		}

		return validationData;
	}

	#getSelectedReviewerEntityList(): Array<Object>
	{
		const result = [];
		for (const representativeSelector of Object.values(this.#reviewerRepresentativeSelectorList))
		{
			const reviewerId = representativeSelector.getRepresentativeId();
			if (!reviewerId)
			{
				continue;
			}

			const reviewerType = representativeSelector.getRepresentativeItemType();
			if (!reviewerType)
			{
				continue;
			}

			result.push({
				entityId: reviewerId,
				entityType: reviewerType,
				role: MemberRole.reviewer,
			});
		}

		return result;
	}

	loadReviewers(reviewerList: Array<SetupMember>): void
	{
		if (reviewerList.length === 0)
		{
			return;
		}

		if (reviewerList.length > this.getReviewerRepresentativeSelectorCount())
		{
			for (let i = 1; i < reviewerList.length; i++)
			{
				this.addReviewerRepresentativeSelector();
			}
		}

		let selectorIndex = 0;
		for (const representativeSelector of Object.values(this.#reviewerRepresentativeSelectorList))
		{
			const reviewer = reviewerList[selectorIndex] ?? null;
			if (reviewer === null)
			{
				continue;
			}

			representativeSelector.load(reviewer.entityId, reviewer.entityType);
			selectorIndex++;
		}

		this.#setExcludedIdListForRepresentativeReviewerList();
	}

	loadEditors(editorList: Array<SetupMember>): void
	{
		if (editorList.length === 0)
		{
			return;
		}

		const editor = editorList[0];
		this.editorRepresentativeSelector.load(editor.entityId, editor.entityType);
	}

	getReviewerRepresentativeSelectorCount(): number
	{
		return Object.keys(this.#reviewerRepresentativeSelectorList).length;
	}
}
