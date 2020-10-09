import {Dialog} from 'ui.entity-selector';
import {Loc, Tag, Text} from 'main.core';
import {Input} from './input';

export class TagSearcher
{
	constructor(options)
	{
		this.requestSender = options.requestSender;

		this.allTags = new Map();
	}

	addTagToSearcher(tagName)
	{
		tagName = tagName.trim();
		this.allTags.set('tag_' + tagName, {
			id: tagName,
			entityId: 'tag',
			tabs: 'recents',
			title: tagName,
			avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag.svg'
		});
	}

	addEpicToSearcher(epic: Object)
	{
		const epicName = epic.name.trim();
		this.allTags.set('epic_' + epicName, {
			id: epic.id,
			entityId: 'epic',
			tabs: 'recents',
			title: epicName,
			avatar: '/bitrix/components/bitrix/tasks.scrum/templates/.default/images/search-hashtag-green.svg'
		});
	}

	getTagFromSearcher(name: String): Object
	{
		return this.allTags.get(name);
	}

	removeEpicFromSearcher(epic: Object)
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

	showTagsDialog(item: Item, targetNode: HTMLElement): Dialog
	{
		const actionsPanel = item.getCurrentActionsPanel();
		const currentTags = item.getTags();
		const requestData = {};

		const selectedItems = [];
		currentTags.forEach((tag) => {
			selectedItems.push(this.allTags.get('tag_' + tag));
		});

		const createTag = () => {
			const tagName = this.tagDialog.getTagSelector().getTextBoxValue();
			if (!tagName)
			{
				this.tagDialog.focusSearch();
				return;
			}
			this.addTagToSearcher(tagName);
			const newTag = this.getTagFromSearcher('tag_' + tagName);
			const item = this.tagDialog.addItem(newTag);
			item.select();
			this.tagDialog.getTagSelector().clearTextBox();
			this.tagDialog.focusSearch();
			this.tagDialog.selectFirstTab();
			const label = this.tagDialog.getContainer().querySelector('.ui-selector-footer-conjunction');
			label.textContent = '';
		};

		this.tagDialog = new Dialog({
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: true,
			dropdownMode: true,
			enableSearch: true,
			selectedItems: selectedItems,
			items: this.getTagsList(),
			events: {
				'Item:onSelect': (event) => {
					const selectedItem = event.getData().item;
					requestData.taskId = item.getSourceId();
					requestData.tag = selectedItem.title;
					this.requestSender.attachTagToTask(requestData).then((response) => {
						currentTags.push(requestData.tag);
						item.setEpicAndTags(item.getEpic(), currentTags);
					}).catch((response) => {});
				},
				'Item:onDeselect': (event) => {
					const deselectedItem = event.getData().item;
					requestData.taskId = item.getSourceId();
					requestData.tag = deselectedItem.title;
					this.requestSender.deAttachTagToTask(requestData).then((response) => {
						currentTags.splice(currentTags.indexOf(requestData.tag), 1);
						item.setEpicAndTags(item.getEpic(), currentTags);
					}).catch((response) => {});
				},
			},
			tagSelectorOptions: {
				events: {
					onInput: (event) => {
						const selector = event.getData().selector;
						const dialog = selector.getDialog();
						const label = dialog.getContainer().querySelector('.ui-selector-footer-conjunction');
						label.textContent = Text.encode(selector.getTextBoxValue());
					},
				}
			},
			footer: [
				Tag.render`
					<span onclick="${createTag}" class="ui-selector-footer-link ui-selector-footer-link-add">
						${Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_TAG_ADD')}
					</span>
				`,
				Tag.render`<span class="ui-selector-footer-conjunction"></span>`
			],
		});

		actionsPanel.setBlockBlurNode(this.tagDialog.getContainer());
		this.tagDialog.subscribe('onHide', () => {
			actionsPanel.destroy();
		});

		this.tagDialog.show();
	}

	showEpicDialog(item: Item, targetNode: HTMLElement): Dialog
	{
		const actionsPanel = item.getCurrentActionsPanel();
		const currentEpic = item.getEpic();
		const requestData = {};

		const selectedItems = [];
		if (currentEpic)
		{
			selectedItems.push(this.allTags.get('epic_' + currentEpic.name));
		}

		const dialog =new Dialog({
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			selectedItems: selectedItems,
			items: this.getEpicList(),
			events: {
				'Item:onSelect': (event) => {
					const selectedItem = event.getData().item;
					requestData.itemId = item.getItemId();
					requestData.epicId = selectedItem.id;
					this.requestSender.attachEpicToItem(requestData).then((response) => {
						item.setParentId(response.data.epic.id);
						item.setEpicAndTags(response.data.epic, item.getTags());
					}).catch((response) => {});
				},
				'Item:onDeselect': (event) => {
					requestData.itemId = item.getItemId();
					this.requestSender.deAttachEpicToItem(requestData).then((response) => {
						item.setParentId(0);
						item.setEpicAndTags(null, null);
					}).catch((response) => {});
				},
			},
			tagSelectorOptions: {
				placeholder: Loc.getMessage('TASKS_SCRUM_ITEM_EPIC_SEARCHER_PLACEHOLDER')
			}
		});

		actionsPanel.setBlockBlurNode(dialog.getContainer());
		dialog.subscribe('onHide', () => {
			actionsPanel.destroy();
		});
		dialog.show();
	}

	showTagsSearchDialog(inputObject: Input, enteredHashTagName: String): Dialog
	{
		const input = inputObject.getInputNode();

		if (this.tagsDialog && this.tagsDialog.getId() !== inputObject.getNodeId())
		{
			this.tagsDialog = null;
		}

		if (!this.tagsDialog)
		{
			this.tagsDialog = new Dialog({
				id: inputObject.getNodeId(),
				targetNode: inputObject.getNode(),
				width: inputObject.getNode().offsetWidth,
				height: 210,
				multiple: false,
				dropdownMode: true,
				items: this.getTagsList(),
				events: {
					'Item:onSelect': (event) => {
						const selectedItem = event.getData().item;
						const selectedHashTag = '#' + selectedItem.title;
						const hashTags = TagSearcher.getHashTagsFromText(input.value);
						const enteredHashTag = (hashTags.length > 0 ? hashTags.pop().trim() : '');
						input.value = input.value.replace(
							new RegExp('#(['+enteredHashTag+']+|)(?:$)', 'g'),
							selectedHashTag
						);
						input.focus();
						selectedItem.deselect();
					}
				}
			});

			this.tagsDialog.subscribe('onHide', () => {
				inputObject.setTagsSearchMode(false);
			});
		}

		inputObject.setTagsSearchMode(true);

		this.tagsDialog.show();
		this.tagsDialog.search(enteredHashTagName);
	}

	closeTagsSearchDialog()
	{
		if (this.tagsDialog)
		{
			this.tagsDialog.hide();
		}
	}

	showEpicSearchDialog(inputObject: Input, enteredHashEpicName: String): Dialog
	{
		const input = inputObject.getInputNode();

		if (this.epicDialog && this.epicDialog.getId() !== inputObject.getNodeId())
		{
			this.epicDialog = null;
		}

		if (!this.epicDialog)
		{
			this.epicDialog = new Dialog({
				id: inputObject.getNodeId(),
				targetNode: inputObject.getNode(),
				width: inputObject.getNode().offsetWidth,
				height: 210,
				multiple: false,
				dropdownMode: true,
				items: this.getEpicList(),
				events: {
					'Item:onSelect': (event) => {
						const selectedItem = event.getData().item;
						const selectedHashEpic = '@' + selectedItem.title;
						input.value = input.value.replace(new RegExp('(?:^|\\s)(?:@)([^\\s]*)','g'),'');
						input.value = input.value + ' ' + selectedHashEpic
						input.focus();
						selectedItem.deselect();
						inputObject.setEpicId(selectedItem.id);
					}
				}
			});

			this.epicDialog.subscribe('onHide', () => {
				inputObject.setEpicSearchMode(false);
			});
		}

		inputObject.setEpicSearchMode(true);

		this.epicDialog.show();
		this.epicDialog.search(enteredHashEpicName);
	}

	closeEpicSearchDialog()
	{
		if (this.epicDialog)
		{
			this.epicDialog.hide();
		}
	}

	cleanEpicTagsInText(inputText: String): String
	{
		const regex = new RegExp('(?:^|\\s)(?:@)(\\S+)', 'g');
		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[0]);
		}
		return matches;
	}

	static getHashTagsFromText(inputText: String): Array
	{
		const regex = new RegExp('(?:^|\\s)(?:#)(\\S+)', 'g');
		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[0]);
		}
		return matches;
	}

	static getHashEpicFromText(inputText: String): Array
	{
		const regex = new RegExp('(?:^|\\s)(?:@)(\\S+)', 'g');
		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[0]);
		}
		return matches;
	}

	static getHashTagNamesFromText(inputText: String): Array
	{
		const regex = new RegExp('(?:^|\\s)(?:#)(\\S+|)', 'g');
		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[1]);
		}
		return matches;
	}

	static getHashEpicNamesFromText(inputText: String): Array
	{
		const regex = new RegExp('(?:^|\\s)(?:@)(\\S+|)', 'g');
		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[1]);
		}
		return matches;
	}
}