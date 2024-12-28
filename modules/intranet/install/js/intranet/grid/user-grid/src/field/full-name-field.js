import { BaseField } from './base-field';
import { Dom, Tag, Loc, Text } from 'main.core';
import { Label, LabelColor } from 'ui.label';

export type FullNameFieldType = {
	fullName: string,
	profileLink: string,
	position: ?string,
	photoUrl: string,
	role: string,
	inviteStatus: string,
}

export class FullNameField extends BaseField
{
	render(params: FullNameFieldType): void
	{
		const fullNameContainer = Tag.render`
			<div class="user-grid_full-name-container">${this.#getFullNameLink(params.fullName, params.profileLink)}</div>
		`;

		if (params.position)
		{
			Dom.append(this.#getPositionLabelContainer(Text.encode(params.position)), fullNameContainer);
		}

		switch (params.role)
		{
			case 'integrator':
				Dom.append(this.#getIntegratorBalloonContainer(), fullNameContainer);
				break;
			case 'admin':
				Dom.append(this.#getAdminBalloonContainer(), fullNameContainer);
				break;
			case 'extranet':
				Dom.append(this.#getExtranetBalloonContainer(), fullNameContainer);
				break;
			case 'collaber':
				Dom.append(this.#getCollaberBalloonContainer(), fullNameContainer);
				break;
			default:
				break;
		}

		switch (params.inviteStatus)
		{
			case 'INVITE_AWAITING_APPROVE':
				Dom.append(this.#getWaitingConfirmationLabelContainer(), fullNameContainer);
				break;
			case 'INVITED':
				Dom.append(this.#getInvitedLabelContainer(), fullNameContainer);
				break;
			default:
				break;
		}

		this.appendToFieldNode(fullNameContainer);
	}

	#getFullNameLink(fullName: string, profileLink: string): HTMLElement
	{
		return Tag.render`
			<a class="user-grid_full-name-label" href="${profileLink}">
				${fullName}
			</a>
		`;
	}

	#getInvitedLabelContainer(): HTMLElement
	{
		const label = new Label({
			text: Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_INVITATION_NOT_ACCEPTED'),
			color: LabelColor.LIGHT_BLUE,
			fill: true,
			size: Label.Size.MD,
			customClass: 'user-grid_label',
		});

		return label.render();
	}

	#getWaitingConfirmationLabelContainer(): HTMLElement
	{
		const label = new Label({
			text: Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_NOT_CONFIRMED'),
			color: LabelColor.YELLOW,
			fill: true,
			size: Label.Size.MD,
			customClass: 'user-grid_label',
		});

		return label.render();
	}

	#getPositionLabelContainer(position: string): HTMLElement
	{
		return Tag.render`<div class="user-grid_position-label">${position}</div>`;
	}

	#getIntegratorBalloonContainer(): HTMLElement
	{
		return Tag.render`
			<span class="user-grid_role-label --integrator">
				${Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_INTEGRATOR')}
			</span>
		`;
	}

	#getAdminBalloonContainer(): HTMLElement
	{
		return Tag.render`
			<span class="user-grid_role-label --admin">
				${Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_ADMIN')}
			</span>
		`;
	}

	#getExtranetBalloonContainer(): HTMLElement
	{
		return Tag.render`
			<span class="user-grid_role-label --extranet">
				${Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_EXTRANET')}
			</span>
		`;
	}

	#getCollaberBalloonContainer(): HTMLElement
	{
		return Tag.render`
			<span class="user-grid_role-label --collaber">
				${Loc.getMessage('INTRANET_JS_CONTROL_BALLOON_COLLABER')}
			</span>
		`;
	}
}
