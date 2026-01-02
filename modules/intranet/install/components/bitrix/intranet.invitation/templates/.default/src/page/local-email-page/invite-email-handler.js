import { Loc, Tag, Event, Type } from 'main.core';
import { Analytics } from '../../analytics';
import DepartmentControl from 'intranet.department-control';
import { Transport } from '../../transport';
import { LocalEmailPageType } from '../local-email-page';
import { EmailInvitationInput } from 'email-invitation-input';
import { EventEmitter, BaseEvent } from 'main.core.events';

export class InviteEmailHandler
{
	#departmentControl: DepartmentControl;
	#linkRegisterEnabled: boolean;
	#transport: Transport = null;
	#input: EmailInvitationInput = null;
	#analytics: Analytics = null;

	constructor(options: LocalEmailPageType, input: EmailInvitationInput)
	{
		this.#transport = options.transport;
		this.#linkRegisterEnabled = options.linkRegisterEnabled;
		this.#departmentControl = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
		this.#input = input;
		this.#analytics = options.analytics;

		EventEmitter.subscribe('BX.Intranet.Invitation:selfChange', (event: BaseEvent) => {
			this.#linkRegisterEnabled = event.getData().selfEnabled;
		});

		EventEmitter.subscribe('BX.Intranet.Invitation:InviteSuccess', () => {
			this.#showNotification(true);
		});

		EventEmitter.subscribe('BX.Intranet.Invitation:InviteFailed', () => {
			this.#showNotification(false);
		});

		EventEmitter.subscribe('BX.Intranet.Invitation.EmailPopup:onSubmit', this.#onSubmitWithMailService.bind(this));
	}

	onSubmit(): void
	{
		if (this.#linkRegisterEnabled)
		{
			this.#onSubmitWithLocalEmailProgram();
		}
		else
		{
			this.#onSubmitWithMailService();
		}
	}

	#onSubmitWithLocalEmailProgram(): void
	{
		const departmentsId = this.#departmentControl.getValues();

		// eslint-disable-next-line promise/catch-or-return
		this.#transport.send({
			action: 'getInviteLink',
			data: {
				departmentsId,
			},
		}).then((response) => {
			EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
			EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');

			const invitationUrl = response.data?.invitationLink;
			if (Type.isStringFilled(invitationUrl))
			{
				this.#openLocalMailProgram(invitationUrl);
			}
		}, (response) => {
			EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
			EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
		});
	}

	#openLocalMailProgram(invitationUrl: string): void
	{
		this.#analytics.sendLocalEmailProgram();
		const subject = `subject=${encodeURIComponent(Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_SUBJECT'))}`;
		const body = `body=${encodeURIComponent(Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_BODY'))} ${invitationUrl}`;
		window.location = `mailto:?${subject}&${body}`;
	}

	#onSubmitWithMailService(): void
	{
		const { emails, errorElements } = this.#input.getValues();
		const errors = [];

		if (emails.length <= 0)
		{
			errors.push(Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL'));
		}

		if (errorElements.length > 0)
		{
			errors.push(Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL'));
		}

		if (errors.length > 0)
		{
			EventEmitter.emit(window.invitationForm, 'BX.Intranet.Invitation:onSendData', {
				errors,
			});

			return;
		}

		const data = {
			invitations: emails,
			departmentIds: this.#departmentControl.getValues(),
			tab: 'email',
		};

		const analyticsLabel = {
			INVITATION_TYPE: 'invite',
			INVITATION_COUNT: emails.length,
		};

		EventEmitter.emit(window.invitationForm, 'BX.Intranet.Invitation:onSendData', {
			action: 'invite',
			type: 'invite-email',
			data,
			analyticsLabel,
		});
	}

	#showNotification(isSuccess: boolean): void
	{
		const notificationOptions = {
			id: 'invite-notification-result',
			closeButton: true,
			autoHideDelay: 4000,
			autoHide: true,
			content: this.#getNotificationContent(isSuccess),
		};

		const notify = BX.UI.Notification.Center.notify(notificationOptions);
		notify.show();
		notify.activateAutoHide();
	}

	#getNotificationContent(isSuccess: boolean): HTMLElement
	{
		const title = isSuccess
			? Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_SUCCESS_TITLE')
			: Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_FAILED_TITLE');

		const description =	isSuccess
			? Loc.getMessage(
				'INTRANET_INVITE_DIALOG_LOCAL_POPUP_SUCCESS_DESCRIPTION',
				{
					'[LINK]': '<a class="intranet-invitation-dialog-link">',
					'[/LINK]': '</a>',
				},
			)
			: Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_FAILED_DESCRIPTION');

		const content = Tag.render`
			<div class="invite-email-notification">
				<div class="invite-email-notification__title">
					${title}
				</div>
				<div class="invite-email-notification__description">
					${description}
				</div>
			</div>
		`;

		const link = content.querySelector('.intranet-invitation-dialog-link');
		if (Type.isDomNode(link))
		{
			Event.unbindAll(link);
			Event.bind(link, 'click', this.#openInvitationPageWithFilter.bind(this));
		}

		return content;
	}

	#openInvitationPageWithFilter(): void
	{
		window.top.location = '/company/?apply_filter=Y&INVITED=Y';
	}
}
