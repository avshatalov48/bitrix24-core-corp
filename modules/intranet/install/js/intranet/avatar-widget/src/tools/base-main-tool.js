import { Tag } from 'main.core';
import { BaseTool } from './base-tool';

export class BaseMainTool extends BaseTool
{
	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			return Tag.render`
				<div data-testid="bx-avatar-widget-main-tool-${this.getId()}" onclick="${this.onClick.bind(this)}" class="intranet-avatar-widget-main-tool__wrapper">
					<div class="intranet-avatar-widget-main-tool-icon__wrapper">
						${this.getIconElement()}
						${this.#getCounterWrapper()}
					</div>
					<div class="intranet-avatar-widget-main-tool__title">
						${this.getTitle()}
					</div>
				</div>
			`;
		});
	}

	getIconElement(): HTMLElement
	{
		return this.cache.remember('icon', () => {
			return Tag.render`<i class="ui-icon-set ${this.getIconClass()} intranet-avatar-widget-main-tool__icon"/>`;
		});
	}

	getCounter(): ?Counter
	{
		return null;
	}

	getId(): string
	{
		return '';
	}

	#getCounterWrapper(): ?HTMLElement
	{
		return this.cache.remember('counterWrapper', () => {
			const counter = this.getCounter();

			return Tag.render`
				<div class="intranet-avatar-widget-main-tool__counter-wrapper">
					${counter?.render()}
				</div>
			`;
		});
	}
}
