import { bindOnce, Dom, Tag, Type } from 'main.core';

type ExpandOptions = {
	startHeight: number;
}

/** @memberof BX.Crm.Timeline.Animation */
export default class Expand
{
	#overlay: HTMLElement | null;
	#startHeight: number;
	#node: HTMLElement | null;
	#callback: Function | null;

	constructor()
	{
		this.#node = null;
		this.#callback = null;
		this.#overlay = null;
		this.#startHeight = 0;
	}

	initialize(node, callback, options: ExpandOptions)
	{
		this.#node = node;
		this.#callback = BX.type.isFunction(callback) ? callback : null;
		this.#startHeight = options?.startHeight || 0;
	}

	run()
	{
		if (this.#isNodeVisible(this.#node) === false)
		{
			if (this.#callback)
			{
				this.#callback();
			}

			return;
		}

		requestAnimationFrame(() => {
			const position = Dom.getPosition(this.#node);

			const elemStyle = getComputedStyle(this.#node);
			const paddingTop = parseInt(elemStyle.getPropertyValue('padding-top'), 10);
			const paddingBottom = parseInt(elemStyle.getPropertyValue('padding-bottom'), 10);
			const marginBottom = parseInt(elemStyle.getPropertyValue('margin-bottom'), 10);

			const startHeight = this.#startHeight;
			Dom.style(this.#node, {
				height: `${startHeight}px`,
				overflowY: 'clip',
				position: 'relative',
				padding: 0,
				marginBottom: 0,
			});

			requestAnimationFrame(() => {
				Dom.style(this.#node, 'transition', 'transition: height 220ms ease, opacity 220ms ease, background-color 220ms ease');

				this.#overlay = Tag.render`<div class="crm-timeline__card_overlay crm-timeline__card-scope"></div>`;

				Dom.append(this.#overlay, this.#node);
				setTimeout(() => {
					// eslint-disable-next-line new-cap
					(new BX.easing(
						{
							duration: 400,
							start: {
								height: startHeight,
								overlayOpacity: 0,
								paddingTop: 0,
								paddingBottom: 0,
								marginBottom: 0,
							},
							finish: {
								height: position.height,
								overlayOpacity: 50,
								paddingTop,
								paddingBottom,
								marginBottom,
							},
							transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
							step: this.onNodeHeightStep.bind(this),
							complete: this.onNodeHeightComplete.bind(this),
						},
					)
					).animate();
				}, 200);
			});
		});
	}

	onNodeHeightStep(state)
	{
		Dom.style(this.#overlay, 'opacity', 1 - (state.overlayOpacity / 100));
		Dom.style(this.#node, {
			height: `${state.height}px`,
			paddingTop: `${state.paddingTop}px`,
			paddingBottom: `${state.paddingBottom}px`,
			marginBottom: `${state.marginBottom}px`,
		});
	}

	onNodeHeightComplete()
	{
		setTimeout(() => {
			bindOnce(this.#overlay, 'transitionend', () => {
				Dom.remove(this.#overlay);
				this.#overlay = null;

				const color = Dom.style(this.#node, '--crm-timeline__card-color-background');
				Dom.style(this.#node, null);
				if (Type.isStringFilled(color))
				{
					Dom.style(this.#node, '--crm-timeline__card-color-background', color);
				}
			});
			Dom.style(this.#overlay, 'opacity', 0);
			if (this.#callback)
			{
				this.#callback();
			}
		}, 400);
	}

	#isNodeVisible(node: HTMLElement): boolean
	{
		const position = Dom.getPosition(this.#node);

		return position.width !== 0 && position.height !== 0;
	}

	static create(node, callback, options: ExpandOptions): Expand
	{
		const self = new Expand();
		self.initialize(node, callback, options);

		return self;
	}
}
