import {Dialog} from 'ui.entity-selector';
import {Loc, Tag, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Input} from './input';

import type {EpicType} from '../item/task/epic';

export class TagSearcher extends EventEmitter
{
	static tagRegExp = '#[^#@](?:[^#@]*[^\s#@])?';
	static epicRegExp = '@[^#@](?:[^#@]*[^\s#@])?';

	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.TagSearcher');

		this.allTags = new Map();
	}

	addTagToSearcher(tagName)
	{
		tagName = tagName.trim();

		tagName = tagName === '' ? Text.getRandom() : tagName;

		this.allTags.set('tag_' + tagName, {
			id: tagName,
			entityId: 'tag',
			tabs: 'recents',
			title: tagName,
			avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag.svg'
		});
	}

	addEpicToSearcher(epic: EpicType)
	{
		const epicName = epic.name.trim();

		this.allTags.set('epic_' + epicName, {
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

	getTagFromSearcher(name: String): Object
	{
		return this.allTags.get(name);
	}

	removeEpicFromSearcher(epic: EpicType)
	{
		this.allTags.delete('epic_' + epic.name);
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
		const currentTags = item.getTags().getValue();

		const selectedItems = [];
		currentTags.forEach((tag) => {
			const currentTag = this.allTags.get(('tag_' + tag).trim());
			if (currentTag)
			{
				selectedItems.push(currentTag);
			}
		});

		let choiceWasMade = false;

		this.tagDialog = new Dialog({
			id: item.getId(),
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: true,
			dropdownMode: true,
			enableSearch: true,
			searchOptions: {
				allowCreateItem: true,
				footerOptions: {
					label: Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')
				}
			},
			offsetTop: 12,
			selectedItems: selectedItems,
			items: this.getTagsList(),
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
						this.addTagToSearcher(tagName);
						const newTag = this.getTagFromSearcher('tag_' + tagName);
						const item = dialog.addItem(newTag);
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
		});

		this.tagDialog.show();
	}

	showEpicDialog(item: Item, targetNode: HTMLElement): Dialog
	{
		const currentEpic = item.getEpic().getValue();

		const selectedItems = [];
		if (currentEpic)
		{
			const currentEpicInfo = this.allTags.get(('epic_' + currentEpic.name).trim());
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
			events: {
				'Item:onSelect': (event) => {
					choiceWasMade = true;
					const selectedItem = event.getData().item;
					const epicId = selectedItem.getId();
					this.emit('updateItemEpic', epicId);
				},
				'Item:onDeselect': (event) => {
					setTimeout(() => {
						choiceWasMade = true;
						if (this.epicDialog.getSelectedItems().length === 0)
						{
							this.emit('updateItemEpic', 0);
							this.epicDialog.hide();
						}
					}, 50);
				},
			},
			tagSelectorOptions: {
				placeholder: Loc.getMessage('TASKS_SCRUM_ITEM_EPIC_SEARCHER_PLACEHOLDER')
			}
		});

		this.epicDialog.subscribe('onHide', () => {
			if (choiceWasMade)
			{
				this.emit('hideEpicDialog');
			}
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

	showTagsSearchDialog(inputObject: Input, enteredHashTagName: string): Dialog
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
				items: this.getTagsList(),
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
						const selectedItem = event.getData().item;
						const selectedHashTag = '#' + selectedItem.getTitle();
						const hashTags = TagSearcher.getHashTagNamesFromText(input.value);
						const enteredHashTag = (hashTags.length > 0 ? hashTags.pop() : '');
						input.value = input.value.replace(
							new RegExp('#' + enteredHashTag, 'g'),
							selectedHashTag
						);
						input.focus();
						selectedItem.deselect();
					}
				}
			});

			this.tagSearchDialog.subscribe('onHide', () => {
				inputObject.setTagsSearchMode(false);
			});

			inputObject.subscribeOnce('onMetaEnter', () => {
				this.tagSearchDialog.hide();
				input.focus();
			});
		}

		inputObject.setTagsSearchMode(true);

		this.tagSearchDialog.show();

		this.tagSearchDialog.search(enteredHashTagName);
	}

	closeTagsSearchDialog()
	{
		if (this.tagSearchDialog)
		{
			this.tagSearchDialog.hide();
		}
	}

	showEpicSearchDialog(inputObject: Input, enteredHashEpicName: string): Dialog
	{
		const input = inputObject.getInputNode();

		if (this.epicSearchDialog && this.epicSearchDialog.getId() !== inputObject.getNodeId())
		{
			this.epicSearchDialog = null;
		}

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
							const dialog = event.getTarget();
							const { searchQuery } = event.getData();
							this.emit('createEpic', searchQuery.getQuery());
							dialog.hide();
							input.focus();
							this.epicSearchDialog = null;
							resolve();
						});
					},
					'Item:onSelect': (event: BaseEvent) => {
						const selectedItem = event.getData().item;
						const selectedHashEpic = '@' + selectedItem.getTitle();
						input.value = input.value.replace(
							selectedHashEpic === '' ? '@' : new RegExp(TagSearcher.epicRegExp, 'g'),
							''
						);
						input.value = input.value + selectedHashEpic
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
				this.emit(
					'createEpic',
					this.epicSearchDialog.getSearchTab().getLastSearchQuery().getQuery()
				);
				this.epicSearchDialog.hide();
				this.epicSearchDialog = null;
				input.focus();
			});
		}

		inputObject.setEpicSearchMode(true);

		this.epicSearchDialog.show();
		this.epicSearchDialog.search(enteredHashEpicName);
	}

	closeEpicSearchDialog()
	{
		if (this.epicSearchDialog)
		{
			this.epicSearchDialog.hide();
		}
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
}
