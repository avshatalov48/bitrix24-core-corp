import { Content } from './content';
import { Tag, Loc } from 'main.core';
import { Analytics } from '../analytics';
import type { StructureContentOptions } from '../types/options';
import type { ConfigContent } from '../types/content';

export class StructureContent extends Content
{
	constructor(options: StructureContentOptions) {
		super(options);
		this.setEventNamespace('BX.Intranet.InvitationWidget.StructureContent');
	}

	getConfig(): ConfigContent
	{
		return {
			html: this.getLayout(),
			flex: 3,
		};
	}

	getLayout(): HTMLDivElement
	{
		return this.cache.remember('layout', () => {
			const onclick = () => {
				Analytics.send(Analytics.EVENT_OPEN_STRUCTURE);
			};

			return Tag.render`
				<div class="intranet-invitation-widget-item intranet-invitation-widget-item--company intranet-invitation-widget-item--active">
					<div class="intranet-invitation-widget-item-logo"></div>
					<div class="intranet-invitation-widget-item-content">
						<div class="intranet-invitation-widget-item-name">
							<span>
								${Loc.getMessage('INTRANET_INVITATION_WIDGET_STRUCTURE')}
							</span>
						</div>
						<a onclick="${onclick}" href="${this.getOptions().link}" class="intranet-invitation-widget-item-btn"> 
							${Loc.getMessage('INTRANET_INVITATION_WIDGET_EDIT')}
						</a>
					</div>
				</div>
			`;
		});
	}
}
