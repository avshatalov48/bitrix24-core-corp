import { Event } from 'main.core';
import { PopupManager } from 'main.popup';
import 'spotlight';

import { Guide } from 'ui.tour';
import { AutoLauncher } from 'ui.auto-launch';
import { BannerDispatcher } from 'ui.banner-dispatcher';

import { Core } from 'booking.core';
import { AhaMoment, Model, Option } from 'booking.const';
import { optionService } from 'booking.provider.service.option-service';

type Params = {
	id: string,
	title: string,
	text: string,
	article: {
		code: string,
		anchorCode?: string,
	},
	target: HTMLElement,
	targetContainer?: HTMLElement,
	top?: boolean,
};

class AhaMoments
{
	#bookingForAhaMoment: number;
	#shownPopups: { [name: string]: boolean } = {};

	show(params: Params): Promise<void>
	{
		if (!AutoLauncher.isEnabled())
		{
			AutoLauncher.enable();
		}

		return new Promise((resolve) => {
			BannerDispatcher.critical.toQueue(async (onDone) => {
				await this.showGuide(params);

				onDone();
				resolve();
			});
		});
	}

	showGuide(params: Params): Promise<void>
	{
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
					article: params.article.code,
					articleAnchor: params.article.anchorCode,
				},
			],
			targetContainer: params.targetContainer,
		});

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

			Event.bind(document, 'scroll', adjustPosition, true);
		});
	}

	shouldShow(ahaMoment: $Values<typeof AhaMoment>, params: Object = {}): boolean
	{
		return {
			[AhaMoment.Banner]: this.#shouldShowBanner(ahaMoment),
			[AhaMoment.TrialBanner]: this.#wasNotShown(ahaMoment),
			[AhaMoment.AddResource]: this.#shouldShowAddResource(),
			[AhaMoment.MessageTemplate]: this.#wasNotShown(ahaMoment),
			[AhaMoment.AddClient]: this.#shouldShowAddClient(params),
			[AhaMoment.ResourceWorkload]: this.#wasNotShown(ahaMoment),
			[AhaMoment.ResourceIntersection]: this.#shouldShowResourceIntersection(),
			[AhaMoment.ExpandGrid]: this.#shouldShowExpandGrid(),
			[AhaMoment.SelectResources]: this.#shouldShowSelectResources(),
		}[ahaMoment];
	}

	setShown(ahaMoment: $Values<typeof AhaMoment>): void
	{
		const optionName = this.#getOptionName(ahaMoment);

		this.setPopupShown(ahaMoment);

		void optionService.setBool(optionName, true);
	}

	setPopupShown(ahaMoment: $Values<typeof AhaMoment>): void
	{
		this.#shownPopups[ahaMoment] = true;
	}

	setBookingForAhaMoment(bookingId: number): void
	{
		this.#bookingForAhaMoment ??= bookingId;
	}

	#shouldShowBanner(ahaMoment: $Values<typeof AhaMoment>): boolean
	{
		const canTurnOnDemo = Core.getStore().getters[`${Model.Interface}/canTurnOnDemo`];

		if (canTurnOnDemo)
		{
			return true;
		}
		else
		{
			return this.#wasNotShown(ahaMoment);
		}
	}

	#shouldShowAddResource(): boolean
	{
		const wasNotShown = this.#wasNotShown(AhaMoment.AddResource);
		const isLoaded = Core.getStore().getters[`${Model.Interface}/isLoaded`];
		const resourcesIds = Core.getStore().getters[`${Model.Interface}/resourcesIds`];

		return wasNotShown && isLoaded && resourcesIds.length === 0;
	}

	#shouldShowAddClient(params: { bookingId: number }): boolean
	{
		const wasNotShown = this.#wasNotShown(AhaMoment.AddClient);
		const isBookingForAhaMoment = this.#bookingForAhaMoment === params.bookingId;

		return wasNotShown && isBookingForAhaMoment;
	}

	#shouldShowResourceIntersection(): boolean
	{
		const wasNotShown = this.#wasNotShown(AhaMoment.ResourceIntersection);
		const isLoaded = Core.getStore().getters[`${Model.Interface}/isLoaded`];
		const resourcesIds = Core.getStore().getters[`${Model.Interface}/resourcesIds`];

		return wasNotShown && isLoaded && resourcesIds.length >= 2 && !PopupManager.isAnyPopupShown();
	}

	#shouldShowExpandGrid(): boolean
	{
		const wasNotShown = this.#wasNotShown(AhaMoment.ExpandGrid);
		const previousAhaMomentsShown = [AhaMoment.ResourceWorkload, AhaMoment.ResourceWorkload]
			.every((ahaMoment) => !this.#wasNotShown(ahaMoment))
		;

		return wasNotShown && previousAhaMomentsShown && !PopupManager.isAnyPopupShown();
	}

	#shouldShowSelectResources(): boolean
	{
		const wasNotShown = this.#wasNotShown(AhaMoment.SelectResources);
		const previousAhaMomentShown = !this.#wasNotShown(AhaMoment.ExpandGrid);

		return wasNotShown && previousAhaMomentShown && !PopupManager.isAnyPopupShown();
	}

	#wasNotShown(ahaMoment: $Values<typeof AhaMoment>): boolean
	{
		const { ahaMoments } = Core.getParams();

		return ahaMoments[ahaMoment] && !this.#shownPopups[ahaMoment];
	}

	#getOptionName(ahaMoment: $Values<typeof AhaMoment>): $Values<typeof Option>
	{
		return {
			[AhaMoment.Banner]: Option.AhaBanner,
			[AhaMoment.TrialBanner]: Option.AhaTrialBanner,
			[AhaMoment.AddResource]: Option.AhaAddResource,
			[AhaMoment.MessageTemplate]: Option.AhaMessageTemplate,
			[AhaMoment.AddClient]: Option.AhaAddClient,
			[AhaMoment.ResourceWorkload]: Option.AhaResourceWorkload,
			[AhaMoment.ResourceIntersection]: Option.AhaResourceIntersection,
			[AhaMoment.ExpandGrid]: Option.AhaExpandGrid,
			[AhaMoment.SelectResources]: Option.AhaSelectResources,
		}[ahaMoment];
	}
}

export const ahaMoments = new AhaMoments();
