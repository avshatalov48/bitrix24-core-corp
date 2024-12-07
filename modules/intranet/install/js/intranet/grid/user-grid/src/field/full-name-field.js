import { BaseField } from './base-field';
import { Dom, Tag, Loc, Text } from 'main.core';
import { Label, LabelColor } from 'ui.label';

export type FullNameFieldType = {
	fullName: string,
	profileLink: string,
	isConfirmed: boolean,
	isAdmin: boolean,
	isExtranet: boolean,
	position: ?string,
	isInvited: boolean,
	photoUrl: string,
	isIntegrator: boolean,
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
			Dom.append(this.#getPositionContainer(Text.encode(params.position)), fullNameContainer);
		}

		if (!params.isInvited && params.isConfirmed)
		{
			if (params.isIntegrator)
			{
				Dom.append(this.#getIntegratorBalloonContainer(), fullNameContainer);
			}
			else if (params.isAdmin)
			{
				Dom.append(this.#getAdminBalloonContainer(), fullNameContainer);
			}
			else if (params.isExtranet)
			{
				Dom.append(this.#getExtranetBalloonContainer(), fullNameContainer);
			}
		}
		else if (params.isInvited && !params.isConfirmed)
		{
			Dom.append(this.#getInviteNotConfirmedContainer(), fullNameContainer);
		}
		else if (params.isInvited)
		{
			Dom.append(this.#getInviteNotAcceptedContainer(), fullNameContainer);
		}

		this.appendToFieldNode(fullNameContainer);
	}

	#getFullNameLink(fullName: string, profileLink: string): HTMLElement
	{
		return Tag.render`<a class="user-grid_full-name-label" href="${profileLink}">${fullName}</a>`;
	}

	#getInviteNotAcceptedContainer(): HTMLElement
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

	#getInviteNotConfirmedContainer(): HTMLElement
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

	#getPositionContainer(position: string): HTMLElement
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
}
