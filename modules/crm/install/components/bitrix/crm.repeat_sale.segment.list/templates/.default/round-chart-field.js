import { Dom, Reflection, Tag } from 'main.core';
import { ProgressRound } from 'ui.progressround';

const namespace = Reflection.namespace('BX.Crm.RepeatSale.SegmentList');

type Borders = Border[];

type Border = {
	value?: number;
	color: string;
	id: string;
}

const DEFAULT_BORDER = 'default';
const LOW_BORDER = 'lowBorder';
const HIGH_BORDER = 'highBorder';

export class RoundChartField
{
	#id: string;
	#targetNode: string;
	#borders: ?Borders;
	#value: number;
	#valueContainer: HTMLElement;

	constructor({ id, targetNodeId, borders, value })
	{
		this.#id = id;
		this.#targetNode = document.getElementById(targetNodeId);
		this.#borders = borders ?? null;
		this.#value = value;
	}

	init(): void
	{
		if (this.#value === null)
		{
			return;
		}

		this.#valueContainer = Tag.render`<div></div>`;

		const content = Tag.render`
			<div class="crm-repeat-sale-segment-list-client-percent">
				${this.#valueContainer}
				<div class="crm-repeat-sale-segment-list-client-percent-value">
					${this.#value}
					<span class="crm-repeat-sale-segment-list-client-percent-percent">%</span>
				</div>
			</div>
		`;

		Dom.append(content, this.#targetNode);

		const loader = new ProgressRound({
			width: 28,
			lineSize: 8,
			colorBar: this.#getTrackColor(),
			colorTrack: '#EBF1F6',
			rotation: false,
			value: this.#value,
			color: ProgressRound.Color.SUCCESS,
		});

		loader.renderTo(this.#valueContainer);
	}

	#getTrackColor(): string
	{
		const highBorder = this.#getBorderById(HIGH_BORDER);
		if (highBorder && this.#value >= highBorder?.value)
		{
			return highBorder.color;
		}

		const lowBorder = this.#getBorderById(LOW_BORDER);
		if (lowBorder && this.#value <= lowBorder?.value)
		{
			return lowBorder.color;
		}

		const defaultBorder = this.#getBorderById(DEFAULT_BORDER);
		if (defaultBorder)
		{
			return defaultBorder.color;
		}

		throw new RangeError('unknown track color');
	}

	#getBorderById(id: string): ?string
	{
		return this.#borders.find((border) => {
			return border.id === id;
		}) ?? null;
	}
}

namespace.RoundChartField = RoundChartField;
