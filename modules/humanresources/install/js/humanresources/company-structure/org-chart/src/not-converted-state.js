import { EventEmitter, type BaseEvent } from 'main.core.events';
import { events } from './consts';
import { Dom, Loc, Tag } from 'main.core';
import { chartAPI } from './api';

export class NotConvertedState
{
	static updateConvertedStatusInterval;

	static #subscribeOnEvents(): void
	{
		const onCloseByEsc = (event: BaseEvent) => {
			const [sidePanelEvent] = event.data;
			sidePanelEvent.denyAction();
		};

		const onClose = () => {
			EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
			EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE, onClose);
			clearInterval(this.updateConvertedStatusInterval);
		};
		EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
		EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE, onClose);
	}

	static async mount(containerId: string): Promise<void>
	{
		const container = document.getElementById(containerId);
		Dom.append(this.#getNotConvertedStateScreen(), container);
		this.#subscribeOnEvents();

		const updateTimeMs = 30000;
		this.updateConvertedStatusInterval = setInterval(async () => {
			try
			{
				await chartAPI.getDictionary();
			}
			catch (e)
			{
				if (e.code === 'STRUCTURE_IS_NOT_CONVERTED')
				{
					return;
				}

				if (e.code === 'STRUCTURE_ACCESS_DENIED')
				{
					clearInterval(this.updateConvertedStatusInterval);
					window.location.reload();
				}

				throw e;
			}

			clearInterval(this.updateConvertedStatusInterval);
			window.location.reload();
		}, updateTimeMs);
	}

	static #getNotConvertedStateScreen(): HTMLElement
	{
		return Tag.render`
			<div class="humanresources-not-converted-state-screen">
				<div class="humanresources-not-converted-state-screen__icon"></div>
				<div class="humanresources-not-converted-state-screen__title">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_NOT_CONVERTED_SCREEN_LOADING_TITLE')}
				</div>
				<div class="humanresources-not-converted-state-screen__description">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_NOT_CONVERTED_SCREEN_LOADING_DESCRIPTION')}
				</div>
			</div>
		`;
	}
}
