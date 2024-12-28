import { Content } from './content';
import { Counter } from 'ui.cnt';
import { Tag, Loc, Type, Dom } from 'main.core';
import { Analytics } from '../analytics';
import type { StructureContentOptions } from '../types/options';
import type { ConfigContent } from '../types/content';
import { EventEmitter } from 'main.core.events';

export class StructureContent extends Content
{
	constructor(options: StructureContentOptions) {
		super(options);
		this.setEventNamespace('BX.Intranet.InvitationWidget.StructureContent');
	}

	getConfig(): ConfigContent
	{
		this.#showCounter();

		if (this.getOptions().shouldShowStructureCounter)
		{
			EventEmitter.subscribeOnce('HR.company-structure:first-popup-showed', this.#onFirstWatchNewStructure.bind(this));
		}

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

	#getCounterWrapper(): HTMLElement
	{
		return this.cache.remember('counter-wrapper', () => {
			return this.getLayout().querySelector('.intranet-invitation-widget-item-name');
		});
	}

	#showCounter(): void
	{
		if (this.#getCounterValue() > 0)
		{
			Dom.addClass(this.#getCounter().getContainer(), 'invitation-structure-counter');
			this.#getCounter().renderTo(this.#getCounterWrapper());
		}
	}

	#getCounter(): Counter
	{
		return this.cache.remember('counter', () => {
			return new Counter({
				value: this.#getCounterValue(),
				color: Counter.Color.DANGER,
			});
		});
	}

	#getCounterValue(): number
	{
		return (this.getOptions().shouldShowStructureCounter) ? 1 : 0;
	}

	#onFirstWatchNewStructure(): void
	{
		const value = this.#getCounter().value;
		if (!Type.isNumber(value))
		{
			return;
		}

		if (!this.getOptions().shouldShowStructureCounter)
		{
			return;
		}

		this.getOptions().shouldShowStructureCounter = false;
		this.#getCounter().destroy();
		this.cache.delete('counter');
	}
}
