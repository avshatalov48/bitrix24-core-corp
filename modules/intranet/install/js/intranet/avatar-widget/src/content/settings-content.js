import { Tag } from 'main.core';
import { Content } from './content';
import { Analytics } from '../analytics';

export class SettingsContent extends Content
{
	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			const onclick = () => {
				Analytics.sendOpenCommonSecurity();
				BX.SidePanel.Instance.open(this.getOptions().path, { width: 1100 });
			};

			return Tag.render`
				<div onclick="${onclick}" class="intranet-avatar-widget-item__wrapper" data-id="bx-avatar-widget-content-settings">
					<i class="ui-icon-set --o-settings intranet-avatar-widget-item__icon"/>
					<span class="intranet-avatar-widget-item__title">${this.getOptions().title}</span>
					<i class="ui-icon-set --chevron-right-m intranet-avatar-widget-item__chevron"/>
				</div>
			`;
		});
	}
}
