import { Loc } from 'main.core';
import { Guide } from 'ui.tour';

export default class BoardsGuide
{
	target: HTMLElement | null = null;
	targetSpotlight: HTMLElement | null = null;
	isBoardsPage: boolean;

	guide: Guide | null = null;

	constructor(options)
	{
		this.target = document.querySelector(options.targetSelector);
		this.targetSpotlight = document.querySelector(options.spotlightSelector);
		this.isBoardsPage = options.isBoardsPage;

		if (this.#checkParams())
		{
			this.guide = this.#createGuide(options.id);
		}
		else
		{
			console.error('Unable to create guide');
		}
	}

	start(): void
	{
		if (this.guide === null)
		{
			console.error('Unable to start guide');

			return;
		}

		setTimeout(() => {
			this.guide.scrollToTarget(this.target);
			this.guide.start();
		}, 1000);
	}

	#checkParams(): boolean
	{
		return this.target !== null && this.targetSpotlight !== null;
	}

	#createGuide(id: string): Guide
	{
		const spotlight = this.#createSpotlight();

		const guide = new Guide({
			id,
			simpleMode: true,
			overlay: false,
			onEvents: true,
			autoSave: true,
			steps: [
				{
					target: this.target,
					title: this.#getTitle(),
					text: this.#getText(),
					position: 'bottom',
					condition: {
						color: 'primary',
						bottom: false,
						top: true,
					},
				},
			],
			events: {
				onStart: () => {
					spotlight.show();
				},
				onFinish: () => {
					spotlight.close();
				},
			},
		});

		const guidePopup = guide.getPopup();

		guidePopup.setWidth(380);
		guidePopup.setAngle({
			offset: (this.target.offsetWidth / 2) - (guidePopup.contentContainer.offsetWidth / 2),
		});

		return guide;
	}

	#createSpotlight(): BX.SpotLight
	{
		const spotLight = new BX.SpotLight(
			{
				targetElement: this.targetSpotlight,
				targetVertex: 'middle-center',
				lightMode: true,
			},
		);
		spotLight.getTargetContainer().style.pointerEvents = 'none';

		return spotLight;
	}

	#getTitle(): string
	{
		// noinspection JSAnnotator
		return this.isBoardsPage ? Loc.getMessage('DISK_BOARD_TOUR_TITLE') : Loc.getMessage('DISK_DOCUMENTS_TOUR_TITLE');
	}

	#getText(): string
	{
		// noinspection JSAnnotator
		return this.isBoardsPage ? Loc.getMessage('DISK_BOARD_TOUR_DESCRIPTION') : Loc.getMessage('DISK_DOCUMENTS_TOUR_DESCRIPTION');
	}
}
