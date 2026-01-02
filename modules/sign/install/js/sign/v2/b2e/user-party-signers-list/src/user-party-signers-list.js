import { Tag, Loc } from 'main.core';
import { UserParty } from 'sign.v2.b2e.user-party';
import './style.css';

export class UserPartySignersList extends UserParty
{
	constructor()
	{
		super({ mode: 'edit' });
	}

	getLayout(region: string): HTMLElement
	{
		if (this.ui.container)
		{
			return this.ui.container;
		}

		this.ui.itemContainer = Tag.render`
			<div class="sign-document-b2e-user-party__item-list"></div>
		`;

		return Tag.render`
			<div class="sign-b2e-settings_signers-list">
				<div class="sign-b2e-settings__header-wrapper">
					<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_USER_PARTY_HEADER')}</h1>
					${this.userPartyCounters.getLayout()}
				</div>
				<div class="sign-b2e-settings__item">
					${this.ui.itemContainer}
				</div>
			</div>
		`;
	}

	isRejectExcludedEnabled(): boolean
	{
		return false;
	}
}
