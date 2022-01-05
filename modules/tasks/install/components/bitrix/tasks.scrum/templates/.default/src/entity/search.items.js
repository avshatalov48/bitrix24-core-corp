import {Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {PlanBuilder} from '../view/plan/plan.builder';

import {Sprint} from './sprint/sprint';
import {EntityStorage} from './entity.storage';
import {SearchArrows} from './search.arrows';

import {Item} from '../item/item';

import {Scroller} from '../utility/scroller';

type Params = {
	planBuilder: PlanBuilder,
	entityStorage: EntityStorage
}

export class SearchItems extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.SearchItems');

		this.planBuilder = params.planBuilder;
		this.entityStorage = params.entityStorage;

		this.scroller = new Scroller({
			planBuilder: this.planBuilder,
			entityStorage: this.entityStorage
		});

		this.active = false;

		this.list = new Set();

		this.currentIndex = 0;

		this.arrows = null;
	}

	start(startItem: Item, linkedItemIds: Array<number>)
	{
		this.active = true;

		this.setList(linkedItemIds);

		this.fadeOutAll();

		if (!this.isBacklogItem(startItem))
		{
			this.scroller.scrollToItem(this.getCurrent());
		}

		if (!startItem.isDisabled())
		{
			this.updateCurrentIndexByItem(startItem);

			this.activateCurrent(startItem);
		}

		this.list.forEach((item: Item) => {
			item.activateLinkedMode();
		});

		this.showArrows();
	}

	stop()
	{
		this.active = false;

		this.currentIndex = 0;

		this.fadeInAll();

		this.cleanList();
		this.removeArrows();
	}

	setList(linkedItemIds: Array<number>)
	{
		this.list = new Set();

		const items = this.entityStorage.getAllItems();

		linkedItemIds.forEach((itemId: number) => {
			if (items.has(itemId))
			{
				this.list.add(items.get(itemId));
			}
		});
	}

	isActive(): boolean
	{
		return this.active;
	}

	isBacklogItem(item: Item): boolean
	{
		return this.entityStorage.getBacklog().hasItem(item);
	}

	getCurrent(): Item
	{
		return [...this.list.values()][this.currentIndex];
	}

	updateCurrentIndexByItem(inputItem: Item)
	{
		this.deactivateCurrent(this.getCurrent());

		[...this.list.values()].forEach((item: Item, index: number) => {
			if (inputItem.getId() === item.getId())
			{
				this.currentIndex = index;
			}
		});

		this.updateArrows();
	}

	moveToPrev()
	{
		this.deactivateCurrent(this.getCurrent());

		this.currentIndex--;

		if (this.currentIndex < 0)
		{
			this.currentIndex = (this.list.size - 1);
		}

		this.updateArrows();

		const currentItem = this.getCurrent();

		this.scroller.scrollToItem(currentItem);

		this.activateCurrent(currentItem);
	}

	moveToNext()
	{
		this.deactivateCurrent(this.getCurrent());

		this.currentIndex++;

		if (this.currentIndex === this.list.size)
		{
			this.currentIndex = 0;
		}

		this.updateArrows();

		const currentItem = this.getCurrent();

		this.scroller.scrollToItem(currentItem);

		this.activateCurrent(currentItem);
	}

	activateCurrent(item: Item)
	{
		item.activateCurrentLinkedMode(item);
	}

	deactivateCurrent()
	{
		this.list.forEach((item: Item) => {
			item.deactivateCurrentLinkedMode();
		});
	}

	fadeOutAll()
	{
		this.entityStorage.getBacklog().fadeOut();
		this.entityStorage.getBacklog().setActiveLoadItems(true);

		const activeSprint = this.entityStorage.getActiveSprint();
		if (activeSprint)
		{
			activeSprint.fadeOut();
			activeSprint.setActiveLoadItems(true);

			if (activeSprint.isHideContent())
			{
				activeSprint.toggleVisibilityContent(activeSprint.getContentContainer());
			}
		}

		this.entityStorage.getPlannedSprints()
			.forEach((sprint: Sprint) => {
				sprint.fadeOut();
				sprint.setActiveLoadItems(true);
				if (sprint.isHideContent())
				{
					sprint.toggleVisibilityContent(sprint.getContentContainer());
				}
			})
		;
	}

	fadeInAll()
	{
		this.entityStorage.getBacklog().fadeIn();

		const activeSprint = this.entityStorage.getActiveSprint();
		if (activeSprint)
		{
			activeSprint.fadeIn();
		}

		this.entityStorage.getPlannedSprints()
			.forEach((sprint: Sprint) => {
				sprint.fadeIn();
			})
		;
	}

	deactivateGroupMode()
	{
		this.entityStorage.getBacklog().deactivateGroupMode();

		const activeSprint = this.entityStorage.getActiveSprint();
		if (activeSprint)
		{
			activeSprint.deactivateGroupMode();
		}

		this.entityStorage.getPlannedSprints()
			.forEach((sprint: Sprint) => {
				sprint.deactivateGroupMode();
			})
		;
	}

	showArrows()
	{
		this.arrows = new SearchArrows({
			currentPosition: this.currentIndex + 1,
			list: this.list
		});

		this.arrows.subscribe('close', this.stop.bind(this));
		this.arrows.subscribe('prev', this.moveToPrev.bind(this));
		this.arrows.subscribe('next', this.moveToNext.bind(this));

		Dom.append(this.arrows.render(), document.body);

		this.adjustArrowsPosition();
	}

	updateArrows()
	{
		if (this.arrows)
		{
			this.arrows.updateCurrentPosition(this.currentIndex + 1);
		}
	}

	adjustArrowsPosition()
	{
		const arrowsRect = Dom.getPosition(this.arrows.getNode());
		const backlogRect = Dom.getPosition(this.entityStorage.getBacklog().getNode());

		this.arrows.getNode().style.top = `${(backlogRect.height / 2) + (backlogRect.top - arrowsRect.height)}px`;
		this.arrows.getNode().style.left = `${backlogRect.left - ((arrowsRect.width / 2) + 16)}px`;
	}

	cleanList()
	{
		this.list.forEach((item: Item) => item.deactivateLinkedMode());

		this.list = new Set();
	}

	removeArrows()
	{
		this.arrows.remove();
	}

	isClickInside(node: HTMLElement): boolean
	{
		let isClickInside = false;

		const backlog = this.entityStorage.getBacklog();

		if (backlog.getNode().contains(node))
		{
			isClickInside = true;
		}

		const activeSprint = this.entityStorage.getActiveSprint();
		if (activeSprint && activeSprint.getNode().contains(node))
		{
			isClickInside = true;
		}

		if (this.arrows && this.arrows.getNode().contains(node))
		{
			isClickInside = true;
		}

		this.entityStorage.getPlannedSprints()
			.forEach((sprint: Sprint) => {
				if (sprint.getNode().contains(node))
				{
					isClickInside = true;
				}
			})
		;

		return isClickInside;
	}
}