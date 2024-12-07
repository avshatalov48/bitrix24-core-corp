import { Dom, Event, Tag } from 'main.core';

type Segment = {
	id: string,
	title: string,
	isActive: boolean,
	node: HTMLElement,
};

type Params = {
	segments: Segment[],
	onSegmentSelected: function,
};

export class SegmentButton
{
	#params: Params;
	#segments: Segment[];

	constructor(params: Params)
	{
		this.#params = params;

		this.#segments = params.segments;
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__segment-button">
				${this.#segments.map((segment) => this.#renderSegment(segment))}
			</div>
		`;
	}

	#renderSegment(segment: Segment): HTMLElement
	{
		segment.node = Tag.render`
			<div class="tasks-flow__segment-button-segment ${segment.isActive ? '--active' : ''}">
				${segment.title}
			</div>
		`;

		Event.bind(segment.node, 'click', () => this.#selectSegment(segment));

		return segment.node;
	}

	#selectSegment(selectedSegment: Segment)
	{
		this.#segments.forEach((segment) => {
			Dom.removeClass(segment.node, '--active');

			if (segment.id === selectedSegment.id)
			{
				Dom.addClass(segment.node, '--active');
			}
		});

		this.#params.onSegmentSelected(selectedSegment);
	}
}