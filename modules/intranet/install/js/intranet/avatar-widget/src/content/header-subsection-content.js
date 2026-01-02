import { Content } from './content';
import { Tag } from 'main.core';
import { AirButtonStyle, Button } from 'ui.buttons';
import { EventEmitter } from 'main.core.events';

export class HeaderSubsectionContent extends Content
{
	getConfig(): Object
	{
		return {
			html: this.getLayout(),
		};
	}

	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			return Tag.render`
				<div data-testid="bx-avatar-widget-content-${this.getId()}" class="intranet-avatar-widget-item__wrapper">
					<div class="intranet-avatar-widget-item-subsection__header">
						${this.#getBackButton().render()}
						<span class="intranet-avatar-widget-item-subsection__title">
							${this.getOptions().title}
						</span>
					</div>
					${this.getContentWrapper()}
				</div>
			`;
		});
	}

	#getBackButton(): Button
	{
		return this.cache.remember('backButton', () => {
			const button = new Button({
				icon: 'chevron-left-l',
				size: Button.Size.EXTRA_EXTRA_SMALL,
				style: AirButtonStyle.OUTLINE,
				useAirDesign: true,
				onclick: () => {
					EventEmitter.emit('BX.Intranet.AvatarWidget.Subsection:back');
				},
			});
			button.setCollapsed(true);

			return button;
		});
	}

	getId(): string
	{
		return '';
	}
}
