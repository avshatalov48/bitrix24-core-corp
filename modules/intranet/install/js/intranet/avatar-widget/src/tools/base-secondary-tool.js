import { BaseTool } from './base-tool';
import { Tag } from 'main.core';
import { Counter } from 'ui.cnt';

export class BaseSecondaryTool extends BaseTool
{
	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			return Tag.render`
				<div data-testid="bx-avatar-widget-tool-${this.getId()}" onclick="${this.onClick.bind(this)}" class="intranet-avatar-widget-secondary-tool__wrapper">
					${this.getIconElement()}
					<div class="intranet-avatar-widget-item__info-wrapper">
						<span class="intranet-avatar-widget-item__title">
							${this.getTitle()}
						</span>
					</div>
					${this.#getCounterWrapper()}
					${this.getActionElement()}
				</div>
			`;
		});
	}

	getIconElement(): HTMLElement
	{
		return this.cache.remember('icon', () => {
			return Tag.render`<i class="ui-icon-set ${this.getIconClass()} intranet-avatar-widget-secondary-tool__icon"/>`;
		});
	}

	getActionElement(): HTMLElement
	{
		return this.cache.remember('actionElement', () => {
			return Tag.render`<i class="ui-icon-set --chevron-right-m intranet-avatar-widget-item__chevron"/>`;
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

	#getCounterWrapper(): HTMLElement
	{
		return this.cache.remember('counterWrapper', () => {
			const counter = this.getCounter();

			return Tag.render`
				<div class="intranet-avatar-widget-item__counter">
					${counter?.render()}
				</div>
			`;
		});
	}
}
