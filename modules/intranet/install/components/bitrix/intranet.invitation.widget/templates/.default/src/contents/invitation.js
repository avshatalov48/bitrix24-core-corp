import { Content } from "./content";
import { Tag, Loc } from "main.core";
import type { InvitationContentOptions } from "../types/options";
import type { ConfigContent } from "../types/content";

export class InvitationContent extends Content
{
	constructor(options: InvitationContentOptions) {
		super(options);
		this.setEventNamespace('BX.Intranet.InvitationWidget.InvitationContent');
		this.setOptions(options);
	}

	getConfig(): ConfigContent
	{
		return {
			html: this.getLayout(),
			backgroundColor: '#14bfd5',
		};
	}

	getLayout(): HTMLDivElement
	{
		return this.cache.remember('layout', () => {
			const showInvitationSlider = (e) => {
				e.stopPropagation();
				this.showInvitationPlace(
					Loc.getMessage('INTRANET_INVITATION_WIDGET_DISABLED_TEXT'),
					e.target,
					'default-invitation'
				);
			}

			const showInvitationHelper = () => {
				this.showInfoHelper('limit_why_team_invites');
			}

			return Tag.render`
				<div class="intranet-invitation-widget-invite">
					<div class="intranet-invitation-widget-invite-main">
						<div class="intranet-invitation-widget-inner">
							<div class="intranet-invitation-widget-content">
								<div class="intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--invite"></div>
								<div class="intranet-invitation-widget-item-content">
									<div class="intranet-invitation-widget-item-name">
										<span>
											${Loc.getMessage('INTRANET_INVITATION_WIDGET_INVITE_EMPLOYEE')}
										</span>
									</div>
									<div class="intranet-invitation-widget-item-link">
										<span onclick="${showInvitationHelper}" class="intranet-invitation-widget-item-link-text">
											${Loc.getMessage('INTRANET_INVITATION_WIDGET_DESC')}
										</span>
									</div>
								</div>
							</div>
							<a onclick="${showInvitationSlider}" class="intranet-invitation-widget-item-btn intranet-invitation-widget-item-btn--invite"> 
								${Loc.getMessage('INTRANET_INVITATION_WIDGET_INVITE')}
							</a>
						</div>
					</div>
				</div>
			`;
		});
	}
}
