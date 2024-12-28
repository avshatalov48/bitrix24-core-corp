import 'spotlight';

export class Spot
{
	static WIDTH = 380;
	static PATH_TO_IMAGES = '/bitrix/js/tasks/clue/images/spot/';

	#targetElement: HTMLElement;
	#spotlight: ?BX.SpotLight = null;

	constructor()
	{
		if (new.target === Spot)
		{
			throw new Error('This class is abstract and cannot be instantiated directly');
		}
	}

	getWidth(): number
	{
		return Spot.WIDTH;
	}

	getAngleOffset(): number
	{
		return this.#targetElement.offsetWidth / 2;
	}

	isAutoHide(): boolean
	{
		return true;
	}

	setTargetElement(targetElement: HTMLElement): void
	{
		this.#targetElement = targetElement;
	}

	showLight(): void
	{
		this.#spotlight = new BX.SpotLight(
			{
				targetElement: this.#targetElement,
				targetVertex: 'middle-center',
				color: this.getSpotlightColor(),
			},
		);

		this.#spotlight.show();
	}

	close()
	{
		this.#spotlight.close();
	}

	getIconSrc(): ?string
	{
		return null;
	}

	getSpotlightColor(): ?string
	{
		return null;
	}

	getConditionColor(): string
	{
		return 'primary';
	}

	getTitle(): string {}

	getText(): string {}
}
