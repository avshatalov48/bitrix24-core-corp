import { Dom, Loc, Tag, Text, Type } from 'main.core';

const Trend = Object.freeze({
	up: 1,
	down: -1,
	noChanges: 0,
});

type TrendValue = Trend.up | Trend.down | Trend.noChanges;

const ARTICLE_CODE = '23240682';
const ARTICLE_ANCHOR = 'rate';

export class Rating
{
	#id: string;
	#rating: ?number = null;
	#prevRating: ?number = null;
	#userPhotoUrl: ?string = null;
	#articleCode: string = ARTICLE_CODE;
	#articleAnchor: string = ARTICLE_ANCHOR;
	#useSkeletonMode: boolean = true;

	constructor()
	{
		this.#id = `crm.ai.call.quality-rating-${Text.getRandom()}`;
	}

	render(): HTMLElement
	{
		const content = (
			this.#useSkeletonMode
				? this.#getSkeleton()
				: this.#getContent()
		);

		return Tag.render`
			<div id="${this.#id}" class="call-quality__rating__container">
				${content}
			</div>
		`;
	}

	// @todo
	#getSkeleton(): HTMLElement
	{
		return Tag.render`<div></div>`;
	}

	#getContent(): HTMLElement
	{
		return Tag.render`
			<div class="call-quality__rating__text-container">
				${Loc.getMessage('CRM_COPILOT_CALL_QUALITY_RATING')}
				<div 
					class="call-quality__rating_article ui-icon-set --help"
					onclick="${this.#showArticle.bind(this)}"
				></div>
			</div>
			<div class="call-quality__rating__value-container">
				${this.#getAvatar()}
				<div class="call-quality__rating__value">
					${this.#rating}
					<span class="call-quality__rating__measure">%</span>
				</div>
				<div class="call-quality__rating__trend ${this.#getTrendClass()}"></div>
			</div>
		`;
	}

	#getAvatar(): HTMLElement
	{
		if (Type.isStringFilled(this.#userPhotoUrl))
		{
			return Tag.render`
				<div
					class="call-quality__rating__avatar"
					style="background-image: url(${encodeURI(this.#userPhotoUrl)})"
				></div>
			`;
		}

		return Tag.render`
			<div class="call-quality__rating__avatar ui-icon ui-icon-common-user">
				<i style=""></i>
			</div>
		`;
	}

	#getTrendClass(): string
	{
		const trend = this.#getTrend();

		if (trend === Trend.up)
		{
			return '--up';
		}

		if (trend === Trend.down)
		{
			return '--down';
		}

		return '--no-changes';
	}

	#getTrend(): TrendValue
	{
		if (this.#rating > this.#prevRating)
		{
			return Trend.up;
		}

		if (this.#rating < this.#prevRating)
		{
			return Trend.down;
		}

		return Trend.noChanges;
	}

	#showArticle(): void
	{
		window.top.BX?.Helper?.show(`redirect=detail&code=${this.#articleCode}&anchor=${this.#articleAnchor}`);
	}

	setRating(rating: number): void
	{
		this.#rating = rating;
	}

	setPrevRating(rating: number): void
	{
		this.#prevRating = rating;
	}

	setUserPhotoUrl(userPhotoUrl: string): void
	{
		this.#userPhotoUrl = userPhotoUrl;
	}

	setSkeletonMode(useSkeletonMode: boolean = true): void
	{
		if (this.#useSkeletonMode !== useSkeletonMode)
		{
			this.#useSkeletonMode = useSkeletonMode;
			this.#layout();
		}
	}

	#layout(): void
	{
		const currentContent = document.getElementById(this.#id);
		if (currentContent === null)
		{
			return;
		}

		Dom.replace(currentContent, this.render());
	}
}
