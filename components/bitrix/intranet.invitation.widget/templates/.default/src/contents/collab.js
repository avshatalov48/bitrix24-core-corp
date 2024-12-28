import { Content } from './content';
import { Tag, Loc } from 'main.core';
import type { ConfigContent } from '../types/content';
import type { CollabContentOptions } from '../types/options';
import { Analytics } from '../analytics';

export class CollabContent extends Content
{
	articleCode: string = '22706764';
	#openChat: ?func;

	constructor(options: CollabContentOptions) {
		super(options);
		this.setEventNamespace('BX.Intranet.InvitationWidget.CollabContent');
	}

	getConfig(): ConfigContent
	{
		return {
			html: this.getOptions().awaitData.then((response) => {
				const { Messenger, CreatableChat } = response;
				this.#openChat = () => {
					Messenger.openChatCreation(CreatableChat.collab);
					Analytics.sendCreateCollab();
				};

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
				this.#openChat();
				e.stopPropagation();
			};

			const showCollabHelper = () => {
				BX.Helper.show(`redirect=detail&code=${this.articleCode}`);
				this.sendAnalytics(this.articleCode);
			};

			return Tag.render`
				<div class="${this.getWrapperClass()}">
					<div class="intranet-invitation-widget-content">
						<div class="intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--collab">
							<div class="ui-icon-set --collab"></div>
						</div>
						<div class="intranet-invitation-widget-item-content">
							<div class="intranet-invitation-widget-item-name">
								<span>
									${Loc.getMessage('INTRANET_INVITATION_WIDGET_COLLAB')}
								</span>
							</div>
							<div class="intranet-invitation-widget-item-link">
								<span onclick="${showCollabHelper}" class="intranet-invitation-widget-item-link-text">
									${Loc.getMessage('INTRANET_INVITATION_WIDGET_COLLAB_DESC')}
								</span>
							</div>
						</div>
					</div>
					<button onclick="${showInvitationSlider}" class="intranet-invitation-widget-item-btn intranet-invitation-widget-item-btn--collab">
						${Loc.getMessage('INTRANET_INVITATION_WIDGET_COLLAB_CREATE')}
					</button>
				</div>
			`;
		});
	}

	getWrapperClass(): string
	{
		return this.cache.remember('wrapper-class', () => {
			return 'intranet-invitation-widget-item intranet-invitation-widget-item--wide intranet-invitation-widget-item--collab';
		});
	}
}
