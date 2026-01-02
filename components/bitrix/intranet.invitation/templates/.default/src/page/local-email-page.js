import { Loc, Tag, Type, Event } from 'main.core';
import { Analytics } from '../analytics';
import DepartmentControl from 'intranet.department-control';
import { Transport } from '../transport';
import { EmailInvitationInput } from './local-email-page/email-invitation-input';
import { InviteEmailPopup } from './local-email-page/invite-email-popup';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { InviteEmailHandler } from './local-email-page/invite-email-handler';
import { Page } from './page';
import 'ui.fonts.inter';

export type LocalEmailPageType = {
	departmentControl: DepartmentControl,
	transport: Transport,
	linkRegisterEnabled: boolean,
	analytics: Analytics,
}

export class LocalEmailPage extends Page
{
	#container: HTMLElement;
	#departmentControl: DepartmentControl;
	#inviteEmailPopup: InviteEmailPopup = null;
	#linkRegisterEnabled: boolean;
	#input: EmailInvitationInput = null;
	#process: InviteEmailHandler = null;
	#analytics: Analytics = null;

	constructor(options: LocalEmailPageType)
	{
		super();

		this.#linkRegisterEnabled = options.linkRegisterEnabled;
		this.#departmentControl = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
		this.#analytics = options.analytics;
		this.#input = new EmailInvitationInput();
		this.#process = new InviteEmailHandler(options, this.#input);

		EventEmitter.subscribe('BX.Intranet.Invitation:selfChange', (event: BaseEvent) => {
			this.#linkRegisterEnabled = event.getData().selfEnabled;
		});
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		return Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="invite-block">
				${this.#titleRender()}
				${this.#renderForm()}
			</div>
		`;
	}

	#titleRender(): HTMLElement
	{
		return Tag.render`
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-message">
					<div class="ui-icon-set --person-letter"></div>
				</div>
				<div class="invite-title-text">
					${Loc.getMessage('INTRANET_INVITE_DIALOG_TITLE_EMAIL_MSGVER_1')}
				</div>
				<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=25375678');"></div>
			</div>
		`;
	}

	#renderForm(): HTMLElement
	{
		let topBlock = null;
		if (this.#linkRegisterEnabled)
		{
			topBlock = this.#getNotifyBlock();
		}
		else
		{
			topBlock = this.#input.getContent();
			this.#input.onReadySaveInputHandler(this.onReadySaveInputHandler);
			this.#input.onUnreadySaveInputHandler(this.onUnreadySaveInputHandler);
		}

		return Tag.render`
			<div class="invite-content-container">
				${topBlock}
				${this.#getDepartmentBlock()}
				${this.#linkRegisterEnabled && this.#getMailServiceBlock()}
			</div>
		`;
	}

	#getNotifyBlock(): HTMLElement
	{
		return Tag.render`
			<div class="invite-content-notify">
				<div class="invite-content-notify-wrapper">
					<div class="invite-content-notify__image"></div>
				</div>
				<div class="invite-content-notify-content">
					<div class="invite-content-notify-content__description"> 
						${Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_MAIL_CONTENT_DESCRIPTION')}
					</div>
					<div class="invite-content-notify-content__step">
						${Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_MAIL_CONTENT_STEPS')}
					</div>
				</div>
			</div>
		`;
	}

	onReadySaveInputHandler(): void
	{
		EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
		EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	}

	onUnreadySaveInputHandler(): void
	{
		EventEmitter.emit('BX.Intranet.Invitation:onSubmitDisabled');
	}

	#getDepartmentBlock(): HTMLElement
	{
		return Tag.render`
			<div class="invite-content-department">
				${this.#departmentControl.render()}
			</div>
		`;
	}

	#getMailServiceBlock(): HTMLElement
	{
		const text = Loc.getMessage(
			'INTRANET_INVITE_DIALOG_LOCAL_MAIL_SERVICE',
			{
				'[LINK]': '<a class="invite-content-mail-service__link">',
				'[/LINK]': '</a>',
			},
		);

		const block = Tag.render`
			<div class="invite-content-mail-service">
				${text}
			</div>
		`;

		const link = block.querySelector('.invite-content-mail-service__link');
		if (Type.isDomNode(link))
		{
			Event.unbindAll(link);
			Event.bind(link, 'click', this.#openEmailInputPopup.bind(this));
		}

		return block;
	}

	#openEmailInputPopup(): void
	{
		if (!this.#inviteEmailPopup)
		{
			this.#inviteEmailPopup = new InviteEmailPopup(this.#input);
		}
		this.#analytics.sendOpenLocalEmailPopup();
		this.#inviteEmailPopup.show();
	}

	onSubmit(event: BaseEvent): void
	{
		this.#process.onSubmit();
	}

	getAnalyticTab(): string
	{
		return Analytics.TAB_LOCAL_EMAIL;
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	}
}
