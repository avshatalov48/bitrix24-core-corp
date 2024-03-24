import { Guide } from 'ui.tour';
import { Type, Dom, Text } from 'main.core';
import { TourInterface } from 'crm.tour-manager';

const SPOTLIGHT_ID_PREFIX = 'rest_placement_spotlight';
const MODULE_ID = 'crm';
const USER_SEEN_OPTION = 'rest_placement_tour_viewed';
const REST_PLACEMENT_SLIDER_WIDTH = 800;
const CHECK_TARGET_CHANGE_INTERVAL = 1000;

declare type AppContext = {
	applicationId: string,
	placementOptions: Object,
	additionalComponentParam: Object,
	closeCallback: Function,
};

declare type RestPlacementTourData = {
	id: string,
	title: string,
	text: string,
	isCanShowTour: boolean,
	appContext: AppContext,
};

export class Tour implements TourInterface
{
	#id: ?string;
	#title: ?string;
	#text: ?string;
	#isCanShowTour: boolean;
	#appContext: AppContext;
	#isHidden: boolean = false;

	#currentTarget: ?HTMLElement = null;
	#guide: ?Guide = null;
	#spotlight: ?BX.SpotLight = null;

	#checkTargetChangeIntervalID: number = null;

	constructor(data: RestPlacementTourData)
	{
		this.#id = Type.isStringFilled(data.id) ? data.id : null;
		this.#title = Type.isStringFilled(data.title) ? data.title : '';
		this.#text = Type.isStringFilled(data.text) ? data.text : '';
		this.#isCanShowTour = Type.isBoolean(data.isCanShowTour) ? data.isCanShowTour : false;
		this.#appContext = Type.isPlainObject(data.appContext) ? data.appContext : {};
	}

	show(): void
	{
		this.#getSpotlight().show();
		this.getGuide().showNextStep();
		this.#prepareMoreDetailsLink();

		this.#bindEvents();
	}

	canShow(): boolean
	{
		let isValidStringFields = true;
		const stringFields = [this.#id, this.#title, this.#text];
		stringFields.forEach((field) => {
			if (!Type.isStringFilled(field))
			{
				isValidStringFields = false;
			}
		});

		return this.#isCanShowTour
			&& isValidStringFields
			&& Type.isDomNode(this.#getTarget())
		;
	}

	getGuide(): Guide
	{
		if (!this.#guide)
		{
			this.#guide = new Guide({
				onEvents: true,
				steps: [{
					target: this.#getTarget(),
					title: Text.encode(this.#title),
					text: Text.encode(this.#text),
					position: 'bottom',
					rounded: true,
					link: '##',
					events: {
						onClose: () => {
							BX.userOptions.save(MODULE_ID, USER_SEEN_OPTION, this.#id, true);
							this.#getSpotlight().close();

							this.#unbindEvents();
						},
					},
				}],
			});
		}

		return this.#guide;
	}

	#bindEvents(): void
	{
		this.currentTarget = this.#getTarget();
		this.#checkTargetChangeIntervalID = setInterval(this.#onTargetChange.bind(this), CHECK_TARGET_CHANGE_INTERVAL);
	}

	#unbindEvents(): void
	{
		clearInterval(this.#checkTargetChangeIntervalID);
	}

	#onTargetChange(): void
	{
		const possibleNewTarget = this.#getTarget();

		const isTargetVisible = this.#isVisible(possibleNewTarget);
		const isTargetChange = this.#currentTarget !== possibleNewTarget;

		if (isTargetVisible)
		{
			this.#unHide();
		}
		else
		{
			this.#hide();
		}

		if (isTargetChange)
		{
			this.#rebindTarget(possibleNewTarget);
			this.#currentTarget = possibleNewTarget;
		}
	}

	#isVisible(element: HTMLElement): boolean
	{
		return Boolean(
			element.offsetWidth
			|| element.offsetHeight
			|| element.getClientRects().length > 0,
		);
	}

	#hide(): void
	{
		if (this.#isHidden)
		{
			return;
		}

		const guidePopupContainer = this.getGuide().getPopup().getPopupContainer();
		Dom.addClass(guidePopupContainer, '--hidden');

		this.#isHidden = true;
	}

	#unHide(): void
	{
		if (!this.#isHidden)
		{
			return;
		}

		const guidePopup = this.getGuide().getPopup();
		Dom.removeClass(guidePopup.popupContainer, '--hidden');
		guidePopup.adjustPosition();

		this.#isHidden = false;
	}

	#rebindTarget(newTarget: HTMLElement): void
	{
		this.getGuide().getCurrentStep().setTarget(newTarget);
		this.getGuide().showNextStep();
		this.#prepareMoreDetailsLink();

		this.#getSpotlight().setTargetElement(newTarget);
	}

	#getSpotlight(): BX.SpotLight
	{
		if (!this.#spotlight)
		{
			const id = `${SPOTLIGHT_ID_PREFIX}_${this.#id}`;

			this.#spotlight = new BX.SpotLight({
				id,
				targetElement: this.#getTarget(),
				autoSave: 'no',
				targetVertex: 'middle-center',
				zIndex: 200,
			});
		}

		return this.#spotlight;
	}

	#getTarget(): HTMLElement
	{
		let target = document.querySelector(`[data-id="${this.#id}"]`);

		if (target?.offsetTop)
		{
			target = target.parentElement.nextElementSibling;
		}

		return target;
	}

	#prepareMoreDetailsLink(): void
	{
		const moreDetailsLink = this.getGuide().getLink();
		moreDetailsLink.removeAttribute('href');
		moreDetailsLink.removeAttribute('target');

		Dom.style(moreDetailsLink, 'cursor', 'pointer');

		moreDetailsLink.onclick = this.#openAppPlacementSlider.bind(this);
	}

	#openAppPlacementSlider(): void
	{
		const { applicationId, placementOptions, additionalComponentParam, closeCallback } = this.#appContext;

		placementOptions.newUserNotification = 'Y';
		placementOptions.bx24_width = REST_PLACEMENT_SLIDER_WIDTH;

		BX.rest.AppLayout.openApplication(
			applicationId,
			placementOptions,
			additionalComponentParam,
			closeCallback,
		);
	}
}
