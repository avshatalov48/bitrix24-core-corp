import {BaseEvent, EventEmitter} from 'main.core.events';

import {Filter} from '../service/filter';
import {SidePanel} from '../service/side.panel';

import {EntityStorage} from '../entity/entity.storage';

import {TagSearcher} from '../utility/tag.searcher';

import type {EpicType} from '../item/task/epic';

type Params = {
	groupId: number,
	entityStorage: EntityStorage,
	sidePanel: SidePanel,
	filter: Filter,
	tagSearcher: TagSearcher,
}

export class Epic extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Epic.Helper');

		this.groupId = parseInt(params.groupId, 10);

		this.entityStorage = params.entityStorage;
		this.sidePanel = params.sidePanel;
		this.filter = params.filter;
		this.tagSearcher = params.tagSearcher;

		this.subscribeToExtension();
	}

	subscribeToExtension()
	{
		EventEmitter.subscribe(
			'BX.Tasks.Scrum.Epic:afterAdd',
			(baseEvent: BaseEvent) => {
				this.onAfterAdd(baseEvent);
			})
		;

		EventEmitter.subscribe(
			'BX.Tasks.Scrum.Epic:afterEdit',
			(baseEvent: BaseEvent) => {
				this.onAfterEdit(baseEvent);
			})
		;

		EventEmitter.subscribe(
			'BX.Tasks.Scrum.Epic:filterByTag',
			(baseEvent: BaseEvent) => {
				this.emit('filterByTag', baseEvent.getData())
			})
		;

		EventEmitter.subscribe(
			'BX.Tasks.Scrum.Epic:afterRemove',
			(baseEvent: BaseEvent) => {
				this.onAfterRemove(baseEvent);
			})
		;
	}

	openAddForm(): Promise
	{
		return this.sidePanel.showByExtension(
			'Epic',
			{
				view: 'add',
				groupId: this.groupId
			}
		);
	}

	onAfterAdd(baseEvent: BaseEvent)
	{
		const epic: EpicType = baseEvent.getData();

		this.tagSearcher.addEpicToSearcher(epic);

		this.filter.addItemToListTypeField('EPIC', {
			NAME: epic.name.trim(),
			VALUE: String(epic.id)
		});
	}

	onAfterEdit(baseEvent: BaseEvent)
	{
		const epic: EpicType = baseEvent.getData();

		this.entityStorage.getAllItems().forEach((item) => {
			const itemEpic = item.getEpic().getValue();
			if (itemEpic && itemEpic.id === epic.id)
			{
				item.setEpic(epic);
			}
		});

		this.tagSearcher.removeEpicFromSearcher(epic);
		this.tagSearcher.addEpicToSearcher(epic);
	}

	onAfterRemove(baseEvent: BaseEvent)
	{
		const epic: EpicType = baseEvent.getData();

		this.entityStorage.getAllItems().forEach((item) => {
			const itemEpic = item.getEpic().getValue();
			if (itemEpic && itemEpic.id === epic.id)
			{
				item.setEpic();
			}
		});

		this.tagSearcher.removeEpicFromSearcher(epic);
	}
}