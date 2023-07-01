import {ajax as Ajax, Dom, Runtime, Type, Uri, Event} from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

import 'main.polyfill.intersectionobserver';

class TaskResult
{
	taskId = null;
	itemsContentNode = null;
	targetBtnDown = null;
	targetBtnUp = null;
	itemsNodes = null;
	itemsWrapperNode = null;

	constructor(taskId)
	{
		this.init(taskId);
		this.setHeightAutoFunction = this.setHeightAuto.bind(this);
	}

	init(taskId)
	{
		this.taskId = taskId;

		this.initExpand();

		EventEmitter.subscribe('onPullEvent-tasks', this.onPushResult.bind(this));
		EventEmitter.subscribe('BX.Livefeed:recalculateComments', this.onRecalculateLivefeedComments.bind(this));
		EventEmitter.subscribe('SidePanel.Slider:onOpenComplete', this.blockResize.bind(this));
	}

	scrollToResult()
	{
		const resultId = this.getResultIdFromRequest();
		if (resultId)
		{
			const resultItem = this.contentNode.querySelector('[data-id="' + resultId + '"]');
			if (resultItem)
			{
				const scrollTo = () => {
					this.activateBlinking(resultItem);

					const itemTopPosition = Dom.getPosition(resultItem).top;

					window.scrollTo({ top: itemTopPosition, behavior: 'smooth' });
				};

				if (Dom.hasClass(resultItem.parentElement, 'tasks-widget-result__item-more'))
				{
					Event.bindOnce(this.itemsWrapperNode, 'transitionend', scrollTo);

					this.showResults();
				}
				else
				{
					scrollTo();
				}
			}
		}
	}

	getResultIdFromRequest(): ?number
	{
		const uri = new Uri(window.location.href);

		const resultId = uri.getQueryParam('RID');

		return resultId ? parseInt(resultId, 10) : null;
	}

	blockResize()
	{
		this.contentNode.style.height = `${this.containerNode.scrollHeight}px`;

		this.scrollToResult();
	}

	initExpand()
	{
		this.initExpandButton();

		if (this.contentNode)
		{
			this.blockResize();
		}

		this.targetBtnDown && this.targetBtnDown.addEventListener('click', this.showResults.bind(this));

		this.targetBtnUp && this.targetBtnUp.addEventListener('click', () => {
			this.targetBtnUp.classList.remove('--visible');
			this.targetBtnDown.classList.add('--visible');

			this.itemsContentNode.classList.remove('--open');
			this.itemsWrapperNode.style.height = `${this.itemsWrapperNode.scrollHeight}px`;
			this.itemsWrapperNode.clientHeight; // it's needed, P.Rafeev magic
			this.itemsWrapperNode.style.height = 0;

			if (this.contentNode)
			{
				this.contentNode.style.height = `${this.itemsNodes[0].offsetHeight + 35}px`;
			}
		});


	}

	setHeightAuto()
	{
		this.itemsWrapperNode.style.height = 'auto';
		this.itemsWrapperNode.removeEventListener('transitionend', this.setHeightAutoFunction);
	}

	initExpandButton()
	{
		this.contentNode = document.getElementById(`tasks-result-list-container-${this.taskId}`);
		this.containerNode = document.getElementById(`tasks-result-list-wrapper-${this.taskId}`);

		if (!this.containerNode)
		{
			return;
		}

		this.itemsContentNode = this.containerNode.querySelector('[data-role="tasks-widget--content"]');
		this.targetBtnDown = this.containerNode.querySelector('[data-role="tasks-widget--btn-down"]');
		this.targetBtnUp = this.containerNode.querySelector('[data-role="tasks-widget--btn-up"]');
		this.itemsNodes = this.containerNode.querySelectorAll('[data-role="tasks-widget--result-item"]');
		this.itemsWrapperNode = this.containerNode.querySelector('[data-role="tasks-widget--wrapper"]');

		if (
			!this.itemsWrapperNode
			|| this.itemsNodes.length <= 1
		)
		{
			return;
		}

		this.targetBtnDown.classList.add('--visible');

		this.itemsNodes.length === 2
			? this.itemsContentNode.classList.add('--two-results')
			: this.itemsContentNode.classList.add('--many-results');

		EventEmitter.subscribe('BX.Forum.Spoiler:toggle', this.onSpoilerToggle.bind(this));
	}

	onSpoilerToggle(event)
	{
		const [ eventData ] = event.getCompatData();

		if (!eventData.node)
		{
			return;
		}

		const targetContentNode = eventData.node.closest('.tasks-result-list-container');
		if (
			!targetContentNode
			|| !this.contentNode
			|| targetContentNode.id !== this.contentNode.id
		)
		{
			return;
		}

		this.blockResize();
	}

	onPushResult(event: BaseEvent)
	{
		const [ command, params ] = event.getData();

		if (
			command !== 'task_result_create'
			&& command !== 'task_result_update'
			&& command !== 'task_result_delete'
		)
		{
			return;
		}

		if (
			!params.result
			|| !params.result.taskId
			|| params.result.taskId != this.taskId
		)
		{
			return;
		}

		this.reloadResults();
	}

	onRecalculateLivefeedComments(event: BaseEvent)
	{
		const [ data ] = event.getCompatData();
		if (!Type.isDomNode(data.rootNode))
		{
			return;
		}

		const taskResultContainer = data.rootNode.querySelector('.tasks-result-list-container');
		if (
			!taskResultContainer
			|| taskResultContainer.id !== this.contentNode.id
		)
		{
			return;
		}

		this.blockResize();
	}

	showResults()
	{
		this.targetBtnDown.classList.remove('--visible');
		this.targetBtnUp.classList.add('--visible');

		this.itemsContentNode.classList.add('--open');
		this.itemsWrapperNode.style.height = `${this.itemsWrapperNode.scrollHeight}px`;
		this.itemsWrapperNode.addEventListener('transitionend', this.setHeightAutoFunction);

		if (this.contentNode)
		{
			this.contentNode.style.height = `${this.itemsWrapperNode.scrollHeight + this.containerNode.scrollHeight}px`;
		}
	}

	activateBlinking(resultNode: HTMLElement)
	{
		if (Type.isUndefined(IntersectionObserver))
		{
			return;
		}

		const observer = new IntersectionObserver((entries) =>
			{
				if (entries[0].isIntersecting === true)
				{
					Dom.addClass(resultNode, '--blink');

					setTimeout(() => {
						Dom.removeClass(resultNode, '--blink');
					}, 300);

					observer.disconnect();
				}
			},
			{
				threshold: [0]
			}
		);

		observer.observe(resultNode);
	}

	reloadResults()
	{
		Ajax.runComponentAction('bitrix:tasks.widget.result', 'getResults', {
			mode: 'class',
			data: {
				taskId: this.taskId,
			}
		}).then(
		(response) => {
			if (!response.data)
			{
				return;
			}

			this.containerNode.innerHTML = response.data;
			Runtime.html(this.containerNode, response.data).then(() => {
				this.initExpand();
			});
		});
	}

}

export {
	TaskResult,
}