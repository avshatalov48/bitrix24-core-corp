import { Dom } from 'main.core';
import { Core } from 'booking.core';
import { Model } from 'booking.const';

const cellHeight = 50;
const cellHeightProperty = '--booking-off-hours-cell-height';
const classCollapse = '--booking-booking-collapse';
const classExpand = '--booking-booking-expand';

class ExpandOffHours
{
	#animation: BX.easing;

	get #container(): HTMLElement
	{
		return Core.getParams().container;
	}

	get #content(): HTMLElement
	{
		return BX('booking-content');
	}

	get #gridWrap(): HTMLElement
	{
		return BX('booking-booking-grid-wrap');
	}

	get #fromHour(): number
	{
		return Core.getStore().getters['interface/fromHour'];
	}

	get #toHour(): number
	{
		return Core.getStore().getters['interface/toHour'];
	}

	expand({ keepScroll }): void
	{
		Dom.removeClass(this.#container, classCollapse);
		Dom.addClass(this.#container, classExpand);
		this.animate(0, cellHeight, keepScroll);
	}

	collapse(): void
	{
		Dom.removeClass(this.#container, classExpand);
		Dom.addClass(this.#container, classCollapse);
		this.animate(cellHeight, 0, true);
	}

	animate(fromHeight: number, toHeight: number, keepScroll: boolean = false): void
	{
		const savedScrollTop = this.#gridWrap.scrollTop;
		const savedScrollHeight = this.#gridWrap.scrollHeight;
		const topCellsCoefficient = this.#fromHour / (24 - (this.#toHour - this.#fromHour));

		this.#animation?.stop();
		this.#animation = new BX.easing({
			duration: 200,
			start: {
				height: fromHeight,
			},
			finish: {
				height: toHeight,
			},
			step: ({ height }) => {
				Dom.style(this.#content, cellHeightProperty, `calc(var(--zoom) * ${height}px)`);

				if (keepScroll)
				{
					const heightChange = this.#gridWrap.scrollHeight - savedScrollHeight;

					this.#gridWrap.scrollTop = savedScrollTop + heightChange * topCellsCoefficient;
				}
			},
		});
		this.#animation.animate();
	}

	setExpanded(isExpanded: boolean): void
	{
		const savedScrollTop = this.#gridWrap.scrollTop;
		const savedScrollHeight = this.#gridWrap.scrollHeight;
		const topCellsCoefficient = this.#fromHour / (24 - (this.#toHour - this.#fromHour));

		const height = isExpanded ? cellHeight : 0;
		const className = isExpanded ? classExpand : classCollapse;

		Dom.removeClass(this.#container, [classCollapse, classExpand]);
		Dom.addClass(this.#container, className);
		Dom.style(this.#content, cellHeightProperty, `calc(var(--zoom) * ${height}px)`);

		const heightChange = this.#gridWrap.scrollHeight - savedScrollHeight;
		this.#gridWrap.scrollTop = savedScrollTop + heightChange * topCellsCoefficient;

		void Core.getStore().dispatch(`${Model.Interface}/setOffHoursExpanded`, isExpanded);
	}
}

export const expandOffHours: ExpandOffHours = new ExpandOffHours();
