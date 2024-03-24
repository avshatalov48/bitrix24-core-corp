import { Dom, Event, Loc, Reflection, Runtime } from 'main.core';
import { Guide } from 'ui.tour';
import { TourInterface } from 'crm.tour-manager';
import './gotochat.css';

const UserOptions = Reflection.namespace('BX.userOptions');

const ARTICLE_CODE = '18114500';

/** @memberof BX.Crm.Timeline.MenuBar.GoToChat */
export default class Tour implements TourInterface
{
	#guideBindElement: ?HTMLElement = null;
	#targetElementRect: ?DOMRect = null;
	#observerTimeoutId: number = null;
	#guide: ?Guide = null;
	#spotlight: ?BX.SpotLight = null;

	constructor()
	{
		this.onWindowResize = Runtime.debounce(this.onWindowResize.bind(this), 100);

		Event.bind(window, 'resize', this.onWindowResize);
	}

	canShow(): boolean
	{
		return true;
	}

	show(): void
	{
		this.#spotlight = this.#createSpotlight();
		this.#spotlight.show();

		this.getGuide().showNextStep();
	}

	getGuide(): Guide
	{
		if (!this.#guide)
		{
			this.#guide = this.#createGuide(
				this.getGuideBindElement(),
				{
					onClose: () => {
						UserOptions.save('crm', 'gotochat', 'isTimelineTourViewedInWeb', 1);
						this.#spotlight.close();
						if (this.#observerTimeoutId)
						{
							clearInterval(this.#observerTimeoutId);
							this.#observerTimeoutId = null;
						}

						Event.unbind(window, 'resize', this.onWindowResize);
					},
				},
			);
		}

		return this.#guide;
	}

	onWindowResize()
	{
		const target = this.getGuideBindElement(true);

		this.#guide.getCurrentStep().setTarget(target);
		this.#guide.showNextStep();

		this.#spotlight.setTargetElement(target);
	}

	#createSpotlight()
	{
		return new BX.SpotLight(
			{
				id: 'spotlight-crm-timeline-gotochat-guide',
				targetElement: this.getGuideBindElement(),
				autoSave: 'no',
				targetVertex: 'middle-center',
				zIndex: 200,
			},
		);
	}

	getGuideBindElement(force: boolean = false): HTMLElement
	{
		if (!this.#guideBindElement || force)
		{
			this.#guideBindElement = document.querySelector('[data-id="gotochat"]');

			if (this.#guideBindElement.offsetTop)
			{
				this.#guideBindElement = this.#guideBindElement.parentElement.nextElementSibling;
			}
		}

		return this.#guideBindElement;
	}

	#createGuide(target: HTMLElement, guideEvents: Object = {}): Guide
	{
		const guideText = {
			title: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_GUIDE_TITLE'),
			text: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_GUIDE_TEXT'),
		};

		const guide = new Guide({
			onEvents: true,
			steps: [
				{
					target,
					title: guideText.title,
					text: guideText.text,
					position: 'bottom',
					events: guideEvents,
					article: ARTICLE_CODE,
					linkTitle: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_GUIDE_ARTICLE_TITLE'),
					rounded: true,
				},
			],
		});

		const guidePopup = guide.getPopup();
		guidePopup.setWidth(400);

		const link = guidePopup.contentContainer.querySelector('.ui-tour-popup-link');
		Dom.addClass(link, 'crm-entity-stream-content-new-detail-gotochat-guide-link');

		this.#targetElementRect = Dom.getPosition(this.getGuideBindElement());

		this.#observerTimeoutId = setInterval(this.handleTargetElementResize.bind(this), 1000);

		return guide;
	}

	handleTargetElementResize()
	{
		const currentRect = Dom.getPosition(this.getGuideBindElement());

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
				|| targetElement.getClientRects().length > 0
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
}
