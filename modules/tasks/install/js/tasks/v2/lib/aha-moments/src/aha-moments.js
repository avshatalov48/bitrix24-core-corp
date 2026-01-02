import { Dom, Event } from 'main.core';
import 'spotlight';

import { Guide } from 'ui.tour';

import { Core } from 'tasks.v2.core';
import { Option } from 'tasks.v2.const';
import { optionService } from 'tasks.v2.provider.service.option-service';

type Params = {
	id: string,
	ahaMoment: string,
	title: string,
	text: string,
	target: HTMLElement,
	article?: {
		code: string,
		anchorCode?: string,
		title?: string,
	},
	targetContainer?: HTMLElement,
	top?: boolean,
	isPulsarTransparent?: boolean,
};

class AhaMoments
{
	#ahaPopupWidth: number = 380;
	#shownPopups: { [name: string]: boolean } = {};

	show(params: Params): Promise<void>
	{
		if (params.ahaMoment && this.#wasShown(params.ahaMoment))
		{
			return;
		}

		const guide = new Guide({
			id: params.id,
			overlay: false,
			simpleMode: true,
			onEvents: true,
			steps: [
				{
					target: params.target,
					title: params.title,
					text: params.text,
					position: params.top ? 'top' : 'bottom',
					condition: {
						top: !params.top,
						bottom: Boolean(params.top),
						color: 'primary',
					},
					article: params.article?.code,
					articleAnchor: params.article?.anchorCode,
					linkTitle: params.article?.title,
				},
			],
			targetContainer: params.targetContainer,
		});

		// default is 280
		if (this.#ahaPopupWidth)
		{
			guide.getPopup().setWidth(this.#ahaPopupWidth);
		}

		const pulsar = new BX.SpotLight(
			{
				targetElement: params.target,
				targetVertex: 'middle-center',
				color: 'var(--ui-color-primary)',
			},
		);

		return new Promise((resolve) => {
			const guidePopup = guide.getPopup();

			guidePopup.setAutoHide(true);
			guidePopup.setAngle({ offset: params.target.offsetWidth / 2 });

			const adjustPosition = () => {
				guidePopup.adjustPosition();
			};

			const onClose = () => {
				pulsar.close();
				Event.unbind(document, 'scroll', adjustPosition, true);
				resolve();
			};

			guidePopup.subscribe('onClose', onClose);
			guidePopup.subscribe('onDestroy', onClose);

			pulsar.show();
			guide.start();

			guidePopup.adjustPosition({
				forceTop: !params.top,
				forceBindPosition: true,
			});

			if (params.isPulsarTransparent)
			{
				Dom.style(pulsar.container, 'pointer-events', 'none');
			}

			Event.bind(document, 'scroll', adjustPosition, true);
		});
	}

	shouldShow(ahaMoment: $Values<typeof Option>, params: Object = {}): boolean
	{
		return {
			[Option.AhaAuditorsInCompactFormPopup]: this.#shouldShowAuditorsInCard(),
		}[ahaMoment];
	}

	setShown(ahaMoment: $Values<typeof Option>): void
	{
		this.setPopupShown(ahaMoment);

		void optionService.setBool(ahaMoment, true);
	}

	setPopupShown(ahaMoment: $Values<typeof Option>): void
	{
		this.#shownPopups[ahaMoment] = true;
	}

	#shouldShowAuditorsInCard(): boolean
	{
		return this.#wasNotShown(Option.AhaAuditorsInCompactFormPopup);
	}

	#wasNotShown(ahaMoment: $Values<typeof Option>): boolean
	{
		return !this.#wasShown(ahaMoment);
	}

	#wasShown(ahaMoment: $Values<typeof Option>): boolean
	{
		const { ahaMoments } = Core.getParams();

		return !ahaMoments[ahaMoment] || this.#shownPopups[ahaMoment];
	}
}

export const ahaMoments = new AhaMoments();
