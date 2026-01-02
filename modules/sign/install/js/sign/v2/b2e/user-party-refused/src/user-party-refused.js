import { Tag, Loc, Event } from 'main.core';
import { Switcher, SwitcherSize, SwitcherColor } from 'ui.switcher';
import './user-party-refused.css';
import 'ui.design-tokens';
import { Helpdesk } from 'sign.v2.helper';

const HelpdeskCodes = Object.freeze({
	RejectedListDetails: '25375700',
});

export class UserPartyRefused extends Event.EventEmitter
{
	#switcher: Switcher;

	constructor()
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2E.UserPartyRefused');

		this.#switcher = new Switcher({
			size: SwitcherSize.extraSmall,
			color: SwitcherColor.primary,
			checked: true,
			handlers: {
				toggled: () => {
					this.emit('onChange', { checked: this.#switcher.isChecked() });
				},
			},
		});
	}

	shouldRemoveRefused(): boolean
	{
		return this.#switcher.isChecked();
	}

	render(): ?HTMLElement
	{
		return Tag.render`
			<div class="sign-user-party-refused">
				${this.#switcher.getNode()}
				<span class="sign-user-party-refused-desc">
					${Helpdesk.replaceLink(
						Loc.getMessage('SIGN_B2E_USER_PARTY_REJECTED_DESC_MSGVER_1'),
						HelpdeskCodes.RejectedListDetails,
					)}
				</span>
			</div>
		`;
	}
}
