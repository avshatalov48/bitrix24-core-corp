import { Dom, Event, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { SignCancellation } from 'sign.v2.b2e.sign-cancellation';
import { SignLink } from 'sign.v2.b2e.sign-link';
import { Router } from 'crm.router';

import './style.css';

const SIGN_BUTTON_TYPE = 'sign';
const EDIT_BUTTON_TYPE = 'edit';
const REVIEW_BUTTON_TYPE = 'review';
const PREVIEW_BUTTON_TYPE = 'preview';
const MODIFY_BUTTON_TYPE = 'modify';
const CANCEL_BUTTON_TYPE = 'cancel';
const PROCESS_BUTTON_TYPE = 'process';

export class KanbanEntityFooter
{
	#buttons: Object = {
		sign: {
			title: Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_SIGN_TITLE'),
			classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-pencil', 'sign-b2e-sign-document'],
		},
		edit: {
			title: Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_EDIT_TITLE'),
			classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-pencil', 'sign-b2e-edit-document'],
		},
		review: {
			title: Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_REVIEW_TITLE'),
			classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-pencil', 'sign-b2e-review-document'],
		},
		cancel: {
			title: Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_CANCEL_TITLE'),
			classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-cancel', 'sign-b2e-cancel-document'],
		},
		preview: {
			title: Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_PREVIEW_TITLE'),
			classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-view', 'sign-b2e-preview-document'],
		},
		modify: {
			title: Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_MODIFY_TITLE'),
			classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-fill', 'sign-b2e-modify-document'],
		},
		process: {
			title: Loc.getMessage('SIGN_KANBAN_ENTITY_FOOTER_PROCESS_TITLE'),
			classes: ['sign-b2e-ui-icon', 'sign-b2e-ui-icon-fill', 'sign-b2e-process-document'],
		},
	};

	#isUserCanSign: boolean = false;
	#isUserCanEdit: boolean = false;
	#isUserCanReview: boolean = false;
	#isUserCanCancel: boolean = false;
	#isUserCanModify: boolean = false;
	#isUserCanPreview: boolean = false;
	#isUserCanProcess: boolean = false;
	#memberId: number = 0;
	#documentUid:string = '';
	#entityId: number = 0;

	init(): void
	{
		EventEmitter.subscribe(
			'BX.Crm.Kanban.Item::onBeforeFooterCreate',
			(event) => this.#onBeforeFooterCreate(event),
		);
		EventEmitter.subscribe(
			'BX.Crm.Kanban.Item::onBeforeAsideCreate',
			(event) => this.#onBeforeAsideCreate(event),
		);
	}

	#onBeforeAsideCreate(event: Event): void
	{
		const data = event.getData();
		data.elements = [];
	}

	#onBeforeFooterCreate(event: Event): void
	{
		const data = event.getData();
		this.#setData(data.item.data);
		data.elements = [];
		data.elements.push({
			id: 'lastActivityBlockItem',
			node: this.#createLastActivityBlock(
				data.item.lastActivityTime,
				data.item.lastActivityBy,
			),
		}, {
			id: 'buttonsBlock',
			node: this.#createButtonsBlock(),
		});
	}

	#setData(itemData: Object): void
	{
		this.#isUserCanSign = itemData?.isUserCanSign ?? false;
		this.#isUserCanReview = itemData?.isUserCanReview ?? false;
		this.#isUserCanEdit = itemData?.isUserCanEdit ?? false;
		this.#isUserCanModify = itemData?.isUserCanModify ?? false;
		this.#isUserCanPreview = itemData?.isUserCanPreview ?? false;
		this.#isUserCanCancel = itemData?.isUserCanCancel ?? false;
		this.#isUserCanProcess = itemData?.isUserCanProcess ?? false;
		this.#documentUid = itemData?.documentUid ?? '';
		this.#entityId = itemData?.entityId ?? '';
		this.#memberId = itemData?.memberId ?? 0;
	}

	#createLastActivityBlock(lastActivityBy: Node, lastActivityTime: Node): Node
	{
		const lastActivityBlock = Dom.create('div');
		Dom.addClass(lastActivityBlock, 'crm-kanban-item-last-activity');
		Dom.append(lastActivityBy, lastActivityBlock);
		Dom.append(lastActivityTime, lastActivityBlock);

		return lastActivityBlock;
	}

	#createButtonsBlock(): Node
	{
		const buttonsBlock = Dom.create('div');
		Dom.addClass(buttonsBlock, 'sign-b2e-buttons-block');
		let buttonType = '';
		if (this.#isUserCanSign)
		{
			buttonType = SIGN_BUTTON_TYPE;
		}
		else if (this.#isUserCanEdit)
		{
			buttonType = EDIT_BUTTON_TYPE;
		}
		else if (this.#isUserCanReview)
		{
			buttonType = REVIEW_BUTTON_TYPE;
		}

		let isShowDelimiter = false;

		if (buttonType && this.#memberId > 0)
		{
			const memberId = this.#memberId;
			const signBlock = this.#createButton(
				buttonType,
				() => this.#signShow(memberId),
			);
			Dom.append(signBlock, buttonsBlock);
			isShowDelimiter = true;
		}

		if (this.#isUserCanCancel && this.#documentUid)
		{
			const documentUid = this.#documentUid;
			const cancelBlock = this.#createButton(
				CANCEL_BUTTON_TYPE,
				() => this.#signCancel(documentUid),
			);
			Dom.append(cancelBlock, buttonsBlock);
			isShowDelimiter = true;
		}

		const entityId = this.#entityId;
		if (this.#isUserCanModify && entityId)
		{
			const modifyBlock = this.#createButton(
				MODIFY_BUTTON_TYPE,
				() => this.#modifyDocument(entityId),
			);
			Dom.append(modifyBlock, buttonsBlock);
			isShowDelimiter = true;
		}

		if (this.#isUserCanPreview && entityId)
		{
			if (isShowDelimiter === true)
			{
				const buttonDelimiterBlock = Dom.create('div');
				Dom.addClass(buttonDelimiterBlock, 'sign-b2e-ui-button-delimiter');
				Dom.append(buttonDelimiterBlock, buttonsBlock);
			}
			const previewBlock = this.#createButton(
				PREVIEW_BUTTON_TYPE,
				() => this.#previewDocument(entityId),
			);
			Dom.append(previewBlock, buttonsBlock);
		}

		if (this.#isUserCanProcess && entityId)
		{
			const processBlock = this.#createButton(
				PROCESS_BUTTON_TYPE,
				() => this.#showDocumentProcess(entityId),
			);
			Dom.append(processBlock, buttonsBlock);
		}

		return buttonsBlock;
	}

	#createButton(type: string, callback): Node
	{
		const buttonBlock = Dom.create('div');
		Event.bind(buttonBlock, 'click', (event) => {
			callback();
			event.stopPropagation();
		});
		Dom.addClass(buttonBlock, this.#buttons[type].classes);
		Dom.attr(buttonBlock, 'title', this.#buttons[type].title);

		return buttonBlock;
	}

	#signShow(memberId: number): void
	{
		const signLink = new SignLink({ memberId });
		signLink.openSlider();
	}

	#signCancel(documentUid: string): void
	{
		const signCancellation = new SignCancellation();
		signCancellation.cancelWithConfirm(documentUid);
	}

	#modifyDocument(entityId: Number): Promise
	{
		return Router.openSlider(
			`/sign/b2e/doc/0/?docId=${entityId}&stepId=changePartner&noRedirect=Y`,
			{
				width: 1250,
			},
		);
	}

	#previewDocument(entityId: Number): Promise
	{
		return Router.openSlider(`/sign/b2e/preview/0/?docId=${entityId}&noRedirect=Y`);
	}

	#showDocumentProcess(entityId: Number): Promise
	{
		return Router.openSlider(`/bitrix/components/bitrix/sign.document.list/slider.php?type=document&entity_id=${entityId}&apply_filter=N`);
	}
}
