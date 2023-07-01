import { Content } from "./content";
import { Tag, Loc } from "main.core";
import type { ConfigContent } from "../types/content";
import type { ExtranetContentOptions } from "../types/options";

export class ExtranetContent extends Content
{
	articleCode: string = "6770709";

	constructor(options: ExtranetContentOptions) {
		super(options);
		this.setEventNamespace('BX.Intranet.InvitationWidget.ExtranetContent');
	}

	getConfig(): ConfigContent
	{
		return {
			html: this.getOptions().awaitData.then((response) => {
				this.setOptions({
					...response.data.users,
					...this.getOptions(),
				});
				return this.getLayout();
			}),
			minHeight: '55px',
			sizeLoader: 37,
			marginBottom: 24,
			secondary: true,
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
					'extranet'
				);
			}

			const showExtranetHelper = () => {
				BX.Helper.show(`redirect=detail&code=${this.articleCode}`);
				this.sendAnalytics(this.articleCode);
			}

			return Tag.render`
				<div class="${this.getWrapperClass()}">
					<div class="intranet-invitation-widget-content">
						<div class="intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--ext"></div>
						<div class="intranet-invitation-widget-item-content">
							<div class="intranet-invitation-widget-item-name">
								<span>
									${Loc.getMessage('INTRANET_INVITATION_WIDGET_EXTRANET')}
								</span>
							</div>
							<div class="intranet-invitation-widget-item-link">
								<span onclick="${showExtranetHelper}" class="intranet-invitation-widget-item-link-text">
									${Loc.getMessage('INTRANET_INVITATION_WIDGET_EXTRANET_DESC')}
								</span>
							</div>
							${this.getCountUserMessage()}
						</div>
					</div>
					<button onclick="${showInvitationSlider}" class="intranet-invitation-widget-item-btn">
						${Loc.getMessage('INTRANET_INVITATION_WIDGET_INVITE')}
					</button>
				</div>
			`;
		});
	}

	getWrapperClass(): string
	{
		return this.cache.remember('wrapper-class', () => {
			const baseClass = 'intranet-invitation-widget-item intranet-invitation-widget-item--wide';

			if (this.getOptions().currentExtranetUserCount > 0)
			{
				return baseClass + ' intranet-invitation-widget-item--active';
			}

			return baseClass;
		});
	}

	getCountUserMessage(): ?HTMLDivElement
	{
		return this.cache.remember('count-user-message', () => {
			if (this.getOptions().currentExtranetUserCount > 0)
			{
				return Tag.render`
					<div class="intranet-invitation-widget-item-ext-users">
						${this.getOptions().currentExtranetUserCountMessage}
					</div>
				`;
			}

			return null;
		});
	}
}
