import { TourInterface } from 'crm.tour-manager';
import { Dom, Event, Loc, Runtime, Type } from 'main.core';
import { Guide } from 'ui.tour';

const SPOTLIGHT_ID_PREFIX = 'spotlight-crm-timeline-menubar';
const SPOTLIGHT_TARGET_VERTEX = 'middle-center';
const SPOTLIGHT_Z_INDEX = 200;

const GUIDE_LINK_CLASS_NAME = 'crm-entity-stream-content-new-detail-guide-link';
const GUIDE_POPUP_WIDTH = 400;
const GUIDE_POPUP_POSITION = 'bottom';

export type MenuBarTourParams = {
	itemCode: string,
	title: string,
	text: string,
	articleCode: ?number,
	userOptionName: ?string,
	linkTitle: ?string,
	guideBindElement: ?HTMLElement,
	guidePopupWidth: ?number,
};

export class BaseTour implements TourInterface
{
	#params: MenuBarTourParams = {};

	#spotlight: ?BX.SpotLight = null;
	#guide: ?Guide = null;
	#guideBindElement: ?HTMLElement = null;
	#targetElementRect: ?DOMRect = null;
	#observerTimeoutId: number = null;

	constructor(params: MenuBarTourParams)
	{
		if (!this.#assertValidParams(params))
		{
			throw new TypeError('Invalid menu bar tour params');
		}

		this.#params = params;
		this.onWindowResize = Runtime.debounce(this.onWindowResize.bind(this), 100);

		Event.bind(window, 'resize', this.onWindowResize);
	}

	onWindowResize(): void
	{
		const target = this.#getGuideBindElement(true);

		this.#guide.getCurrentStep().setTarget(target);
		this.#guide.showNextStep();

		this.#spotlight.setTargetElement(target);
	}

	canShow(): boolean
	{
		return true;
	}

	show(): void
	{
		this.#spotlight = this.#getSpotlight();
		this.#spotlight.show();
		this.getGuide().showNextStep();
	}

	getGuide(): Guide
	{
		if (!this.#guide)
		{
			const guideCfg = {
				onEvents: true,
				steps: [{
					target: this.#getGuideBindElement(),
					title: this.#params.title,
					text: this.#params.text,
					position: GUIDE_POPUP_POSITION,
					rounded: true,
					events: {
						onClose: () => {
							this.saveUserOption(this.#params.userOptionName);
							this.#spotlight.close();
							if (this.#observerTimeoutId)
							{
								clearInterval(this.#observerTimeoutId);
								this.#observerTimeoutId = null;
							}

							Event.unbind(window, 'resize', this.onWindowResize);
						},
					},
				}],
			};

			if (this.#params.articleCode > 0)
			{
				guideCfg.steps[0].article = this.#params.articleCode;
				guideCfg.steps[0].linkTitle = this.#params.linkTitle ?? Loc.getMessage('CRM_TIMELINE_DETAILS');
			}

			const guide = new Guide(guideCfg);

			const guidePopup = guide.getPopup();
			guidePopup.setWidth(this.#params.guidePopupWidth ?? GUIDE_POPUP_WIDTH);

			const link = guidePopup.contentContainer.querySelector('.ui-tour-popup-link');
			Dom.addClass(link, GUIDE_LINK_CLASS_NAME);

			this.#targetElementRect = Dom.getPosition(this.#getGuideBindElement());
			this.#observerTimeoutId = setInterval(this.#handleTargetElementResize.bind(this), 1000);

			this.#guide = guide;
		}

		return this.#guide;
	}

	saveUserOption(optionName: ?string = null): void
	{
		// eslint-disable-next-line no-console
		console.warn('Method save is not implemented');
	}

	#getSpotlight(): BX.SpotLight
	{
		return new BX.SpotLight(
			{
				id: `${SPOTLIGHT_ID_PREFIX}-${this.#params.itemCode}-guide`,
				targetElement: this.#getGuideBindElement(),
				targetVertex: SPOTLIGHT_TARGET_VERTEX,
				zIndex: SPOTLIGHT_Z_INDEX,
				autoSave: 'no',
			},
		);
	}

	#getGuideBindElement(force: boolean = false): HTMLElement
	{
		if (Type.isDomNode(this.#params.guideBindElement))
		{
			this.#guideBindElement = this.#params.guideBindElement;

			return this.#guideBindElement;
		}

		if (!this.#guideBindElement || force)
		{
			this.#guideBindElement = document.querySelector(`[data-id="${this.#params.itemCode}"]`);
			if (this.#guideBindElement.offsetTop)
			{
				this.#guideBindElement = this.#guideBindElement.parentElement.nextElementSibling;
			}
		}

		return this.#guideBindElement;
	}

	#handleTargetElementResize(): void
	{
		const currentRect = Dom.getPosition(this.#getGuideBindElement());

		if (
			currentRect.left !== this.#targetElementRect.left
			|| currentRect.right !== this.#targetElementRect.right
			|| currentRect.top !== this.#targetElementRect.top
			|| currentRect.bottom !== this.#targetElementRect.bottom
		)
		{
			this.#targetElementRect = Dom.getPosition(this.#guideBindElement);

			const targetElement = this.#guideBindElement;
			const isVisible = Boolean(
				targetElement.offsetWidth
				|| targetElement.offsetHeight
				|| targetElement.getClientRects().length > 0,
			);

			const guidePopup = this.#guide.getPopup();
			if (isVisible)
			{
				Dom.removeClass(guidePopup.popupContainer, '--hidden');
				guidePopup.adjustPosition();
			}
			else
			{
				Dom.addClass(guidePopup.popupContainer, '--hidden');
			}
		}
	}

	#assertValidParams(params: MenuBarTourParams): boolean
	{
		if (!Type.isPlainObject(params))
		{
			console.error('"params" must be specified');

			return false;
		}

		if (!Type.isStringFilled(params.title))
		{
			console.error('"title" must be specified');

			return false;
		}

		if (!Type.isStringFilled(params.text))
		{
			console.error('"text" must be specified');

			return false;
		}

		return true;
	}
}
