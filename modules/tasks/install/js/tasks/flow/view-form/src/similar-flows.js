import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { Loader } from 'main.loader';
import { Button } from 'ui.buttons';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { ViewAjax } from './view-ajax';
import type { SimilarFlow } from './view-ajax';

type Params = {
	flowId: number,
	createTaskButtonClickHandler: ?Function,
	isFeatureEnabled: boolean,
};

export class SimilarFlows
{
	#flowId: number;
	#viewAjax: ViewAjax;
	#createTaskButtonClickHandler: Function;
	#similarFlows: SimilarFlow[] = [];
	#layout: {
		wrap: HTMLElement,
		emptyState: HTMLElement,
	};

	constructor(params: Params)
	{
		this.#flowId = params.flowId;
		this.#layout = {};

		this.isFeatureEnabled = params.isFeatureEnabled;

		this.#viewAjax = new ViewAjax(params.flowId);
		this.#createTaskButtonClickHandler = params.createTaskButtonClickHandler ?? null;
	}

	async #load(): Promise
	{
		this.#layout.emptyState?.remove();

		const loader = new Loader({
			target: this.#layout.wrap,
			size: 60,
		});

		void loader.show();

		const { page, similarFlows } = await this.#viewAjax.getSimilarFlows();

		const isFirstPageLoaded = !Type.isArrayFilled(this.#similarFlows) && Type.isArrayFilled(similarFlows);
		if (isFirstPageLoaded)
		{
			Dom.append(this.#renderSimilarFlowsListTitle(), this.#layout.wrap);
		}

		this.#similarFlows = similarFlows;

		page.forEach((data: SimilarFlow) => Dom.append(this.#renderSimilarFlow(data), this.#layout.wrap));

		if (!Type.isArrayFilled(this.#similarFlows))
		{
			Dom.append(this.#renderEmptyState(), this.#layout.wrap);
		}

		loader.destroy();
	}

	show()
	{
		Dom.style(this.#layout.wrap, 'display', '');

		if (!Type.isArrayFilled(this.#similarFlows))
		{
			Dom.style(this.#layout.wrap, 'overflow', 'hidden');
			this.#load().then(() => Dom.style(this.#layout.wrap, 'overflow', ''));
		}
	}

	hide()
	{
		Dom.style(this.#layout.wrap, 'display', 'none');
	}

	render(): HTMLElement
	{
		this.#layout.wrap = Tag.render`
			<div class="tasks-flow__view-form_similar-flows">
				${this.#similarFlows.map((flow: SimilarFlow) => this.#renderSimilarFlow(flow))}
			</div>
		`;

		Event.bind(this.#layout.wrap, 'scroll', () => {
			const scrollTop = this.#layout.wrap.scrollTop;
			const maxScroll = this.#layout.wrap.scrollHeight - this.#layout.wrap.offsetHeight;

			if (Math.abs(scrollTop - maxScroll) < 1)
			{
				void this.#load();
			}
		});

		return this.#layout.wrap;
	}

	#renderSimilarFlowsListTitle(): ?HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__view-form_similar-flows-title">
				${Loc.getMessage('TASKS_FLOW_VIEW_FORM_SIMILAR_FLOWS_TITLE')}
			</div>
		`;
	}

	#renderSimilarFlow(flow: SimilarFlow): HTMLElement
	{
		const button = new Button({
			color: Button.Color.SECONDARY_LIGHT,
			size: Button.Size.EXTRA_SMALL,
			round: true,
			text: Loc.getMessage('TASKS_FLOW_VIEW_FORM_CREATE_TASK'),
			noCaps: true,
			onclick: () => {
				this.#createTaskButtonClickHandler?.();
				if (this.isFeatureEnabled)
				{
					BX.SidePanel.Instance.open(flow.createTaskUri);
				}
				else
				{
					FeaturePromotersRegistry.getPromoter({ code: 'limit_tasks_flows' }).show();
				}
			},
		});

		return Tag.render`
			<div class="tasks-flow__view-form_similar-flow">
				<div class="tasks-flow__view-form_similar-flow-name" title="${Text.encode(flow.name)}">
					${Text.encode(flow.name)}
				</div>
				${button.render()}
			</div>
		`;
	}

	#renderEmptyState()
	{
		this.#layout.emptyState = Tag.render`
			<div class="tasks-flow__view-form_similar-flows-empty-state">
				<div class="tasks-flow__view-form_similar-flows-empty-state-icon"></div>
				<div class="tasks-flow__view-form_similar-flows-empty-state-text">
					${Loc.getMessage('TASKS_FLOW_VIEW_FORM_NO_SIMILAR_FLOWS')}
				</div>
			</div>
		`;

		return this.#layout.emptyState;
	}
}
