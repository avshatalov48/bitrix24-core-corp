import {Loc, Type, Text, Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Dialog} from 'ui.entity-selector';

import {Filter} from '../service/filter';

import {RequestSender} from './request.sender';
import {Input} from './input';

import type {EpicType} from '../item/task/epic';

type Params = {
	requestSender: RequestSender,
	filter: Filter,
	groupId: number
}

export class TagSearcher extends EventEmitter
{
	static tagRegExp = '#([^\\s,\\[\\]<>]+)';
	static epicRegExp = '@[^#@](?:[^#@]*[^\s#@])?';

	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.TagSearcher');

		this.requestSender = params.requestSender;
		this.filterService = params.filter;
		this.groupId = parseInt(params.groupId, 10);

		this.allTags = new Map();
	}

	addEpicToSearcher(epic: EpicType)
	{
		const epicName = epic.name.trim();

		this.allTags.set('epic_' + epic.id, {
			id: epic.id,
			entityId: 'epic',
			tabs: 'recents',
			title: epicName,
			avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag-green.svg',
			name: epicName,
			description: epic.description,
			color: epic.color,
			groupId: epic.groupId,
			createdBy: epic.createdBy,
			modifiedBy: epic.modifiedBy
		});
	}

	removeEpicFromSearcher(epic: EpicType)
	{
		this.allTags.delete('epic_' + epic.id);
	}

	getAllList(): Array
	{
		return [...this.allTags.values()];
	}

	getTagsList(): Array
	{
		const tagsList = [];
		[...this.allTags.values()].forEach((tag) => {
			if (tag.entityId === 'tag')
			{
				tagsList.push(tag);
			}
		});
		return tagsList;
	}

	getEpicList(): Array
	{
		const epicList = [];

		[...this.allTags.values()].forEach((epic) => {
			if (epic.entityId === 'epic')
			{
				epicList.push(epic);
			}
		});

		return epicList;
	}

	getEpicByName(epicName: string): ?EpicType
	{
		let epic = null;

		[...this.allTags.values()].forEach((tag) => {
			if (tag.entityId === 'epic' && tag.name === epicName)
			{
				epic = tag;
			}
		});

		return epic;
	}

	getEpicById(epicId: number): ?EpicType
	{
		return [...this.allTags.values()]
			.find((epic) => (epic.entityId === 'epic' && epic.id === epicId))
		;
	}

	showTagsDialog(item: Item, targetNode: HTMLElement): Dialog
	{
		let choiceWasMade = false;

		this.tagDialog = new Dialog({
			id: item.getId(),
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: true,
			dropdownMode: true,
			enableSearch: true,
			compactView: true,
			searchOptions: {
				allowCreateItem: true,
				footerOptions: {
					label: Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')
				}
			},
			offsetTop: 12,
			context: 'TASKS_SCRUM_TAG_' + this.groupId,
			entities: [
				{
					id: 'task-tag',
					options: {
						groupId: this.groupId,
						taskId: item.getSourceId()
					}
				}
			],
			events: {
				'Search:onItemCreateAsync': (event) => {
					return new Promise((resolve) => {

						const { searchQuery } = event.getData();
						const dialog = event.getTarget();

						const tagName = searchQuery.getQuery();
						if (!tagName)
						{
							dialog.focusSearch();

							return;
						}

						const item = dialog.addItem({
							id: tagName,
							entityId: 'task-tag',
							title: tagName,
							tabs: 'recents'
						});
						item.select();

						dialog.getTagSelector().clearTextBox();
						dialog.focusSearch();
						dialog.selectFirstTab();

						const label = dialog.getContainer().querySelector('.ui-selector-footer-conjunction');
						label.textContent = '';

						resolve();
					});
				},
				'Item:onSelect': (event) => {
					choiceWasMade = true;
					const selectedItem = event.getData().item;
					const tag = selectedItem.getTitle();
					this.emit('attachTagToTask', tag);
				},
				'Item:onDeselect': (event) => {
					choiceWasMade = true;
					const deselectedItem = event.getData().item;
					const tag = deselectedItem.getTitle();
					this.emit('deAttachTagToTask', tag);
				},
				'onLoad': (baseEvent: BaseEvent) => {
					this.hideDialogLabel(baseEvent.getTarget());
				}
			},
			tagSelectorOptions: {
				events: {
					onInput: (event) => {
						const selector = event.getData().selector;
						if (selector)
						{
							const dialog = selector.getDialog();
							const label = dialog.getContainer().querySelector('.ui-selector-footer-conjunction');
							label.textContent = Text.encode(selector.getTextBoxValue());
						}
					},
				}
			}
		});

		this.tagDialog.subscribe('onHide', () => {
			if (choiceWasMade)
			{
				this.emit('hideTagDialog');
			}
			this.tagDialog = null;
		});

		this.tagDialog.show();
	}

	showEpicDialog(item: Item, targetNode: HTMLElement): Dialog
	{
		const currentEpic = item.getEpic().getValue();

		const selectedItems = [];
		if (currentEpic)
		{
			const currentEpicInfo = this.allTags.get('epic_' + currentEpic.id);
			if (currentEpicInfo)
			{
				selectedItems.push(currentEpicInfo);
			}
		}

		let choiceWasMade = false;

		this.epicDialog = new Dialog({
			id: item.getId(),
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			offsetTop: 12,
			selectedItems: selectedItems,
			items: this.getEpicList(),
			searchOptions: {
				allowCreateItem: true,
				footerOptions: {
					label: Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_EPIC_ADD')
				}
			},
			hideOnDeselect: true,
			events: {
				'Search:onItemCreateAsync': (event) => {
					return new Promise((resolve) => {
						const { searchQuery } = event.getData();
						const dialog = event.getTarget();
						const epicName = searchQuery.getQuery();
						this.createEpic(epicName)
							.then((epic: EpicType) => {
								this.addEpicToSearcher(epic);
								this.filterService.addItemToListTypeField('EPIC', {
									NAME: epic.name.trim(),
									VALUE: String(epic.id)
								});
								const epicDialogItem = this.getEpicById(epic.id);
								epicDialogItem.selected = true;
								epicDialogItem.sort = 1;
								dialog.addItem(epicDialogItem);
								this.emit('updateItemEpic', epic.id);
								choiceWasMade = true;
								this.epicDialog.hide();
								resolve();
							})
						;
					});
				},
				'Item:onSelect': (event) => {
					choiceWasMade = true;
					const selectedItem = event.getData().item;
					const epicId = selectedItem.getId();
					this.emit('updateItemEpic', epicId);
				},
				'Item:onDeselect': () => {
					setTimeout(() => {
						choiceWasMade = true;
						if (this.epicDialog.getSelectedItems().length === 0)
						{
							this.emit('updateItemEpic', 0);
						}
					}, 50);
				},
			},
			tagSelectorOptions: {
				textBoxWidth: 340,
				placeholder: Loc.getMessage('TASKS_SCRUM_ITEM_EPIC_SEARCHER_PLACEHOLDER')
			}
		});

		this.epicDialog.subscribe('onHide', () => {
			if (choiceWasMade)
			{
				this.emit('hideEpicDialog');
			}
			this.epicDialog = null;
		});

		this.epicDialog.show();
	}

	isEpicDialogShown(): boolean
	{
		return this.epicDialog && this.epicDialog.isOpen();
	}

	hasActionPanelDialog(): boolean
	{
		return (this.epicDialog || this.tagDialog)
	}

	closeActionPanelDialogs()
	{
		if (this.epicDialog)
		{
			this.epicDialog.hide();
		}

		if (this.tagDialog)
		{
			this.tagDialog.hide();
		}
	}

	showTagsSearchDialog(inputObject: Input, enteredQuery: string): Dialog
	{
		const input = inputObject.getInputNode();

		if (this.tagSearchDialog && this.tagSearchDialog.getId() !== inputObject.getNodeId())
		{
			this.tagSearchDialog = null;
		}

		if (!this.tagSearchDialog)
		{
			this.tagSearchDialog = new Dialog({
				id: inputObject.getNodeId(),
				targetNode: inputObject.getNode(),
				width: inputObject.getNode().offsetWidth,
				height: 210,
				multiple: false,
				dropdownMode: true,
				compactView: true,
				context: 'TASKS_SCRUM_TAG_' + this.groupId,
				entities: [
					{
						id: 'task-tag',
						options: {
							groupId: this.groupId
						}
					}
				],
				tabOptions: {
					visible: false
				},
				searchOptions: {
					allowCreateItem: true,
					footerOptions: {
						label: Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')
					}
				},
				events: {
					'Search:onItemCreateAsync': (event) => {
						return new Promise((resolve) => {
							const dialog = event.getTarget();
							dialog.hide();
							input.focus();
							resolve();
						});
					},
					'Item:onSelect': (event) => {
						let newValue = '';
						const regex = new RegExp('\\s|#$', 'm');
						const currentPiece = input.value.split(regex).pop();
						input.value.split(regex)
							.forEach((pieceOfValue: string) => {
								if (currentPiece !== pieceOfValue)
								{
									newValue = newValue + ' ' + pieceOfValue;
								}
							})
						;
						const selectedItem = event.getData().item;
						newValue = newValue + ' #' + selectedItem.getTitle();
						input.value = newValue.trim();
						input.focus();
						selectedItem.deselect();
					},
					'onLoad': (baseEvent: BaseEvent) => {
						this.hideDialogLabel(baseEvent.getTarget());
					}
				}
			});

			this.tagSearchDialog.subscribe('onHide', () => {
				inputObject.setTagsSearchMode(false);
			});

			inputObject.subscribe('onEnter', () => {
				if (Type.isNil(this.tagSearchDialog))
				{
					return;
				}
				const searchTab = this.tagSearchDialog.getSearchTab();
				if (Type.isNil(searchTab))
				{
					return;
				}
				if (searchTab.isEmptyResult())
				{
					this.tagSearchDialog.hide();
					this.tagSearchDialog = null;
					input.focus();
				}
			});
		}

		inputObject.setTagsSearchMode(true);

		this.tagSearchDialog.show();

		this.tagSearchDialog.search(enteredQuery);
	}

	closeTagsSearchDialog()
	{
		if (this.tagSearchDialog)
		{
			this.tagSearchDialog.hide();
		}
	}

	showEpicSearchDialog(inputObject: Input, enteredQuery: string): Dialog
	{
		const input = inputObject.getInputNode();

		if (this.epicSearchDialog && this.epicSearchDialog.getId() !== inputObject.getNodeId())
		{
			this.epicSearchDialog = null;
		}

		this.epicEnteredQuery = enteredQuery;

		if (!this.epicSearchDialog)
		{
			this.epicSearchDialog = new Dialog({
				id: inputObject.getNodeId(),
				targetNode: inputObject.getNode(),
				width: inputObject.getNode().offsetWidth,
				height: 210,
				multiple: false,
				dropdownMode: true,
				searchOptions: {
					allowCreateItem: true,
					footerOptions: {
						label: Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_EPIC_ADD')
					}
				},
				items: this.getEpicList(),
				events: {
					'Search:onItemCreateAsync': (event) => {
						return new Promise((resolve) => {
							const { searchQuery } = event.getData();
							const epicName = searchQuery.getQuery();

							inputObject.disable();

							inputObject.setSelectedEpicLength([...epicName].length);
							input.focus();

							this.createEpic(epicName)
								.then((epic: ?EpicType) => {

									this.addEpicToSearcher(epic);

									this.filterService.addItemToListTypeField('EPIC', {
										NAME: epic.name.trim(),
										VALUE: String(epic.id)
									});

									inputObject.unDisable();
									input.focus();
									inputObject.setEpic(epic);

									resolve();
								})
							;

							this.epicSearchDialog.hide();
							this.epicSearchDialog = null;
						});
					},
					'Item:onSelect': (event: BaseEvent) => {
						const selectedItem = event.getData().item;
						const epicName = selectedItem.getTitle();
						input.value = input.value.replace('@' + this.epicEnteredQuery, '').replace('@', '');
						input.value = input.value + '@' + epicName
						inputObject.setSelectedEpicLength([...epicName].length);
						input.focus();
						selectedItem.deselect();
						inputObject.setEpic(this.getEpicByName(selectedItem.getTitle()));
					}
				}
			});

			this.epicSearchDialog.subscribe('onHide', () => {
				inputObject.setEpicSearchMode(false);
			});

			inputObject.subscribe('onMetaEnter', () => {
				if (Type.isNil(this.epicSearchDialog))
				{
					return;
				}
				const searchTab = this.epicSearchDialog.getSearchTab();
				if (Type.isNil(searchTab))
				{
					return;
				}
				const lastSearchQuery = searchTab.getLastSearchQuery();
				if (Type.isNil(lastSearchQuery))
				{
					return;
				}
				const epicName = lastSearchQuery.getQuery();

				inputObject.disable();

				inputObject.setSelectedEpicLength([...epicName].length);
				input.focus();

				this.createEpic(epicName)
					.then((epic: ?EpicType) => {

						this.addEpicToSearcher(epic);

						this.filterService.addItemToListTypeField('EPIC', {
							NAME: epic.name.trim(),
							VALUE: String(epic.id)
						});

						inputObject.unDisable();
						input.focus();
						inputObject.setEpic(epic);
					})
				;

				this.epicSearchDialog.hide();
				this.epicSearchDialog = null;
			});
		}

		inputObject.setEpicSearchMode(true);

		this.epicSearchDialog.show();
		this.epicSearchDialog.search(this.epicEnteredQuery);
	}

	closeEpicSearchDialog()
	{
		if (this.epicSearchDialog)
		{
			this.epicSearchDialog.hide();
		}
	}

	createEpic(epicName: string): Promise
	{
		return this.requestSender.createEpic(
			{
				groupId: this.groupId,
				name: epicName
			}
		)
			.then((response) => {
				return response.data;
			})
		;
	}

	static getHashTagNamesFromText(inputText: string): Array
	{
		const regex = new RegExp(TagSearcher.tagRegExp, 'g');

		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[0].substring(1));
		}

		return matches;
	}

	static getHashEpicNamesFromText(inputText: string): Array
	{
		const regex = new RegExp(TagSearcher.epicRegExp, 'g');

		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[0].substring(1));
		}

		return matches;
	}

	hideDialogLabel(dialog: Dialog)
	{
		//todo tmp, remove after update selector
		dialog
			.getContainer()
			.querySelectorAll('.ui-selector-tab-label')
			.forEach((label: HTMLElement) => {
				Dom.addClass(label, 'ui-selector-tab-label-hidden');
			})
		;
	}
}
