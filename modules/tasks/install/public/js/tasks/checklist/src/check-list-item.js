import 'ui.design-tokens';
import './css/check-list-item.css';

import {Dom, Event, Loc, Runtime, Tag, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {CompositeTreeItem} from './composite-tree-item';
import {CheckListItemFields} from './check-list-item-fields';

class CheckListItem extends CompositeTreeItem
{
	class = CheckListItem;

	checkedClass = 'tasks-checklist-item-solved';
	hiddenClass = 'tasks-checklist-item-hidden';
	collapseClass = 'tasks-checklist-collapse';
	wrapperClass = 'tasks-checklist-items-wrapper';

	showClass = 'tasks-checklist-item-show';
	hideClass = 'tasks-checklist-item-hide';

	skipUpdateClasses = {
		header: [
			'.tasks-checklist-item-auditor',
			'.tasks-checklist-item-accomplice',
			'.tasks-checklist-item-link',
		],
		item: [
			'.tasks-checklist-item-auditor',
			'.tasks-checklist-item-accomplice',
			'.tasks-checklist-item-link',
			'.tasks-checklist-item-important',
			'.tasks-checklist-item-dragndrop',
			'.tasks-checklist-item-group-checkbox',
			'.tasks-checklist-item-remove',
			'.tasks-checklist-item-flag-block',
		],
	};

	static addDangerToElement(element)
	{
		const dangerClass = 'ui-ctl-danger';

		if (!Dom.hasClass(element, dangerClass))
		{
			Dom.addClass(element, dangerClass);
		}
	}

	static updateParents(oldParent, newParent)
	{
		if (oldParent !== newParent)
		{
			oldParent.updateCounts();
			newParent.updateCounts();

			oldParent.updateProgress();
			newParent.updateProgress();

			oldParent.updateIndexes();
			newParent.updateIndexes();
		}
		else
		{
			newParent.updateIndexes();
		}
	}

	static getProgressText(completed, total)
	{
		const replaces = {
			'#total#': total,
			'#completed#': completed,
		};
		let progressText = Loc.getMessage('TASKS_CHECKLIST_PROGRESS_BAR_PROGRESS_TEXT');

		Object.keys(replaces).forEach((search) => {
			progressText = progressText.replace(search, replaces[search]);
		});

		return progressText;
	}

	static getFileExtension(ext)
	{
		let fileExtension;

		switch (ext)
		{
			case 'mp4':
			case 'mkv':
			case 'mpeg':
			case 'avi':
			case '3gp':
			case 'flv':
			case 'm4v':
			case 'ogg':
			case 'swf':
			case 'wmv':
				fileExtension = 'mov';
				break;

			case 'txt':
				fileExtension = 'txt';
				break;

			case 'doc':
			case 'docx':
				fileExtension = 'doc';
				break;

			case 'xls':
			case 'xlsx':
				fileExtension = 'xls';
				break;

			case 'php':
				fileExtension = 'php';
				break;

			case 'pdf':
				fileExtension = 'pdf';
				break;

			case 'ppt':
			case 'pptx':
				fileExtension = 'ppt';
				break;

			case 'rar':
				fileExtension = 'rar';
				break;

			case 'zip':
				fileExtension = 'zip';
				break;

			case 'set':
				fileExtension = 'set';
				break;

			case 'mov':
				fileExtension = 'mov';
				break;

			case 'img':
			case 'jpg':
			case 'jpeg':
			case 'gif':
				fileExtension = 'img';
				break;

			default:
				fileExtension = 'empty';
				break;
		}

		return fileExtension;
	}

	static getInputSelection(input)
	{
		let start = 0;
		let end = 0;
		let normalizedValue;
		let range;
		let textInputRange;
		let len;
		let endRange;

		if (typeof input.selectionStart === 'number' && typeof input.selectionEnd === 'number')
		{
			start = input.selectionStart;
			end = input.selectionEnd;
		}
		else
		{
			range = document.selection.createRange();

			if (range && range.parentElement() === input)
			{
				len = input.value.length;
				normalizedValue = input.value.replace(/\r\n/g, '\n');

				// Create a working TextRange that lives only in the input
				textInputRange = input.createTextRange();
				textInputRange.moveToBookmark(range.getBookmark());

				// Check if the start and end of the selection are at the very end
				// of the input, since moveStart/moveEnd doesn't return what we want
				// in those cases
				endRange = input.createTextRange();
				endRange.collapse(false);

				if (textInputRange.compareEndPoints('StartToEnd', endRange) > -1)
				{
					start = len;
					end = len;
				}
				else
				{
					start = -textInputRange.moveStart('character', -len);
					start += normalizedValue.slice(0, start).split('\n').length - 1;

					if (textInputRange.compareEndPoints('EndToEnd', endRange) > -1)
					{
						end = len;
					}
					else
					{
						end = -textInputRange.moveEnd('character', -len);
						end += normalizedValue.slice(0, end).split('\n').length - 1;
					}
				}
			}
		}

		return {start, end};
	}

	static getDefaultCheckListTitle(title)
	{
		let defaultTitle = title;

		if (title.indexOf('BX_CHECKLIST') === 0)
		{
			if (title === 'BX_CHECKLIST')
			{
				defaultTitle = Loc.getMessage('TASKS_CHECKLIST_DEFAULT_DISPLAY_TITLE_2');
			}
			else if (title.match(/BX_CHECKLIST_\d+$/))
			{
				const itemNumber = title.replace('BX_CHECKLIST_', '');
				defaultTitle = Loc.getMessage('TASKS_CHECKLIST_DEFAULT_DISPLAY_TITLE_WITH_NUMBER').replace('#ITEM_NUMBER#', itemNumber);
			}
		}

		return defaultTitle;
	}

	static smoothScroll(node)
	{
		const posFrom = BX.GetWindowScrollPos().scrollTop;
		const posTo = BX.pos(node).top - Math.round(BX.GetWindowInnerSize().innerHeight / 2);
		const toBottom = posFrom < posTo;
		const distance = Math.abs(posTo - posFrom);
		const speed = (Math.round(distance / 100) > 20 ? 20 : Math.round(distance / 100));
		const step = speed / 2;

		if (step <= 0)
		{
			return;
		}

		let posCurrent = (toBottom ? posFrom + step : posFrom - step);
		let timer = 0;

		if (toBottom)
		{
			for (let i = posFrom; i < posTo; i += step)
			{
				setTimeout(`window.scrollTo(0, ${posCurrent})`, timer * speed);

				posCurrent += step;
				if (posCurrent > posTo)
				{
					posCurrent = posTo;
				}
				timer += 1;
			}
		}
		else
		{
			for (let i = posFrom; i > posTo; i -= step)
			{
				setTimeout(`window.scrollTo(0, ${posCurrent})`, timer * speed);

				posCurrent -= step;
				if (posCurrent < posTo)
				{
					posCurrent = posTo;
				}
				timer += 1;
			}
		}
	}

	static getMemberLinkLayout(type, name, url)
	{
		const messageId = `TASKS_CHECKLIST_${type.toUpperCase()}_ICON_HINT`;
		return `
			<span class="tasks-checklist-item-auditor">
				<a class="tasks-checklist-item-${type}-icon" title="${Loc.getMessage(messageId)}"></a>
				<a href="${url}" class="tasks-checklist-item-${type}-link">${name}</a>
			</span> 
		`;
	}

	static getLinkLayout(url)
	{
		return `<a class="tasks-checklist-item-link" href="${url}" target="_blank">${url}</a>`;
	}

	static get keyCodes()
	{
		return {
			esc: 27,
			enter: 13,
			plus: 43,
			atsign: 64,
			tab: 9,
			up: 38,
			down: 40,
			backspace: 8,
		};
	}

	constructor(fields = {})
	{
		const {action} = fields;

		super();
		this.fields = new CheckListItemFields(fields);
		this.action = {
			canUpdate: (action && 'MODIFY' in action) ? action.MODIFY : true,
			canRemove: (action && 'REMOVE' in action) ? action.REMOVE : true,
			canToggle: (action && 'TOGGLE' in action) ? action.TOGGLE : true,
			canDrag: (action && 'DRAG' in action) ? action.DRAG : true,
		};

		this.input = null;
		this.panel = null;
		this.progress = null;
		this.filesLoaderPopup = null;
		this.filesLoaderProgressBars = new Map();
		this.updateMode = false;
	}

	add(item, position = null)
	{
		super.add(item, position);

		item.optionManager = this.optionManager;
		item.clickEventHandler = this.clickEventHandler;
	}

	isTaskRoot()
	{
		return this.getNodeId() === 0 && this.getParent() === null;
	}

	isCheckList()
	{
		return !this.isTaskRoot() && this.getParent().isTaskRoot();
	}

	getCheckList()
	{
		let parent = this;

		while (!parent.getParent().isTaskRoot())
		{
			parent = parent.getParent();
		}

		return parent;
	}

	findById(id)
	{
		if (!id)
		{
			return null;
		}

		if (this.fields.getId() && this.fields.getId().toString() === id.toString())
		{
			return this;
		}

		let found = null;
		this.getDescendants().forEach((descendant) => {
			if (found === null)
			{
				found = descendant.findById(id);
			}
		});

		return found;
	}

	countCompletedCount(recursively = false)
	{
		let completedCount = 0;

		this.getDescendants().forEach((descendant) => {
			if (descendant.fields.getIsComplete())
			{
				completedCount += 1;
			}

			if (recursively)
			{
				completedCount += descendant.countCompletedCount(recursively);
			}
		});

		return completedCount;
	}

	countTotalCount(recursively = false)
	{
		let totalCount = 0;

		if (!recursively)
		{
			totalCount = this.getDescendantsCount();
		}
		else
		{
			this.getDescendants().forEach((descendant) => {
				totalCount += 1;
				totalCount += descendant.countTotalCount(recursively);
			});
		}

		return totalCount;
	}

	updateCompletedCount()
	{
		const completedCount = this.countCompletedCount();
		this.fields.setCompletedCount(completedCount);
	}

	updateTotalCount()
	{
		const totalCount = this.countTotalCount();
		this.fields.setTotalCount(totalCount);
	}

	updateCounts()
	{
		this.updateCompletedCount();
		this.updateTotalCount();
	}

	updateProgress()
	{
		if (this.progress === null)
		{
			return;
		}

		const total = this.fields.getTotalCount();
		const completed = this.fields.getCompletedCount();

		this.progress.setMaxValue(total);
		this.progress.update(completed);

		if (this.isCheckList())
		{
			this.updateProgressText(completed, total);
		}
	}

	updateProgressText(completed, total)
	{
		const progressText = CheckListItem.getProgressText(completed, total);
		this.progress.setTextAfter(progressText);
	}

	setDefaultStyles(layout, action = 'add')
	{
		if (action === 'add')
		{
			layout.style.overflow = 'hidden';
			layout.style.height = 0;
			layout.style.opacity = 0;

			Dom.addClass(layout, this.showClass);
		}
		else if (action === 'delete')
		{
			layout.style.overflow = 'hidden';
			layout.style.height = `${layout.scrollHeight}px`;
			layout.style.opacity = 1;

			Dom.addClass(layout, this.hideClass);
		}
	}

	delete()
	{
		const parent = this.getParent();

		parent.remove(this);
		parent.updateCounts();
		parent.updateProgress();

		this.setDefaultStyles(this.container, 'delete');

		setTimeout(() => {
			this.container.style.height = 0;
			this.container.style.opacity = 0;
			this.container.style.paddingTop = 0;
		}, 1);

		setTimeout(() => {
			Dom.remove(this.container);
		}, 250);
	}

	restore()
	{
		const parent = this.getParent();
		const position = this.fields.getSortIndex();

		if (position === 0)
		{
			if (parent.getDescendantsCount() > 0)
			{
				parent.addCheckListItem(this, parent.getFirstDescendant(), 'before');
			}
			else
			{
				parent.addCheckListItem(this);
			}
		}
		else
		{
			parent.addCheckListItem(this, parent.getDescendants()[position - 1]);
		}
	}

	deleteAction(showBalloon = true)
	{
		const title = this.fields.getTitle();

		this.delete();

		if (showBalloon && title.length > 0 && title !== '')
		{
			const action = 'DELETE';
			const data = {
				type: this.isCheckList() ? 'CHECKLIST' : 'ITEM',
			};

			this.getNotificationBalloon(action, data);
		}

		EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action: 'delete'});

		this.input = null;
		this.updateMode = false;
	}

	onDeleteClick(e)
	{
		e.preventDefault();

		Dom.hide(this.getRootNode().panel);

		if (this.checkSelectedItems())
		{
			const items = this.getSelectedItems();
			const action = 'DELETE_SELECTED';
			const data = {items};

			this.runForEachSelectedItem((item) => {
				item.fields.setIsSelected(false);
				item.deleteAction(false);
			});

			items.forEach((item) => {
				item.getParent().updateIndexes();
				item.handleCheckListChanges();
			});

			this.getNotificationBalloon(action, data);

			return;
		}

		this.deleteAction();
		this.getParent().updateIndexes();

		this.handleCheckListChanges();
	}

	getNotificationBalloon(action, data)
	{
		const actions = [];
		let content = '';

		switch (action)
		{
			case 'DELETE':
			{
				content = Loc.getMessage(`TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_${action}_${data.type}`);
				actions.push({
					title: Loc.getMessage('TASKS_CHECKLIST_NOTIFICATION_BALLOON_CANCEL'),
					events: {
						click: (event, balloon) =>
						{
							balloon.close();

							this.restore();
							this.handleCheckListChanges();
							this.handleTaskOptions();
						},
					},
				});
				break;
			}

			case 'DELETE_SELECTED':
			{
				content = Loc.getMessage(`TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_${action}_ITEMS`);
				actions.push({
					title: Loc.getMessage('TASKS_CHECKLIST_NOTIFICATION_BALLOON_CANCEL'),
					events: {
						click: (event, balloon) =>
						{
							balloon.close();

							data.items.forEach((item) => {
								item.restore();
								item.handleCheckListChanges();
							});

							this.handleTaskOptions();
						},
					},
				});
				break;
			}

			case 'AUDITOR_ADDED':
			case 'ACCOMPLICE_ADDED':
			{
				let image = '';
				if (data.avatar)
				{
					image = Tag.render`
						<img class="tasks-checklist-notification-balloon-avatar-img" src="${data.avatar}" alt=""/>
					`;
				}
				content = Tag.render`
					<div class="tasks-checklist-notification-balloon-message-container">
						<div class="tasks-checklist-notification-balloon-avatar">
							${image}
						</div>
						<span class="tasks-checklist-notification-balloon-message">
							${Loc.getMessage(`TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_${action}`)}
						</span>
					</div>
				`;
				break;
			}

			default:
			{
				break;
			}
		}

		BX.loadExt('ui.notification').then(() => {
			BX.UI.Notification.Center.notify({content, actions});
		});
	}

	onToAnotherCheckListClick(e)
	{
		const rootNode = this.getRootNode();

		if (rootNode.getDescendantsCount() === 1)
		{
			this.moveToNewCheckList(2);
			return;
		}

		new BX.PopupMenuWindow(
			'to-another-checklist',
			e.target,
			this.getToAnotherCheckListPopupItems(),
			{
				autoHide: true,
				closeByEsc: true,
				offsetLeft: e.target.offsetWidth / 3,
				angle: true,
				events: {
					onPopupClose() {
						this.destroy();
					},
				},
			},
		).show();
	}

	getToAnotherCheckListPopupItems()
	{
		const selectMode = this.checkSelectedItems();
		const popupMenuItems = [];
		const toNewCheckListMenuItem = {
			text: Tag.message`+ ${'TASKS_CHECKLIST_PANEL_TO_ANOTHER_CHECKLIST_POPUP_NEW_CHECKLIST'}`,
			onclick: (event, item) => {
				item.getMenuWindow().close();
				this.moveToNewCheckList(this.getRootNode().getDescendantsCount() + 1);
			},
		};

		if (selectMode)
		{
			this.getDescendants().forEach((descendant) => {
				popupMenuItems.push({
					text: descendant.fields.getTitle(),
					onclick: (event, item) => {
						item.getMenuWindow().close();

						this.runForEachSelectedItem((selectedItem) => {
							selectedItem.makeChildOf(descendant);
							descendant.unselectAll();

							if (!this.checkSelectedItems())
							{
								Dom.hide(this.getRootNode().panel);
							}
						});
					},
				});
			});

			popupMenuItems.push({delimiter: true});
			popupMenuItems.push(toNewCheckListMenuItem);

			return popupMenuItems;
		}

		const checkList = this.getCheckList();
		const checkLists = this.getRootNode().getDescendants().filter(item => item !== checkList);

		checkLists.forEach((descendant) => {
			popupMenuItems.push({
				text: descendant.fields.getTitle(),
				onclick: (event, item) => {
					item.getMenuWindow().close();
					this.makeChildOf(descendant);
					this.handleUpdateEnding();
				},
			});
		});

		popupMenuItems.push({delimiter: true});
		popupMenuItems.push(toNewCheckListMenuItem);

		return popupMenuItems;
	}

	moveToNewCheckList(number)
	{
		const title = `${Loc.getMessage('TASKS_CHECKLIST_NEW_CHECKLIST_TITLE')}`.replace('#ITEM_NUMBER#', number);
		const newCheckList = new CheckListItem({TITLE: title});

		this.getRootNode().addCheckListItem(newCheckList).then(() => {
			if (this.checkSelectedItems())
			{
				this.runForEachSelectedItem((selectedItem) => {
					selectedItem.makeChildOf(newCheckList);
					newCheckList.unselectAll();

					if (!this.checkSelectedItems())
					{
						Dom.hide(this.getRootNode().panel);
					}
				});
			}
			else
			{
				this.makeChildOf(newCheckList);
				this.handleUpdateEnding();
			}
		});
	}

	processMemberSelect(member)
	{
		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				const title = selectedItem.fields.getTitle();
				const space = (title.slice(-1) === ' ' ? '' : ' ');
				const newTitle = `${title}${space}${member.nameFormatted}`;

				selectedItem.fields.addMember(member);
				selectedItem.updateTitle(Text.decode(newTitle));
				selectedItem.updateTitleNode();
			});

			return;
		}

		const inputText = this.input.value;
		const mentioned = +this.mentioned;
		const start = this.inputCursorPosition.start || 0;
		const startSpace = (start === 0 || start - mentioned === 0 || inputText.charAt(start - mentioned - 1) === ' ') ? '' : ' ';
		const endSpace = (inputText.charAt(start) === ' ' ? '' : ' ');

		this.fields.addMember(member);

		const newInputText = `${inputText.slice(0, start - mentioned)}${startSpace}${Text.decode(member.nameFormatted)}${endSpace}`;

		this.inputCursorPosition.start = newInputText.length;
		this.inputCursorPosition.end = newInputText.length;

		this.input.value = `${newInputText}${inputText.slice(start)}`;
		this.mentioned = false;

		this.retrieveFocus();
	}

	onSocNetSelectorAuditorSelected(auditor)
	{
		const type = 'auditor';
		const userData = this.prepareUserData(auditor);
		const resultAuditor = {...userData, type};

		const notificationAction = `${type.toUpperCase()}_ADDED`;
		const notificationData = {avatar: auditor.avatar};

		this.processMemberSelect(resultAuditor);
		this.getNotificationBalloon(notificationAction, notificationData);

		EventEmitter.emit('BX.Tasks.CheckListItem:auditorAdded', userData);
		EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action: 'addAuditor'});
	}

	onSocNetSelectorAccompliceSelected(accomplice)
	{
		const type = 'accomplice';
		const userData = this.prepareUserData(accomplice);
		const resultAccomplice = {...userData, type};

		const notificationAction = `${type.toUpperCase()}_ADDED`;
		const notificationData = {avatar: accomplice.avatar};

		this.processMemberSelect(resultAccomplice);
		this.getNotificationBalloon(notificationAction, notificationData);

		EventEmitter.emit('BX.Tasks.CheckListItem:accompliceAdded', userData);
		EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action: 'addAccomplice'});
	}

	prepareUserData(user)
	{
		const customData = user.getCustomData();
		const entityType = user.getEntityType();

		return {
			avatar: user.avatar,
			description: '',
			entityType: 'U',
			id: user.getId(),
			name: customData.get('name'),
			lastName: customData.get('lastName'),
			email: customData.get('email'),
			nameFormatted: Text.encode(user.getTitle()),
			networkId: '',
			type: {
				crmemail: false,
				extranet: (entityType === 'extranet'),
				email: (entityType === 'email'),
				network: (entityType === 'network'),
			},
		};
	}

	getMemberSelector(e, memberType = 'auditor', mentioned = false)
	{
		if (!this.checkCanAddAccomplice())
		{
			return;
		}

		const typeFunctionMap = {
			auditor: this.onSocNetSelectorAuditorSelected.bind(this),
			accomplice: this.onSocNetSelectorAccompliceSelected.bind(this),
		};
		const typeFunction = typeFunctionMap[memberType] || typeFunctionMap.auditor;

		this.isSelectorLoading = true;

		Runtime.loadExtension('ui.entity-selector').then(exports => {
			const {Dialog} = exports;
			const dialog = new Dialog({
				targetNode: e.target,
				enableSearch: true,
				multiple: false,
				entities: [
					{
						id: 'user',
						options: {
							inviteGuestLink: false,
							inviteEmployeeLink: false,
							emailUsers: true,
							networkUsers: this.optionManager.isNetworkEnabled,
							extranetUsers: true,
						},
					},
				],
				events: {
					'onLoad': (event: BaseEvent) => {
						this.isSelectorLoading = false;
					},
					'Item:onSelect': (event: BaseEvent) => {
						this.isSelectorLoading = false;
						this.mentioned = mentioned;
						this.retrieveFocus();

						const {item: selectedItem} = event.getData();
						typeFunction(selectedItem);
					},
				},
			});
			dialog.show();
		});
	}

	onAddAuditorClick(e)
	{
		this.getMemberSelector(e, 'auditor');
	}

	onAddAccompliceClick(e)
	{
		this.getMemberSelector(e, 'accomplice');
	}

	onUploadAttachmentClick(e)
	{
		const nodeId = this.getNodeId();
		const {prefix, diskUrls} = this.optionManager;
		const {urlSelect, urlRenameFile, urlDeleteFile, urlUpload} = diskUrls;

		if (this.filesLoaderPopup === null)
		{
			this.filesLoaderPopup = new BX.PopupWindow({
				content: this.getAttachmentsLoaderLayout(),
				bindElement: e.target,
				offsetLeft: e.target.offsetWidth / 2,
				autoHide: true,
				closeByEsc: true,
				angle: true,
			});
		}
		else
		{
			this.filesLoaderPopup.setBindElement(e.target);
		}

		this.filesLoaderPopup.show();

		BX.Disk.UF.add({
			UID: nodeId,
			controlName: `${prefix}[${nodeId}][UF_CHECKLIST_FILES][]`,
			hideSelectDialog: false,
			urlSelect,
			urlRenameFile,
			urlDeleteFile,
			urlUpload,
		});

		BX.onCustomEvent(
			this.filesLoaderPopup.contentContainer.querySelector('#files_chooser'),
			'DiskLoadFormController',
			['show'],
		);
	}

	onDeleteAttachmentClick(fileId)
	{
		this.fields.removeAttachment(fileId);
		Dom.remove(this.getAttachmentsContainer().querySelector(`#disk-attach-${fileId}`));
		EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action: 'deleteAttachment'});
	}

	getPanelBodyLayout()
	{
		const membersLayout = Tag.render`
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-auditor" onclick="${this.onAddAuditorClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
				<span class="tasks-checklist-item-editor-panel-text">${Tag.message`+ ${'TASKS_CHECKLIST_PANEL_AUDITOR'}`}</span>
			</div>
			<div class="tasks-checklist-item-editor-panel-separator"></div>
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-accomplice" onclick="${this.onAddAccompliceClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
				<span class="tasks-checklist-item-editor-panel-text">${Tag.message`+ ${'TASKS_CHECKLIST_PANEL_ACCOMPLICE'}`}</span>
			</div>
		`;
		const attachmentButtonLayout = Tag.render`
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-attachment" onclick="${this.onUploadAttachmentClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
			</div>
		`;
		const itemsActionButtonsLayout = Tag.render`
			${this.checkSelectedItems() ? '' : attachmentButtonLayout}
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-tabin" onclick="${this.onTabInClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
			</div>
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-tabout" onclick="${this.onTabOutClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
			</div>
			<div class="tasks-checklist-item-editor-panel-separator"></div>
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-important
				${this.fields.getIsImportant() ? ' tasks-checklist-item-editor-panel-btn-important-selected' : ''}" onclick="${this.onImportantClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
				<span class="tasks-checklist-item-editor-panel-text">${Loc.getMessage('TASKS_CHECKLIST_PANEL_IMPORTANT')}</span>
			</div>
			<div class="tasks-checklist-item-editor-panel-separator"></div>
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-checklist" onclick="${this.onToAnotherCheckListClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
				<span class="tasks-checklist-item-editor-panel-text">${Loc.getMessage('TASKS_CHECKLIST_PANEL_TO_ANOTHER_CHECKLIST')}</span>
			</div>
			<div class="tasks-checklist-item-editor-panel-separator"></div>
			<div class="tasks-checklist-item-editor-panel-btn tasks-checklist-item-editor-panel-btn-remove" onclick="${this.onDeleteClick.bind(this)}">
				<span class="tasks-checklist-item-editor-panel-icon"></span>
			</div>
		`;
		const separator = Tag.render`<div class="tasks-checklist-item-editor-panel-separator"></div>`;

		return Tag.render`
			<div class="tasks-checklist-item-editor-panel ${this.isTaskRoot() || this.isCheckList() ? 'tasks-checklist-item-editor-group-panel' : ''}">
				${this.checkCanAddAccomplice() ? membersLayout : ''}
				${this.checkCanAddAccomplice() && !this.isCheckList() ? separator : ''}
				${!this.isCheckList() ? itemsActionButtonsLayout : ''}
			</div>
		`;
	}

	updateTitle(text)
	{
		this.fields.setTitle(text);
	}

	updateTitleNode()
	{
		Dom.replace(this.getTitleNodeContainer(), this.getTitleLayout());
	}

	getTitleLayout()
	{
		const {userPath} = this.optionManager;
		const escapeRegExp = (string) => string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
		let title = this.fields.getTitle();

		title = (this.isCheckList() ? CheckListItem.getDefaultCheckListTitle(title) : title);

		this.fields.getMembers().forEach(({id, nameFormatted, type}) => {
			const regExp = new RegExp(escapeRegExp(nameFormatted), 'g');
			const url = userPath.replace('#user_id#', id).replace('#USER_ID#', id);
			title = title.replace(regExp, CheckListItem.getMemberLinkLayout(type, nameFormatted, url));
		});

		title = title.replace(/(https?:\/\/[^\s]+)/g, url => CheckListItem.getLinkLayout(url));

		return Tag.render`
			<div class="${this.getTitleNodeClass()}">
				${title}
			</div>
		`;
	}

	processMembersFromText()
	{
		const membersToDelete = [];

		this.fields.getMembers().forEach(({id, nameFormatted}) => {
			if (this.fields.getTitle().indexOf(nameFormatted) === -1)
			{
				membersToDelete.push(id);
			}
		});

		membersToDelete.forEach((id) => {
			this.fields.removeMember(id);
		});
	}

	updateIndexes()
	{
		this.updateSortIndexes();
		this.updateDisplaySortIndexes();
	}

	updateSortIndexes()
	{
		let sortIndex = 0;

		this.getDescendants().forEach((descendant) => {
			descendant.fields.setSortIndex(sortIndex);
			sortIndex += 1;
		});
	}

	updateDisplaySortIndexes()
	{
		const parentSortIndex = (this.isCheckList() || this.isTaskRoot() ? '' : `${this.fields.getDisplaySortIndex()}.`);
		let localSortIndex = 0;

		this.getDescendants().forEach((descendant) => {
			localSortIndex += 1;
			const newSortIndex = `${parentSortIndex}${localSortIndex}`;

			descendant.fields.setDisplaySortIndex(newSortIndex);

			if (!descendant.isCheckList())
			{
				descendant.container.querySelector('.tasks-checklist-item-number').innerText = newSortIndex;
			}

			descendant.updateDisplaySortIndexes();
		});
	}

	handleTaskOptions()
	{
		const {userId, showCompleted, showOnlyMine} = this.optionManager;

		this.getRootNode().hideByCondition((item) => {
			const isComplete = item.fields.getIsComplete();
			const hasUserInMembers = item.fields.getMembers().has(userId.toString());
			let condition;

			if (!showCompleted && showOnlyMine)
			{
				condition = isComplete || !hasUserInMembers;
			}
			else if (!showCompleted)
			{
				condition = isComplete;
			}
			else if (showOnlyMine)
			{
				condition = !hasUserInMembers;
			}
			else
			{
				condition = false;
			}

			return condition;
		});
	}

	hideByCondition(condition)
	{
		if (this.checkCanHide(condition))
		{
			this.hide();
		}
		else
		{
			this.show();
			this.getDescendants().forEach((descendant) => {
				descendant.hideByCondition(condition);
			});
		}
	}

	checkCanHide(condition)
	{
		if (
			this.isTaskRoot()
			|| this.updateMode
			|| Dom.hasClass(this.container, this.showClass)
			|| !condition(this)
		)
		{
			return false;
		}

		let canHide = true;

		this.getDescendants().forEach((descendant) => {
			if (!condition(descendant))
			{
				canHide = false;
			}
			else if (canHide)
			{
				canHide = descendant.checkCanHide(condition);
			}
		});

		return canHide;
	}

	hide()
	{
		Dom.hide(this.container);
	}

	show()
	{
		Dom.show(this.container);
	}

	checkIsComplete()
	{
		let isComplete;

		if (this.isTaskRoot())
		{
			isComplete = false;
		}
		else if (this.isCheckList())
		{
			const completedCount = this.countCompletedCount(true);
			const totalCount = this.countTotalCount(true);

			isComplete = (completedCount === totalCount && totalCount > 0);
		}
		else
		{
			isComplete = this.fields.getIsComplete();
		}

		return isComplete;
	}

	checkActiveUpdateExist()
	{
		if (this.updateMode)
		{
			return true;
		}

		let found = false;

		this.getDescendants().forEach((descendant) => {
			if (found === false)
			{
				found = descendant.checkActiveUpdateExist();
			}
		});

		return found;
	}

	disableAllGroup()
	{
		this.getDescendants().forEach((descendant) => {
			if (descendant.fields.getIsSelected())
			{
				descendant.toggleGroup();
			}
		});
	}

	disableAllUpdateModes()
	{
		if (this.updateMode)
		{
			this.handleUpdateEnding();
		}

		this.getDescendants().forEach((descendant) => {
			descendant.disableAllUpdateModes();
		});
	}

	rememberInputState()
	{
		this.input = this.container.querySelector(`#text_${this.getNodeId()}`);
		this.inputCursorPosition = CheckListItem.getInputSelection(this.input);
	}

	clearInput(e)
	{
		e.preventDefault();

		this.container.querySelector(`#text_${this.getNodeId()}`).value = '';
		this.retrieveFocus();
	}

	retrieveFocus()
	{
		if (this.input !== null && this.inputCursorPosition)
		{
			const {start, end} = this.inputCursorPosition;

			setTimeout(() => {
				this.input.focus();
				this.input.setSelectionRange(start, end);
			}, 10);
		}
	}

	getUpdateModeLayout()
	{
		const nodeId = this.getNodeId();

		if (this.isCheckList())
		{
			return Tag.render`
				<div class="tasks-checklist-header-name tasks-checklist-header-name-edit-mode">
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-after-icon ui-ctl-xs ui-ctl-no-padding ui-ctl-underline 
								tasks-checklist-header-name-editor">
						<input class="ui-ctl-element" type="text" id="text_${nodeId}"
							   value="${this.fields.getTitle()}"
							   onkeydown="${this.onInputKeyDown.bind(this)}"
							   onblur="${this.rememberInputState.bind(this)}"/>
						<button class="ui-ctl-after ui-ctl-icon-clear" onclick="${this.clearInput.bind(this)}"></button>
					</div>
				</div>
			`;
		}

		const progressBarLayout = new BX.UI.ProgressRound({
			value: this.fields.getCompletedCount(),
			maxValue: this.fields.getTotalCount(),
			width: 20,
			lineSize: 3,
			fill: false,
			color: BX.UI.ProgressRound.Color.PRIMARY,
		});

		return Tag.render`
			<div class="tasks-checklist-item-inner tasks-checklist-item-new ${this.fields.getIsComplete() ? 'tasks-checklist-item-solved' : ''}">
				<div class="tasks-checklist-item-flag-block">
					<div class="tasks-checklist-item-flag">
						<label class="tasks-checklist-item-flag-element" onclick="${this.onCompleteButtonClick.bind(this)}">
							<span class="tasks-checklist-item-flag-sub-checklist-progress">
								${progressBarLayout.getContainer()}
							</span>
							<span class="tasks-checklist-item-flag-element-decorate"></span>
						</label>
					</div>
				</div>
				<div class="tasks-checklist-item-content-block">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon ui-ctl-w100">
						<input class="ui-ctl-element" type="text" id="text_${nodeId}"
							   placeholder="${Loc.getMessage('TASKS_CHECKLIST_NEW_ITEM_PLACEHOLDER')}"
							   value="${this.fields.getTitle()}"
							   onkeydown="${this.onInputKeyDown.bind(this)}"
							   onblur="${this.rememberInputState.bind(this)}"/>
						<button class="ui-ctl-after ui-ctl-icon-clear" onclick="${this.clearInput.bind(this)}"></button>
					</div>
				</div>
			</div>
		`;
	}

	showEditorPanel(item, nodeToPosition = null)
	{
		const node = nodeToPosition || item.getContainer();
		const position = Dom.getPosition(node);

		if (!this.panel)
		{
			this.panel = Tag.render`
				<div class="tasks-checklist-item-editor-panel-container">
					${item.getPanelBodyLayout()}
				</div>
			`;

			this.panel.style.top = `${position.top}px`;
			this.panel.style.left = `${position.left}px`;
			this.panel.style.width = `${position.width}px`;

			Dom.append(this.panel, document.body);
		}
		else
		{
			Dom.replace(this.panel.querySelector('.tasks-checklist-item-editor-panel'), item.getPanelBodyLayout());

			this.panel.style.top = `${position.top}px`;
			this.panel.style.left = `${position.left}px`;
			this.panel.style.width = `${position.width}px`;
		}

		if (!Dom.isShown(this.panel))
		{
			Dom.show(this.panel);
		}

		if (
			(Dom.isShown(this.panel) && item.isCheckList() && !item.checkCanAddAccomplice())
			|| (position.left === 0 && position.right === 0 && position.width === 0)
		)
		{
			Dom.hide(this.panel);
		}
	}

	enableUpdateMode()
	{
		const viewModeLayout = this.getInnerContainer();
		const updateModeLayout = this.getUpdateModeLayout();

		Dom.addClass(viewModeLayout, this.hiddenClass);
		Dom.insertBefore(updateModeLayout, viewModeLayout);

		this.input = updateModeLayout.querySelector(`#text_${this.getNodeId()}`);
		this.input.focus();
		this.input.setSelectionRange(this.input.value.length, this.input.value.length);
		this.inputCursorPosition = CheckListItem.getInputSelection(this.input);

		Event.bind(this.input, 'beforeinput', this.onInputBeforeInput.bind(this));

		if (this.input.value === '' || this.input.value.length === 0)
		{
			setTimeout(() => {
				if (Dom.isShown(this.input))
				{
					this.getRootNode().showEditorPanel(this, this.input);
				}
			}, 250);
		}
		else
		{
			this.getRootNode().showEditorPanel(this, this.input);
		}

		this.updateMode = true;
	}

	disableUpdateMode()
	{
		const currentInner = this.getInnerContainer();
		const text = currentInner.querySelector(`#text_${this.getNodeId()}`).value.trim();

		this.updateTitle(text);
		this.processMembersFromText();
		this.updateTitleNode();

		Dom.removeClass(currentInner.nextElementSibling, this.hiddenClass);
		Dom.remove(currentInner);
		Dom.hide(this.getRootNode().panel);

		this.input = null;
		this.updateMode = false;
	}

	checkCanDeleteOnUpdateEnding()
	{
		return this.getDescendantsCount() === 0
			&& Object.keys(this.fields.getAttachments()).length === 0
			&& this.filesLoaderProgressBars.size === 0;
	}

	handleUpdateEnding(createNewItem = false)
	{
		const input = this.container.querySelector(`#text_${this.getNodeId()}`);
		const text = input.value.trim();

		if (text.length === 0)
		{
			if (this.checkCanDeleteOnUpdateEnding())
			{
				this.deleteAction(false);
				this.getParent().updateIndexes();
				this.handleCheckListIsEmpty();

				Dom.hide(this.getRootNode().panel);
			}
			else
			{
				CheckListItem.addDangerToElement(input.parentElement);

				if (this.input !== null)
				{
					this.getRootNode().showEditorPanel(this, this.input);
				}
			}
		}
		else if (createNewItem)
		{
			this.getParent().addCheckListItem(null, this);
		}
		else
		{
			this.disableUpdateMode();
			this.handleTaskOptions();
		}

		if (this.filesLoaderPopup !== null)
		{
			this.filesLoaderPopup.close();
		}
	}

	toggleUpdateMode(e)
	{
		if (this.updateMode)
		{
			if (e.keyCode === CheckListItem.keyCodes.enter || e.keyCode === CheckListItem.keyCodes.tab)
			{
				this.handleUpdateEnding(!this.isCheckList());
			}
			else if (e.keyCode === CheckListItem.keyCodes.esc)
			{
				this.handleUpdateEnding();
			}
		}
		else
		{
			const rootNode = this.getRootNode();

			rootNode.disableAllUpdateModes();
			rootNode.disableAllGroup();

			if (!rootNode.checkActiveUpdateExist())
			{
				this.enableUpdateMode();
				EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action: 'toggleUpdateMode'});
			}
		}
	}

	onInputKeyDown(e)
	{
		if (this.isSelectorLoading)
		{
			e.preventDefault();
			return;
		}

		switch (e.keyCode)
		{
			case CheckListItem.keyCodes.esc:
			case CheckListItem.keyCodes.enter:
			{
				e.preventDefault();
				setTimeout(() => this.toggleUpdateMode(e));
				break;
			}

			case CheckListItem.keyCodes.tab:
			{
				if (!this.isCheckList())
				{
					(e.shiftKey ? this.tabOut.bind(this) : this.tabIn.bind(this))();
				}
				this.retrieveFocus();
				break;
			}

			case CheckListItem.keyCodes.up:
			{
				const leftSiblingThrough = this.getLeftSiblingThrough();
				if (leftSiblingThrough && leftSiblingThrough !== this.getRootNode())
				{
					leftSiblingThrough.toggleUpdateMode(e);
				}
				break;
			}

			case CheckListItem.keyCodes.down:
			{
				const rightSiblingThrough = this.getRightSiblingThrough();
				if (rightSiblingThrough)
				{
					rightSiblingThrough.toggleUpdateMode(e);
				}
				break;
			}

			default:
				// do nothing
				break;
		}
	}

	onInputBeforeInput(e)
	{
		if (this.isSelectorLoading)
		{
			e.preventDefault();
			return;
		}

		if (['+', '@'].includes(e.data))
		{
			this.getMemberSelector(e, this.optionManager.defaultMemberSelectorType, true);
		}
	}

	onHeaderMouseDown(e)
	{
		this.clickEventHandler.handleMouseDown(e);
		this.clickEventHandler.registerClickDoneCallback(this.onHeaderClickDone.bind(this, e));
	}

	onHeaderMouseUp(e)
	{
		this.clickEventHandler.handleMouseUp(e);
	}

	onHeaderClickDone(e)
	{
		if (!this.checkCanUpdate() || this.checkSkipUpdate(e, 'header'))
		{
			return;
		}

		this.toggleUpdateMode(e);
	}

	onInnerContainerMouseDown(e)
	{
		this.clickEventHandler.handleMouseDown(e);
		this.clickEventHandler.registerClickDoneCallback(this.onInnerContainerClickDone.bind(this, e));
	}

	onInnerContainerMouseUp(e)
	{
		this.clickEventHandler.handleMouseUp(e);
	}

	onInnerContainerClickDone(e)
	{
		if (!this.checkCanUpdate() || this.checkSkipUpdate(e, 'item'))
		{
			return;
		}

		if (this.getCheckList().fields.getIsSelected())
		{
			this.toggleSelect(e);
			return;
		}

		this.toggleUpdateMode(e);
	}

	getImportantLayout()
	{
		return Tag.render`
			<div class="tasks-checklist-item-important" onclick="${this.onImportantClick.bind(this)}"></div>
		`;
	}

	toggleImportant()
	{
		if (this.fields.getIsImportant())
		{
			this.fields.setIsImportant(false);
			Dom.remove(this.container.querySelector('.tasks-checklist-item-important'));
		}
		else
		{
			this.fields.setIsImportant(true);
			Dom.insertBefore(this.getImportantLayout(), this.container.querySelector('.tasks-checklist-item-description'));
		}

		EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action: 'toggleImportant'});

		this.retrieveFocus();
	}

	checkSelectedItems()
	{
		return this.getRootNode().getSelectedItems().length > 0;
	}

	runForEachSelectedItem(callback, reverse = false)
	{
		let selectedItems = this.getRootNode().getSelectedItems();

		if (reverse)
		{
			selectedItems = [...selectedItems.reverse()];
		}

		selectedItems.forEach((item) => {
			callback(item);
		});
	}

	onImportantClick(e)
	{
		if (!this.checkCanUpdate())
		{
			return;
		}

		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				selectedItem.toggleImportant();
			});

			return;
		}

		this.toggleImportant();

		const panelImportantButton = e.target.closest('.tasks-checklist-item-editor-panel-btn-important');
		if (panelImportantButton)
		{
			Dom.toggleClass(panelImportantButton, 'tasks-checklist-item-editor-panel-btn-important-selected');
		}
	}

	onCompleteAllButtonClick()
	{
		if (this.fields.getIsSelected() || this.updateMode)
		{
			return;
		}

		this.completeAll();
		this.runAjaxCompleteAll();
	}

	completeAll(): void
	{
		this.getDescendants().forEach((descendant) => {
			if (
				descendant.checkCanToggle()
				&& !descendant.updateMode
				&& !descendant.fields.getIsComplete()
			)
			{
				descendant.toggleComplete(false);
			}
			descendant.completeAll();
		});
	}

	runAjaxCompleteAll(): void
	{
		const {ajaxActions, entityId, entityType, stableTreeStructure} = this.optionManager;

		if (!ajaxActions || !ajaxActions.COMPLETE_ALL)
		{
			return;
		}

		BX.ajax.runAction(ajaxActions.COMPLETE_ALL, {
			data: {
				[`${entityType.toLowerCase()}Id`]: entityId,
				checkListItemId: this.fields.getId(),
			}
		}).then((response) => {
			response.data.forEach(item => {
				this.findById(item.id).updateStableTreeStructure(item.isComplete, stableTreeStructure, stableTreeStructure);
			});
		});
	}

	onCompleteButtonClick()
	{
		if (
			this.getCheckList().fields.getIsSelected()
			|| this.updateMode
			|| !this.checkCanToggle()
		)
		{
			return;
		}

		this.toggleComplete();
	}

	toggleComplete(runAjax = true)
	{
		const isComplete = this.fields.getIsComplete();

		this.fields.setIsComplete(!isComplete);
		this.getParent().updateCounts();
		this.getParent().updateProgress();

		Dom.toggleClass(this.getInnerContainer(), this.checkedClass);

		this.handleCheckListChanges();
		this.handleTaskOptions();

		if (runAjax)
		{
			this.runAjaxToggleComplete();
		}
	}

	runAjaxToggleComplete()
	{
		const id = this.fields.getId();

		if (!id)
		{
			return;
		}

		const data = {};
		const {ajaxActions, entityId, entityType, stableTreeStructure} = this.optionManager;
		const actionName = (this.fields.getIsComplete() ? ajaxActions.COMPLETE : ajaxActions.RENEW);

		data[`${entityType.toLowerCase()}Id`] = entityId;
		data.checkListItemId = id;

		BX.ajax.runAction(actionName, {data}).then((response) => {
			const {isComplete} = response.data.checkListItem;
			this.updateStableTreeStructure(isComplete, stableTreeStructure, stableTreeStructure);
		});
	}

	updateStableTreeStructure(isComplete, item, parent)
	{
		if (this.fields.getId() === item.FIELDS.id)
		{
			item.FIELDS.isComplete = isComplete;
			parent.FIELDS.completedCount += (isComplete ? 1 : -1);

			return this;
		}

		let found = null;
		item.DESCENDANTS.forEach((descendant) => {
			if (found === null)
			{
				found = this.updateStableTreeStructure(isComplete, descendant, item);
			}
		});

		return found;
	}

	unselectAll()
	{
		const checkBox = this.container.querySelector(`#select_${this.getNodeId()}`);

		if (checkBox && checkBox.checked === true)
		{
			this.fields.setIsSelected(false);

			checkBox.checked = false;
			Dom.removeClass(this.getInnerContainer(), 'tasks-checklist-item-selected');
		}

		this.getDescendants().forEach((descendant) => {
			descendant.unselectAll();
		});
	}

	getSelected()
	{
		let selected = [];

		if (this.fields.getIsSelected())
		{
			selected.push(this);
		}

		this.getDescendants().forEach((descendant) => {
			selected = [...selected, ...descendant.getSelected()];
		});

		return selected;
	}

	getSelectedItems()
	{
		return this.getSelected().filter(item => !item.isCheckList() && !item.isTaskRoot());
	}

	onSelectCheckboxClick(e)
	{
		if (!this.checkCanUpdate())
		{
			e.target.checked = false;
			return;
		}

		this.toggleSelect();
	}

	toggleSelect()
	{
		const rootNode = this.getRootNode();

		if (this.fields.getIsSelected())
		{
			this.container.querySelector(`#select_${this.getNodeId()}`).checked = false;
			this.fields.setIsSelected(false);

			rootNode.showEditorPanel(rootNode, this.container);

			if (!this.checkSelectedItems())
			{
				Dom.hide(rootNode.panel);
			}
		}
		else
		{
			this.container.querySelector(`#select_${this.getNodeId()}`).checked = true;
			this.fields.setIsSelected(true);

			rootNode.showEditorPanel(rootNode, this.container);
		}

		Dom.toggleClass(this.getInnerContainer(), 'tasks-checklist-item-selected');
	}

	onGroupButtonClick()
	{
		if (!this.getRootNode().checkActiveUpdateExist())
		{
			this.toggleGroup();
		}
	}

	toggleGroup()
	{
		if (this.fields.getIsSelected())
		{
			this.unselectAll();
			this.fields.setIsSelected(false);

			if (!this.checkSelectedItems())
			{
				Dom.hide(this.getRootNode().panel);
			}
		}
		else
		{
			this.fields.setIsSelected(true);

			if (this.fields.getIsCollapse())
			{
				this.toggleCollapse();
			}
		}

		Dom.toggleClass(this.container, 'tasks-checklist-item-group-editor-collapse');
		Dom.toggleClass(this.container, 'tasks-checklist-item-group-editor-expand');
	}

	onCollapseButtonClick()
	{
		if (this.collapseFreezed)
		{
			return;
		}

		this.toggleCollapse();
	}

	toggleCollapse()
	{
		this.collapseFreezed = true;

		const wrapperList = this.container.querySelector(`.${this.wrapperClass}`);
		const wrapperListHeight = `${Dom.getPosition(wrapperList).height}px`;

		if (!Dom.hasClass(this.container, this.collapseClass))
		{
			this.fields.setIsCollapse(true);

			wrapperList.style.overflow = 'hidden';
			wrapperList.style.height = wrapperListHeight;
			setTimeout(() => { wrapperList.style.height = 0; }, 0);

			Dom.addClass(this.container, this.collapseClass);

			this.collapseFreezed = false;
		}
		else
		{
			this.fields.setIsCollapse(false);

			wrapperList.style.height = 0;
			wrapperList.style.height = `${wrapperList.scrollHeight}px`;

			const setAutoHeight = () => {
				wrapperList.style.height = 'auto';
				BX.unbind(wrapperList, 'transitionend', setAutoHeight);

				this.collapseFreezed = false;
			};
			BX.bind(wrapperList, 'transitionend', setAutoHeight);

			Dom.removeClass(this.container, this.collapseClass);
		}
	}

	toggleEmpty()
	{
		Dom.toggleClass(this.container, 'tasks-checklist-empty');
	}

	handleCheckListIsComplete()
	{
		const checkList = this.getCheckList();
		const checkListIsComplete = checkList.checkIsComplete();

		checkList.fields.setIsComplete(checkListIsComplete);

		if (
			checkListIsComplete
			&& !Dom.hasClass(checkList.container, 'tasks-checklist-collapse')
			&& this.collapseOnCompleteAll()
		)
		{
			checkList.toggleCollapse();
		}
	}

	handleCheckListIsEmpty()
	{
		const checkList = this.getCheckList();
		const checkListIsEmpty = checkList.getDescendantsCount() === 0;

		if (
			(checkListIsEmpty && !Dom.hasClass(checkList.container, 'tasks-checklist-empty'))
			|| (!checkListIsEmpty && Dom.hasClass(checkList.container, 'tasks-checklist-empty'))
		)
		{
			checkList.toggleEmpty();
		}
	}

	handleCheckListChanges()
	{
		this.handleCheckListIsComplete();
		this.handleCheckListIsEmpty();
	}

	checkSkipUpdate(e, area)
	{
		return this.skipUpdateClasses[area]
			&& this.skipUpdateClasses[area].find(item => e.target.closest(item));
	}

	showCompleteAllButton()
	{
		return this.optionManager.getShowCompleteAllButton();
	}

	collapseOnCompleteAll()
	{
		return this.optionManager.getCollapseOnCompleteAll();
	}

	checkCanAdd()
	{
		return this.optionManager.getCanAdd();
	}

	checkCanAddAccomplice()
	{
		return this.optionManager.getCanAddAccomplice();
	}

	checkCanUpdate()
	{
		return this.action.canUpdate;
	}

	checkCanRemove()
	{
		return this.action.canRemove;
	}

	checkCanToggle()
	{
		return this.action.canToggle;
	}

	checkCanDrag()
	{
		return this.action.canDrag;
	}

	getTitleNodeClass()
	{
		return (this.isCheckList() ? 'tasks-checklist-header-name-text' : 'tasks-checklist-item-description-text');
	}

	getTitleNodeContainer()
	{
		return this.container.querySelector(`.${this.getTitleNodeClass()}`);
	}

	getAttachmentsContainer()
	{
		return this.container.querySelector(`#attachments_${this.getNodeId()}`);
	}

	getSubItemsContainer()
	{
		return this.container.querySelector(`#subItems_${this.getNodeId()}`);
	}

	getInnerContainer()
	{
		return this.container.querySelector(this.isCheckList() ? '.tasks-checklist-header-name' : '.tasks-checklist-item-inner');
	}

	getContainer()
	{
		return this.container;
	}

	move(item, position = 'bottom', action = 'move')
	{
		if (
			this.getNodeId() === item.getNodeId()
			|| this.findChild(item.getNodeId()) !== null
		)
		{
			return;
		}

		const oldParent = this.getParent();
		const newParent = item.getParent();

		oldParent.remove(this);

		if (position === 'top')
		{
			newParent.addBefore(this, item);
		}
		else
		{
			newParent.addAfter(this, item);
		}

		CheckListItem.updateParents(oldParent, newParent);

		if (position === 'top')
		{
			Dom.insertBefore(this.container, item.container);
		}
		else
		{
			Dom.insertAfter(this.container, item.container);
		}

		this.handleCheckListIsEmpty();
		this.handleTaskOptions();

		EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action});
	}

	makeChildOf(item, position = 'bottom', action = 'makeChildOf')
	{
		if (item.getDescendantsCount() > 0)
		{
			const borderItems = {
				top: item.getFirstDescendant(),
				bottom: item.getLastDescendant(),
			};

			this.move(borderItems[position], position, action);
		}
		else
		{
			const oldParent = this.getParent();
			const newParent = item;

			oldParent.remove(this);
			newParent.add(this);

			CheckListItem.updateParents(oldParent, newParent);

			Dom.append(this.container, newParent.getSubItemsContainer());
			Dom.addClass(this.container, 'mobile-task-checklist-item-wrapper-animate');

			this.handleCheckListIsEmpty();
			this.handleTaskOptions();

			EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action});
		}
	}

	tabIn()
	{
		if (!this.isFirstDescendant())
		{
			this.makeChildOf(this.getLeftSibling(), 'bottom', 'tabIn');
		}
	}

	onTabInClick()
	{
		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				selectedItem.tabIn();
			});

			return;
		}

		this.tabIn();
		this.retrieveFocus();
	}

	tabOut()
	{
		const parent = this.getParent();

		if (parent.isCheckList())
		{
			return;
		}

		this.move(parent, 'bottom', 'tabOut');
	}

	onTabOutClick()
	{
		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				selectedItem.tabOut();
			}, true);

			return;
		}

		this.tabOut();
		this.retrieveFocus();
	}

	addCheckListItem(item = null, dependsOn = null, position = 'after')
	{
		const itemGet = item instanceof this.class;

		return new Promise((resolve) => {
			const newCheckListItem = item || new this.class();
			let newCheckListItemLayout;

			if (dependsOn instanceof this.class)
			{
				if (position === 'before')
				{
					this.addBefore(newCheckListItem, dependsOn);

					newCheckListItemLayout = newCheckListItem.getLayout();
					this.setDefaultStyles(newCheckListItemLayout);

					Dom.insertBefore(newCheckListItemLayout, dependsOn.container);
				}
				else if (position === 'after')
				{
					this.addAfter(newCheckListItem, dependsOn);

					newCheckListItemLayout = newCheckListItem.getLayout();
					this.setDefaultStyles(newCheckListItemLayout);

					Dom.insertAfter(newCheckListItemLayout, dependsOn.container);
				}
			}
			else
			{
				this.add(newCheckListItem);

				newCheckListItemLayout = newCheckListItem.getLayout();
				this.setDefaultStyles(newCheckListItemLayout);

				Dom.append(newCheckListItemLayout, this.getSubItemsContainer());
			}

			this.updateCounts();
			this.updateIndexes();

			if (!this.isTaskRoot())
			{
				this.updateProgress();
				this.handleCheckListIsEmpty();

				if (!itemGet)
				{
					newCheckListItem.toggleUpdateMode();
				}
			}

			setTimeout(() => {
				newCheckListItemLayout.style.height = `${newCheckListItemLayout.scrollHeight}px`;
				newCheckListItemLayout.style.opacity = 1;
			}, 1);

			setTimeout(() => {
				newCheckListItemLayout.style.overflow = '';
				newCheckListItemLayout.style.height = '';
				newCheckListItemLayout.style.opacity = '';

				Dom.removeClass(newCheckListItemLayout, this.showClass);

				if (!this.isTaskRoot() && !itemGet && newCheckListItem.input !== null)
				{
					this.class.smoothScroll(newCheckListItem.getContainer());
				}

				resolve(newCheckListItem);
			}, 250);
		});
	}

	onAddCheckListItemClick()
	{
		if (this.getRootNode().checkActiveUpdateExist())
		{
			return;
		}

		this.addCheckListItem();
	}

	getItemRequestData()
	{
		const itemRequestData = {
			NODE_ID: this.getNodeId(),
			PARENT_NODE_ID: this.getParent().getNodeId(),
			ID: this.fields.getId(),
			COPIED_ID: this.fields.getCopiedId(),
			PARENT_ID: this.fields.getParentId(),
			TITLE: Text.decode(this.fields.getTitle()),
			SORT_INDEX: this.fields.getSortIndex(),
			IS_COMPLETE: this.fields.getIsComplete(),
			IS_IMPORTANT: this.fields.getIsImportant(),
			MEMBERS: [],
			ATTACHMENTS: {},
		};

		this.fields.getMembers().forEach((value, key) => {
			const {nameFormatted, type} = value;
			itemRequestData.MEMBERS.push({
				[key]: {
					TYPE: type,
					NAME: Text.decode(nameFormatted),
				},
			});
		});

		const attachments = this.fields.getAttachments();
		Object.keys(attachments).forEach((id) => {
			itemRequestData.ATTACHMENTS[id] = attachments[id];
		});

		return itemRequestData;
	}

	getRequestData(inputData = null)
	{
		const title = this.fields.getTitle();
		let data = inputData || [];

		if (!this.isTaskRoot() && title !== '' && title.length > 0)
		{
			data.push(this.getItemRequestData());
		}

		this.getDescendants().forEach((descendant) => {
			data = descendant.getRequestData(data);
		});

		return data;
	}

	appendRequestLayout()
	{
		if (!this.isTaskRoot())
		{
			const nodeId = this.getNodeId();
			const attachments = this.fields.getAttachments();
			const prefix = `${this.optionManager.prefix}[${nodeId}]`;

			let membersLayout = '';
			let attachmentsLayout = '';

			this.fields.getMembers().forEach((value, key) => {
				membersLayout += `<input type="hidden" id="MEMBERS_TYPE_${key}" name="${prefix}[MEMBERS][${key}][TYPE]" value="${value.type}"/>`;
				membersLayout += `<input type="hidden" id="MEMBERS_NAME_${key}" name="${prefix}[MEMBERS][${key}][NAME]" value="${value.nameFormatted}"/>`;
			});

			Object.keys(attachments).forEach((id) => {
				attachmentsLayout += `<input type="hidden" id="ATTACHMENTS_${id}" name="${prefix}[ATTACHMENTS][${id}]" value="${attachments[id]}"/>`;
			});

			const requestLayout = Tag.render`
				<div id="request_${nodeId}">
					<input type="hidden" id="NODE_ID" name="${prefix}[NODE_ID]" value="${nodeId}"/>
					<input type="hidden" id="PARENT_NODE_ID" name="${prefix}[PARENT_NODE_ID]" value="${this.getParent().getNodeId()}"/>
					<input type="hidden" id="ID" name="${prefix}[ID]" value="${this.fields.getId()}"/>
					<input type="hidden" id="COPIED_ID" name="${prefix}[COPIED_ID]" value="${this.fields.getCopiedId()}"/>
					<input type="hidden" id="PARENT_ID" name="${prefix}[PARENT_ID]" value="${this.fields.getParentId()}"/>
					<input type="hidden" id="TITLE" name="${prefix}[TITLE]" value="${this.fields.getTitle()}"/>
					<input type="hidden" id="SORT_INDEX" name="${prefix}[SORT_INDEX]" value="${this.fields.getSortIndex()}"/>
					<input type="hidden" id="IS_COMPLETE" name="${prefix}[IS_COMPLETE]" value="${this.fields.getIsComplete()}"/>
					<input type="hidden" id="IS_IMPORTANT" name="${prefix}[IS_IMPORTANT]" value="${this.fields.getIsImportant()}"/>
					<input type="hidden" id="MODIFY" name="${prefix}[ACTION][MODIFY]" value="${this.checkCanUpdate()}"/>
					<input type="hidden" id="REMOVE" name="${prefix}[ACTION][REMOVE]" value="${this.checkCanRemove()}"/>
					<input type="hidden" id="TOGGLE" name="${prefix}[ACTION][TOGGLE]" value="${this.checkCanToggle()}"/>
					${membersLayout}
					${attachmentsLayout}
				</div>
			`;

			Dom.remove(this.container.querySelector(`#request_${nodeId}`));
			Dom.append(requestLayout, this.container);
		}

		this.getDescendants().forEach((descendant) => {
			descendant.appendRequestLayout();
		});
	}

	getAttachmentsLayout()
	{
		const searchId = this.fields.getId() || this.fields.getCopiedId();
		const {optionManager} = this;
		const optionAttachments = optionManager.attachments;
		let attachmentsLayout = '';

		if (optionAttachments && (searchId in optionAttachments))
		{
			const attachments = Tag.render`${optionAttachments[searchId]}`;
			const stableAttachments = this.getStableAttachments(optionManager.getStableTreeStructure());
			const attachmentsToDelete = [];

			if (!attachments)
			{
				return attachmentsLayout;
			}

			attachmentsLayout = attachments;

			if (!Array.isArray(attachments))
			{
				attachmentsLayout = [attachments];
			}

			Object.keys(attachmentsLayout).forEach((key) => {
				const attachment = attachmentsLayout[key];
				const fileId = attachment.getAttribute('data-bx-id');
				const extension = CheckListItem.getFileExtension(attachment.getAttribute('data-bx-extension'));
				const extensionClass = `ui-icon-file-${extension}`;
				const iconContainer = attachment.querySelector(`#disk-attach-file-${fileId}`);
				const deleteButton = Tag.render`
					<div class="tasks-checklist-item-attachment-file-remove"
						 onclick="${this.onDeleteAttachmentClick.bind(this, fileId)}"></div>
				`;
				const has = Object.prototype.hasOwnProperty;

				if (!has.call(stableAttachments, fileId))
				{
					attachmentsToDelete.push(key);
					return;
				}

				if (iconContainer && !Dom.hasClass(iconContainer, extensionClass))
				{
					Dom.addClass(iconContainer, extensionClass);
				}

				if (this.checkCanUpdate())
				{
					Dom.append(deleteButton, attachment.querySelector('.tasks-checklist-item-attachment-file-cover'));
				}
			});

			attachmentsToDelete.sort((a, b) => b - a);
			attachmentsToDelete.forEach((id) => {
				attachmentsLayout.splice(id, 1);
			});
		}
		else
		{
			this.fields.setAttachments({});
		}

		return attachmentsLayout;
	}

	getStableAttachments(item)
	{
		const fields = item.FIELDS;
		const id = fields.id || fields.copiedId;

		if (id === this.fields.getId() || id === this.fields.getCopiedId())
		{
			return fields.attachments;
		}

		let found = null;
		item.DESCENDANTS.forEach((descendant) => {
			if (found === null)
			{
				found = this.getStableAttachments(descendant);
			}
		});

		return found;
	}

	getLoadedAttachmentLayout(attachment)
	{
		const {id, name, viewUrl, size, ext} = attachment;
		let img = '';

		if (viewUrl)
		{
			img = Tag.render`
				<div class="tasks-checklist-item-attachment-file-cover" style="background-image: url(${viewUrl})">
					<div class="tasks-checklist-item-attachment-file-remove" onclick="${this.onDeleteAttachmentClick.bind(this, id)}"></div>
				</div>
			`;
		}
		else
		{
			const extension = CheckListItem.getFileExtension(ext);

			img = Tag.render`
				<div class="tasks-checklist-item-attachment-file-cover">
					<div class="ui-icon ui-icon-file-${extension}"><i></i></div>
					<div class="tasks-checklist-item-attachment-file-remove" onclick="${this.onDeleteAttachmentClick.bind(this, id)}"></div>
				</div>
			`;
		}

		return Tag.render`
			<div class="tasks-checklist-item-attachment-file" id="disk-attach-${id}" data-bx-id="${id}">
				${img}
				<div class="tasks-checklist-item-attachment-file-name">
					<label class="tasks-checklist-item-attachment-file-name-text" title="${name}">${name}</label>
				</div>
				<div class="tasks-checklist-item-attachment-file-size">
					<label class="tasks-checklist-item-attachment-file-size-text">${size}</label>
				</div>
			</div>
		`;
	}

	onAttachmentsLoaderMenuItemClick()
	{
		if (this.filesLoaderPopup !== null)
		{
			this.filesLoaderPopup.close();
		}
	}

	getAttachmentsLoaderLayout()
	{
		const nodeId = this.getNodeId();
		const {prefix} = this.optionManager;
		const filesChooser = Tag.render`
			<div id="files_chooser">
				<div id="diskuf-selectdialog-${nodeId}" class="diskuf-files-entity diskuf-selectdialog bx-disk">
					<div class="diskuf-files-block tasks-checklist-loader-files">
						<div class="diskuf-placeholder">
							<table class="files-list">
								<tbody class="diskuf-placeholder-tbody"></tbody>
							</table>
						</div>
					</div>
					<div class="diskuf-extended" style="display: block">
						<input type="hidden" name="${prefix}[${nodeId}][UF_CHECKLIST_FILES][]" value=""/>
						<div class="diskuf-extended-item">
							<label for="file_loader_${nodeId}" onclick="${this.onAttachmentsLoaderMenuItemClick.bind(this)}">
								${Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_COMPUTER')}
							</label>
							<input class="diskuf-fileUploader" id="file_loader_${nodeId}" type="file"
								   multiple="multiple" size="1" style="display: none"/>
						</div>
						<div class="diskuf-extended-item" onclick="${this.onAttachmentsLoaderMenuItemClick.bind(this)}">
							<span class="diskuf-selector-link">
								${Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_B24')}
							</span>
						</div>
						<div class="diskuf-extended-item" onclick="${this.onAttachmentsLoaderMenuItemClick.bind(this)}">
							<span class="diskuf-selector-link-cloud" data-bx-doc-handler="gdrive">
								<span>${Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_CLOUD')}</span>
							</span>
						</div>
					</div>
				</div>
			</div>
		`;

		BX.addCustomEvent(filesChooser, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
		BX.addCustomEvent(filesChooser, 'DiskDLoadFormControllerInit', (uf) => {
			uf._onUploadProgress = this.onUploadProgress.bind(this);
		});

		return filesChooser;
	}

	onUploadProgress(item, progress)
	{
		const {id, name, size} = item;
		const newProgress = Math.min(progress, 98);

		if (!this.filesLoaderProgressBars.has(id))
		{
			const myProgress = new BX.UI.ProgressRound({
				id: `load_progress_${id}`,
				value: newProgress,
				maxValue: 100,
				width: 69,
				lineSize: 5,
				fill: false,
				color: BX.UI.ProgressRound.Color.PRIMARY,
				statusType: BX.UI.ProgressRound.Status.INCIRCLE,
			});
			const filePreview = Tag.render`
				<div class="tasks-checklist-item-attachment-file" id="disk-attach-${id}">
					${myProgress.getContainer()}
					<div class="tasks-checklist-item-attachment-file-name">
						<label class="tasks-checklist-item-attachment-file-name-text" title="${name}">${name}</label>
					</div>
					<div class="tasks-checklist-item-attachment-file-size">
						<label class="tasks-checklist-item-attachment-file-size-text">${size}</label>
					</div>
				</div>
			`;

			this.filesLoaderProgressBars.set(id, myProgress);
			Dom.append(filePreview, this.getAttachmentsContainer());
		}

		if (!item.progressBarWidth)
		{
			item.progressBarWidth = 5;
		}

		if (newProgress > item.progressBarWidth)
		{
			item.progressBarWidth = Math.ceil(newProgress);
			item.progressBarWidth = item.progressBarWidth > 100 ? 100 : item.progressBarWidth;

			if (this.filesLoaderProgressBars.has(id))
			{
				this.filesLoaderProgressBars.get(id).update(item.progressBarWidth);
			}
		}
	}

	OnFileUploadSuccess(fileResult, uf, file, uploaderFile)
	{
		if (typeof file === 'undefined' || typeof uploaderFile === 'undefined')
		{
			return;
		}

		const attachmentId = fileResult.element_id.toString();
		const attachment = {
			id: attachmentId,
			name: fileResult.element_name,
			viewUrl: fileResult.element_url,
			size: uploaderFile.size,
			ext: uploaderFile.ext,
		};

		this.fields.addAttachments({[attachmentId]: attachmentId});
		this.filesLoaderProgressBars.delete(uploaderFile.id);

		const attachmentProgress = this.getAttachmentsContainer().querySelector(`#disk-attach-${uploaderFile.id}`);
		const attachmentLayout = this.getLoadedAttachmentLayout(attachment);

		if (attachmentProgress)
		{
			Dom.replace(attachmentProgress, attachmentLayout);
		}
		else
		{
			Dom.append(attachmentLayout, this.getAttachmentsContainer());
		}

		const id = this.fields.getId();
		const optionAttachments = this.optionManager.attachments;

		if (optionAttachments)
		{
			if (id in optionAttachments)
			{
				this.optionManager.attachments[id] += attachmentLayout.outerHTML;
			}
			else
			{
				this.optionManager.attachments[id] = attachmentLayout.outerHTML;
			}
		}

		EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged', {action: 'fileUpload'});
	}

	getTaskRootLayout(children)
	{
		this.container = Tag.render`
			<div class="tasks-checklist-task-root">
				<div id="subItems_${this.getNodeId()}">
					${children}
				</div>
			</div>
		`;

		return this.container;
	}

	getCheckListLayout(children)
	{
		const nodeId = this.getNodeId();
		const value = this.fields.getCompletedCount();
		const maxValue = this.fields.getTotalCount();
		const layouts = {
			listActionsPanel: Tag.render`<div class="tasks-checklist-items-list-actions droppable"></div>`,
			completeAllButton: Tag.render`
				<div class="tasks-checklist-action-complete-all-btn" onclick="${this.onCompleteAllButtonClick.bind(this)}">
					${Loc.getMessage('TASKS_CHECKLIST_COMPLETE_ALL')}
				</div>
			`,
			groupButton: '',
			dndButton: Tag.render`<div class="tasks-checklist-wrapper-dragndrop"></div>`,
		};

		if (this.checkCanAdd())
		{
			const addButtonLayout = Tag.render`
				<a class="tasks-checklist-item-add-btn" onclick="${this.onAddCheckListItemClick.bind(this)}">
					${Loc.getMessage('TASKS_CHECKLIST_ADD_NEW_ITEM')}
				</a>
			`;
			const groupButton = Tag.render`
				<div class="tasks-checklist-action-group-btn" onclick="${this.onGroupButtonClick.bind(this)}">
					${Loc.getMessage('TASKS_CHECKLIST_GROUP_ACTIONS')}
				</div>
			`;

			Dom.append(addButtonLayout, layouts.listActionsPanel);
			layouts.groupButton = groupButton;
		}

		if (this.checkCanRemove())
		{
			const removeButtonLayout = Tag.render`
				<a class="tasks-checklist-item-remove-btn" onclick="${this.onDeleteClick.bind(this)}">
					${Loc.getMessage('TASKS_CHECKLIST_DELETE_CHECKLIST')}
				</a>
			`;
			Dom.append(removeButtonLayout, layouts.listActionsPanel);
		}

		if (!this.checkCanDrag())
		{
			layouts.dndButton.style.visibility = 'hidden';
		}

		if (!this.showCompleteAllButton())
		{
			layouts.completeAllButton = '';
		}

		this.progress = new BX.UI.ProgressBar({
			value,
			maxValue,
			size: BX.UI.ProgressBar.Size.MEDIUM,
			textAfter: CheckListItem.getProgressText(value, maxValue),
		});

		this.container = Tag.render`
			<div class="tasks-checklist-wrapper tasks-checklist-item-group-editor-collapse" id="${nodeId}">
				<div class="tasks-checklist-header-wrapper droppable">
					${layouts.dndButton}
					<div class="tasks-checklist-header-block">
						<div class="tasks-checklist-header-inner">
							<div class="tasks-checklist-header-name"
								 onmousedown="${this.onHeaderMouseDown.bind(this)}"
								 onmouseup="${this.onHeaderMouseUp.bind(this)}">
								${this.getTitleLayout()}
								<div class="tasks-checklist-header-name-edit-btn"></div>
							</div>
							<div class="tasks-checklist-header-progress-block">
								<div class="tasks-checklist-header-progress" id="progress_${nodeId}">
									${this.progress.getContainer()}
								</div>
							</div>
						</div>
					</div>
					<div class="tasks-checklist-header-actions">
						${layouts.completeAllButton}
						${layouts.groupButton}
						<div class="tasks-checklist-action-collapse-btn collapsed" onclick="${this.onCollapseButtonClick.bind(this)}"></div>
					</div>
				</div>
				<div class="tasks-checklist-items-wrapper">
					<div class="tasks-checklist-items-list" id="subItems_${nodeId}">
						${children}
					</div>
					${layouts.listActionsPanel}
				</div>
			</div>
		`;

		return this.container;
	}

	getCheckListItemLayout(children)
	{
		const nodeId = this.getNodeId();
		const layouts = {
			deleteButton: Tag.render`<button class="tasks-checklist-item-remove" onclick="${this.onDeleteClick.bind(this)}"></button>`,
			dndButton: Tag.render`<div class="tasks-checklist-item-dragndrop"></div>`,
			attachments: this.getAttachmentsLayout(),
		};

		if (!this.checkCanRemove())
		{
			layouts.deleteButton = '';
		}

		if (!this.checkCanDrag())
		{
			layouts.dndButton.style.visibility = 'hidden';
		}

		this.progress = new BX.UI.ProgressRound({
			id: `progress_${nodeId}`,
			value: this.fields.getCompletedCount(),
			maxValue: this.fields.getTotalCount(),
			width: 20,
			lineSize: 3,
			fill: false,
			color: BX.UI.ProgressRound.Color.PRIMARY,
		});

		this.container = Tag.render`
			<div class="tasks-checklist-item" id="${nodeId}">
				<div class="tasks-checklist-item-inner droppable ${this.fields.getIsComplete() ? 'tasks-checklist-item-solved' : ''}"
					 onmousedown="${this.onInnerContainerMouseDown.bind(this)}" onmouseup="${this.onInnerContainerMouseUp.bind(this)}">
					${layouts.dndButton}
					<div class="tasks-checklist-item-flag-block">
						<div class="tasks-checklist-item-flag">
							<label class="tasks-checklist-item-flag-element" onclick="${this.onCompleteButtonClick.bind(this)}">
								<span class="tasks-checklist-item-flag-sub-checklist-progress" id="progress_${nodeId}">
									${this.progress.getContainer()}
								</span>
								<span class="tasks-checklist-item-flag-element-decorate"></span>
							</label>
						</div>
					</div>
					<div class="tasks-checklist-item-content-block">
						<div class="tasks-checklist-item-number">${this.fields.getDisplaySortIndex()}</div>
						${this.fields.getIsImportant() ? this.getImportantLayout() : ''}
						<div class="tasks-checklist-item-description">
							${this.getTitleLayout()}
						</div>
					</div>
					<div class="tasks-checklist-item-additional-block">
						${layouts.deleteButton}
					</div>
					<div class="tasks-checklist-item-actions-block">
						<input class="tasks-checklist-item-group-checkbox" id="select_${nodeId}" type="checkbox"
							   onclick="${this.onSelectCheckboxClick.bind(this)}"/>
					</div>
				</div>
				<div class="tasks-checklist-item-attachment">
					<div class="tasks-checklist-item-attachment-list" id="attachments_${nodeId}">
						${layouts.attachments}
					</div>
				</div>
				<div class="tasks-checklist-sublist-items-wrapper" id="subItems_${nodeId}">
					${children}
				</div>
			</div>
		`;

		return this.container;
	}

	getLayout()
	{
		const children = [];

		this.descendants.forEach((descendant) => {
			children.push(descendant.getLayout());
		});

		if (this.isTaskRoot())
		{
			return this.getTaskRootLayout(children);
		}

		if (this.isCheckList())
		{
			const checkListLayout = this.getCheckListLayout(children);

			this.handleCheckListChanges();

			return checkListLayout;
		}

		return this.getCheckListItemLayout(children);
	}
}

class MobileCheckListItem extends CheckListItem
{
	class = MobileCheckListItem;

	checkedClass = 'mobile-task-checklist-item-checked';
	hiddenClass = 'mobile-task-checklist-item-hidden';
	collapseClass = 'mobile-task-checklist-section-collapse';
	wrapperClass = 'mobile-task-checklist-wrapper';

	showClass = 'mobile-checklist-item-show';
	hideClass = 'mobile-checklist-item-hide';

	skipUpdateClasses = {
		header: [
			'.tasks-checklist-item-auditor',
			'.tasks-checklist-item-accomplice',
			'.tasks-checklist-item-link',
		],
		item: [
			'.tasks-checklist-item-auditor',
			'.tasks-checklist-item-accomplice',
			'.tasks-checklist-item-link',
			'.mobile-task-checklist-item-checker',
			'.mobile-task-checklist-item-param',
			'.mobile-task-checklist-item-controls',
		],
	};

	static addDangerToElement(element)
	{
		const dangerClass = 'mobile-task-checklist-error';

		if (!Dom.hasClass(element, dangerClass))
		{
			Dom.addClass(element, dangerClass);
		}
	}

	getItemRequestData()
	{
		const itemRequestData = {
			PARENT_ID: this.fields.getParentId(),
			TITLE: Text.decode(this.fields.getTitle()),
			IS_COMPLETE: this.fields.getIsComplete(),
			IS_IMPORTANT: this.fields.getIsImportant(),
			MEMBERS: {},
			ATTACHMENTS: this.fields.getAttachments() || {},
		};
		const membersTypes = {
			accomplice: 'A',
			auditor: 'U',
		};

		this.fields.getMembers().forEach((value, key) => {
			itemRequestData.MEMBERS[key] = {TYPE: membersTypes[value.type]};
		});

		return itemRequestData;
	}

	onChecklistAjaxError()
	{
		BXMobileApp.Events.postToComponent('onChecklistAjaxError', {
			taskId: this.optionManager.entityId,
			taskGuid: this.optionManager.taskGuid,
		}, 'tasks.view');
	}

	sendAddAjaxAction()
	{
		return new Promise((resolve, reject) => {
			const fields = this.getItemRequestData();
			const parent = this.getParent();

			fields.PARENT_ID = parent.fields.getId() || (parent.isTaskRoot() ? 0 : null);


			BX.ajax.runAction('tasks.task.checklist.add', {
				data: {
					taskId: this.optionManager.entityId,
					fields,
				},
			}).then((response) => {
				if (response.status === 'success')
				{
					const {checkListItem} = response.data;
					this.fields.setId(checkListItem.id);
					resolve();
				}
				else
				{
					this.onChecklistAjaxError();
					reject();
				}
			}).catch(() => {
				this.onChecklistAjaxError();
				reject();
			});
		});
	}

	sendUpdateAjaxAction(fields, onFailCallback = null)
	{
		const onFailFunction = onFailCallback || this.onChecklistAjaxError.bind(this);

		BX.ajax.runAction('tasks.task.checklist.update', {
			data: {
				taskId: this.optionManager.entityId,
				checkListItemId: this.fields.getId(),
				fields,
			},
		}).then((response) => {
			if (response.status !== 'success')
			{
				onFailFunction();
			}
		}).catch(() => onFailFunction());
	}

	sendRemoveAjaxAction()
	{
		BX.ajax.runAction('tasks.task.checklist.delete', {
			data: {
				taskId: this.optionManager.entityId,
				checkListItemId: this.fields.getId(),
			},
		}).then((response) => {
			if (response.status !== 'success')
			{
				this.onChecklistAjaxError();
			}
		}).catch(() => this.onChecklistAjaxError());
	}

	sendMembersAddAjaxAction(member, focusInput)
	{
		const map = {
			auditor: {
				actionName: 'addAuditors',
				paramName: 'auditorsIds',
				letter: 'U',
			},
			accomplice: {
				actionName: 'addAccomplices',
				paramName: 'accomplicesIds',
				letter: 'A',
			},
		};
		const currentType = map[member.type];

		const toChecklistAddAction = 'tasks.task.checklist.addMembers';
		const toChecklistAddData = {
			taskId: this.optionManager.entityId,
			checkListItemId: this.fields.getId(),
			members: {[member.id]: currentType.letter},
		};

		BX.ajax.runAction(toChecklistAddAction, {data: toChecklistAddData}).then((toChecklistAddResponse) => {
			if (toChecklistAddResponse.status === 'success')
			{
				if (focusInput)
				{
					this.toggleUpdateMode();
				}

				BX.ajax.runAction(`tasks.task.${currentType.actionName}`, {data: {
					taskId: this.optionManager.entityId,
					[currentType.paramName]: [member.id],
				}});
			}
			else
			{
				this.fields.removeMember(member.id);
			}
		}).catch(() => this.fields.removeMember(member.id));
	}

	sendMoveAfterAjaxAction(afterItem)
	{
		BX.ajax.runAction('tasks.task.checklist.moveAfter', {
			data: {
				taskId: this.optionManager.entityId,
				checkListItemId: this.fields.getId(),
				afterItemId: afterItem.fields.getId(),
			},
		}).then((response) => {
			if (response.status !== 'success')
			{
				this.onChecklistAjaxError();
			}
		}).catch(() => this.onChecklistAjaxError());
	}

	getPopupMenuItems()
	{
		const locPrefix = 'TASKS_CHECKLIST_MOBILE_POPUP_MENU_';
		const popupMenuItems = [];
		const popupMenuItemsBuildMap = {
			checklist: {
				addAuditor: {
					condition: this.checkCanAddAccomplice.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}ADD_AUDITOR`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-add-auditor.png',
				},
				addAccomplice: {
					condition: this.checkCanAddAccomplice.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}ADD_ACCOMPLICE`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-add-accomplice.png',
				},
				rename: {
					condition: this.checkCanUpdate.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}RENAME`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-rename.png',
				},
				remove: {
					condition: this.checkCanRemove.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}REMOVE`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-remove.png',
				},
			},
			checklistItem: {
				addFile: {
					condition: this.checkCanUpdate.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}ADD_FILE`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-add-file.png',
				},
				addAuditor: {
					condition: this.checkCanAddAccomplice.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}ADD_AUDITOR`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-add-auditor.png',
				},
				addAccomplice: {
					condition: this.checkCanAddAccomplice.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}ADD_ACCOMPLICE`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-add-accomplice.png',
				},
				tabIn: {
					condition: this.checkCanTabIn.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}TAB_IN`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-tab-in.png',
				},
				tabOut: {
					condition: this.checkCanTabOut.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}TAB_OUT`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-tab-out.png',
				},
				important: {
					condition: this.checkCanUpdate.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}IMPORTANT`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-important.png',
				},
				toAnotherChecklist: {
					condition: this.checkCanUpdate.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}TO_ANOTHER_CHECKLIST`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-to-another-checklist.png',
				},
				remove: {
					condition: this.checkCanRemove.bind(this),
					sectionCode: '0',
					title: Loc.getMessage(`${locPrefix}REMOVE`),
					iconUrl: '/bitrix/js/tasks/checklist/images/mobile-checklist-remove.png',
				},
			},
		};
		const type = (this.isCheckList() ? 'checklist' : 'checklistItem');

		Object.keys(popupMenuItemsBuildMap[type]).forEach((id) => {
			const {sectionCode, title, iconUrl, condition} = popupMenuItemsBuildMap[type][id];
			popupMenuItems.push({id, sectionCode, title, iconUrl, disable: !condition()});
		});

		return popupMenuItems;
	}

	getPopupChecklistsList()
	{
		const checkList = this.getCheckList();
		const checkLists = this.getRootNode().getDescendants().filter(item => item !== checkList);
		const popupChecklistsList = [];

		checkLists.forEach((descendant) => {
			popupChecklistsList.push({
				id: descendant.getNodeId(),
				title: Text.decode(descendant.fields.getTitle()),
				sectionCode: '0',
			});
		});
		popupChecklistsList.push({
			id: 'newChecklist',
			title: Tag.message`+ ${'TASKS_CHECKLIST_PANEL_TO_ANOTHER_CHECKLIST_POPUP_NEW_CHECKLIST'}`,
			sectionCode: '0',
		});

		return popupChecklistsList;
	}

	onMemberSelectedEvent(eventData)
	{
		const {nodeId, member, position, focusInput} = eventData;
		const node = this.findChild(nodeId);

		if (!node)
		{
			return;
		}

		const title = node.fields.getTitle();
		let newTitle = '';

		member.nameFormatted = Text.encode(member.nameFormatted);

		if (focusInput)
		{
			const start = position || 0;
			const startSpace = ((start === 0 || start - 1 === 0 || title.charAt(start - 2) === ' ') ? '' : ' ');
			const endSpace = (title.charAt(start - 1) === ' ' ? '' : ' ');
			const newInputText = `${title.slice(0, start - 1)}${startSpace}${member.nameFormatted}${endSpace}`;
			newTitle = `${newInputText}${title.slice(start)}`;
		}
		else
		{
			const space = (title.slice(-1) === ' ' ? '' : ' ');
			newTitle = `${title}${space}${member.nameFormatted}`;
		}

		node.fields.addMember(member);
		node.updateTitle(Text.decode(newTitle));
		node.updateTitleNode();

		if (!this.checkEditMode())
		{
			node.sendUpdateAjaxAction({TITLE: Text.decode(node.fields.getTitle())});
			node.sendMembersAddAjaxAction(member, focusInput);
		}
		else if (focusInput)
		{
			node.toggleUpdateMode();
		}
	}

	onAddAttachmentEvent(eventData)
	{
		const {nodeId, attachment} = eventData;
		const node = this.findChild(nodeId);

		if (node)
		{
			const key = Object.keys(attachment)[0];
			const value = Object.values(attachment)[0];

			node.fields.addAttachments({[`n${key}`]: value});
		}
	}

	onRemoveAttachmentEvent(eventData)
	{
		const {nodeId, attachmentId} = eventData;
		const node = this.findChild(nodeId);

		if (node)
		{
			node.fields.removeAttachment(attachmentId);
			node.fields.removeAttachment(`n${attachmentId}`);
		}
	}

	getFakeAttachmentsCount(filesToRemove, filesToAdd)
	{
		const attachmentsIds = Object.keys(this.fields.getAttachments());
		const countWithRemovable = attachmentsIds.filter(id => !filesToRemove.includes(id)).length;

		return countWithRemovable + filesToAdd.length;
	}

	setLayoutAttachmentsCount(attachmentsCount)
	{
		const newAttachmentsLayout = Tag.render`
			<div class="mobile-task-checklist-item-param" id="attachments_${this.getNodeId()}" onclick="${this.onAttachmentsLayoutClick.bind(this)}">
				${attachmentsCount > 0 ? `<div class="mobile-task-checklist-item-param-attach">${attachmentsCount}</div>` : ''}
			</div>
		`;
		Dom.replace(this.getAttachmentsContainer(), newAttachmentsLayout);
	}

	updateNodeAttachments(filesToRemove, filesToAdd, attachments = null)
	{
		if (attachments)
		{
			this.fields.setAttachments(attachments);
		}

		const fakeAttachmentsCount = this.getFakeAttachmentsCount(filesToRemove, filesToAdd);
		this.setLayoutAttachmentsCount(fakeAttachmentsCount);
	}

	onAttachFilesEvent(eventData)
	{
		const {nodeId, filesToRemove, filesToAdd, attachments, checkListItemId} = eventData;
		const node = (nodeId ? this.findChild(nodeId) : this.findById(checkListItemId));

		if (node)
		{
			node.updateNodeAttachments(filesToRemove, filesToAdd, attachments);
		}
	}

	onRemoveFilesEvent(eventData)
	{
		const {nodeId, filesToRemove, filesToAdd, attachments} = eventData;
		const node = this.findChild(nodeId);

		if (node)
		{
			node.updateNodeAttachments(filesToRemove, filesToAdd, attachments);
		}
	}

	onFakeAttachFilesEvent(eventData)
	{
		const {nodeId, filesToRemove, filesToAdd, checkListItemId} = eventData;
		const node = (nodeId ? this.findChild(nodeId) : this.findById(checkListItemId));

		if (node)
		{
			node.updateNodeAttachments(filesToRemove, filesToAdd);
		}
	}

	onFakeRemoveFilesEvent(eventData)
	{
		const {nodeId, filesToRemove, filesToAdd} = eventData;
		const node = this.findChild(nodeId);

		if (node)
		{
			node.updateNodeAttachments(filesToRemove, filesToAdd);
		}
	}

	onRenameEvent(eventData)
	{
		const node = this.findChild(eventData.nodeId);

		if (node)
		{
			node.toggleUpdateMode();
		}
	}

	onRemoveEvent(eventData)
	{
		const node = this.findChild(eventData.nodeId);

		if (node)
		{
			if (!this.checkEditMode() && node.fields.getId())
			{
				node.sendRemoveAjaxAction();
			}
			node.deleteAction(false);
			node.getParent().updateIndexes();
			node.handleCheckListChanges();
		}
	}

	onTabInEvent(eventData)
	{
		const node = this.findChild(eventData.nodeId);

		if (node && node.checkCanTabIn())
		{
			node.tabIn();

			if (!this.checkEditMode())
			{
				if (node.getLeftSibling())
				{
					node.sendMoveAfterAjaxAction(node.getLeftSibling());
				}
				else
				{
					const fields = {
						PARENT_ID: node.getParent().fields.getId(),
						SORT_INDEX: node.fields.getSortIndex(),
					};
					const onFailCallback = () => { node.tabOut(); };

					node.sendUpdateAjaxAction(fields, onFailCallback);
				}
			}
		}
	}

	onTabOutEvent(eventData)
	{
		const node = this.findChild(eventData.nodeId);

		if (node && node.checkCanTabOut())
		{
			if (!this.checkEditMode())
			{
				node.sendMoveAfterAjaxAction(node.getParent());
			}
			node.tabOut();
		}
	}

	onImportantEvent(eventData)
	{
		const node = this.findChild(eventData.nodeId);

		if (node)
		{
			node.toggleImportant();
			if (!this.checkEditMode())
			{
				const onFailCallback = () => { node.toggleImportant(); };
				node.sendUpdateAjaxAction({IS_IMPORTANT: node.fields.getIsImportant()}, onFailCallback);
			}
		}
	}

	onToAnotherCheckListEvent(eventData)
	{
		const {nodeId, checklistId} = eventData;
		const node = this.findChild(nodeId);

		if (!node)
		{
			return;
		}

		if (checklistId === 'newChecklist')
		{
			node.moveToNewCheckList(this.getDescendantsCount() + 1);
		}
		else
		{
			node.makeChildOf(this.findChild(checklistId));
			node.handleCheckListChanges();

			if (!this.checkEditMode())
			{
				if (node.getLeftSibling())
				{
					node.sendMoveAfterAjaxAction(node.getLeftSibling());
				}
				else
				{
					node.sendUpdateAjaxAction({
						PARENT_ID: node.getParent().fields.getId(),
						SORT_INDEX: node.fields.getSortIndex(),
					});
				}
			}
		}
	}

	getNativeComponentName()
	{
		return (this.checkEditMode() ? 'tasks.edit' : 'tasks.view');
	}

	checkEditMode()
	{
		return this.optionManager.isEditMode();
	}

	checkCanTabIn()
	{
		return !this.isTaskRoot() && !this.isCheckList() && !this.isFirstDescendant();
	}

	checkCanTabOut()
	{
		return !this.isTaskRoot() && !this.isCheckList() && !this.getParent().isCheckList();
	}

	showEditorPanel(item, nodeToPosition = null)
	{
		// no editor panel in mobile version
	}

	getTitleNodeClass()
	{
		return this.isCheckList() ? 'mobile-task-checklist-head-title-text' : 'mobile-task-checklist-item-text';
	}

	getInnerContainer()
	{
		return this.container.querySelector(this.isCheckList() ? '.mobile-task-checklist-head-title' : '.mobile-task-checklist-item');
	}

	moveToNewCheckList(number)
	{
		const title = `${Loc.getMessage('TASKS_CHECKLIST_NEW_CHECKLIST_TITLE')}`.replace('#ITEM_NUMBER#', number);
		const newCheckList = new MobileCheckListItem({TITLE: title});

		this.getRootNode().addCheckListItem(newCheckList).then(() => {
			this.makeChildOf(newCheckList);
			this.handleCheckListChanges();

			if (!this.checkEditMode())
			{
				newCheckList.sendAddAjaxAction().then(() => {
					this.sendUpdateAjaxAction({
						PARENT_ID: this.getParent().fields.getId(),
						SORT_INDEX: this.fields.getSortIndex(),
					});
				}, () => {});
			}
		});
	}

	toggleImportant()
	{
		if (this.fields.getIsImportant())
		{
			this.fields.setIsImportant(false);
			Dom.removeClass(this.getInnerContainer(), 'mobile-task-checklist-item-important');
		}
		else
		{
			this.fields.setIsImportant(true);
			Dom.addClass(this.getInnerContainer(), 'mobile-task-checklist-item-important');
		}
	}

	handleUpdateEnding(createNewItem = false)
	{
		const input = this.container.querySelector(`#text_${this.getNodeId()}`);
		const text = input.value.trim();

		if (text.length === 0)
		{
			if (this.checkCanDeleteOnUpdateEnding())
			{
				if (!this.checkEditMode() && this.fields.getId())
				{
					this.sendRemoveAjaxAction();
				}
				this.deleteAction(false);
				this.getParent().updateIndexes();
				this.handleCheckListIsEmpty();
			}
			else
			{
				MobileCheckListItem.addDangerToElement(input.parentElement);
			}
		}
		else if (createNewItem)
		{
			this.getParent().addCheckListItem(null, this);
		}
		else
		{
			this.disableUpdateMode();
			this.handleTaskOptions();

			if (!this.checkEditMode())
			{
				if (this.fields.getId())
				{
					const itemRequestData = this.getItemRequestData();
					const members = itemRequestData.MEMBERS;

					this.sendUpdateAjaxAction({
						TITLE: itemRequestData.TITLE,
						MEMBERS: (Object.keys(members).length > 0 ? members : ''),
					});
				}
				else
				{
					this.sendAddAjaxAction().then(() => {}, () => {});
				}
			}
		}
	}

	onInputBeforeInput(e)
	{
		const memberSelectorCallKeys = ['@', '+'];
		const position = CheckListItem.getInputSelection(this.input).start;

		if (memberSelectorCallKeys.includes(e.data) && this.checkCanAddAccomplice())
		{
			const params = {
				position,
				nodeId: this.getNodeId(),
			};
			BXMobileApp.Events.postToComponent(
				'onChecklistInputMemberSelectorCall',
				params,
				this.getNativeComponentName()
			);
			e.preventDefault();
		}
	}

	onInputKeyPressed(e)
	{
		if (e.keyCode === CheckListItem.keyCodes.enter)
		{
			this.toggleUpdateMode(e);
			e.preventDefault();
		}
	}

	getParamsForMobileEvent(type)
	{
		if (!['settings', 'attachments'].includes(type))
		{
			return {};
		}

		const {entityId, entityType, taskGuid, diskOptions, mode} = this.optionManager;
		const defaultParams = {
			taskGuid,
			taskId: entityId,
			nodeId: this.getNodeId(),
			disk: diskOptions,
			ajaxData: {
				entityId,
				mode,
				checkListItemId: (this.checkEditMode() ? this.getNodeId() : this.fields.getId()),
				entityTypeId: `${entityType.toLowerCase()}Id`,
			},
		};
		let localParams = {};

		if (type === 'settings')
		{
			localParams = {
				popupChecklists: this.getPopupChecklistsList(),
				popupMenuItems: this.getPopupMenuItems(),
				popupMenuSections: [{id: '0', title: Text.decode(this.fields.getTitle())}],
			};
		}
		else if (type === 'attachments')
		{
			localParams = {
				attachmentsIds: Object.keys(this.fields.getAttachments()),
				canUpdate: this.checkCanUpdate(),
			};
		}

		return {...defaultParams, ...localParams};
	}

	onAttachmentsLayoutClick()
	{
		const params = this.getParamsForMobileEvent('attachments');
		BXMobileApp.Events.postToComponent('onChecklistAttachmentsClick', params, this.getNativeComponentName());
	}

	onSettingsClick()
	{
		const params = this.getParamsForMobileEvent('settings');
		BXMobileApp.Events.postToComponent('onChecklistSettingsClick', params, this.getNativeComponentName());
	}

	getUpdateModeLayout()
	{
		const nodeId = this.getNodeId();

		if (this.isCheckList())
		{
			return Tag.render`
				<div class="mobile-task-checklist-head-title mobile-task-checklist-item-edit-mode">
					<input class="mobile-task-checklist-item-input" type="text" id="text_${nodeId}"
						   value="${this.fields.getTitle()}"
						   onkeypress="${this.onInputKeyPressed.bind(this)}"
						   onblur="${this.rememberInputState.bind(this)}"/>
				</div>
			`;
		}

		const progressBarLayout = new BX.Mobile.Tasks.CheckList.ProgressRound({
			value: this.fields.getCompletedCount(),
			maxValue: this.fields.getTotalCount(),
			width: 29,
			lineSize: 3,
			fill: false,
			color: BX.UI.ProgressRound.Color.PRIMARY,
		});

		return Tag.render`
			<div class="mobile-task-checklist-item mobile-task-checklist-item-edit-mode ${this.fields.getIsComplete() ? this.checkedClass : ''}">
				<div class="mobile-task-checklist-item-checker">
					${progressBarLayout.getContainer()}
				</div>
				<div class="mobile-task-checklist-item-title">
					<div class="tasks-checklist-item-number" style="display: none">${this.fields.getDisplaySortIndex()}</div>
					<input class="mobile-task-checklist-item-input" type="text" id="text_${nodeId}"
						   placeholder="${Loc.getMessage('TASKS_CHECKLIST_NEW_ITEM_PLACEHOLDER')}"
						   value="${this.fields.getTitle()}"
						   onkeypress="${this.onInputKeyPressed.bind(this)}"
						   onblur="${this.rememberInputState.bind(this)}"/>
				</div>
			</div>
		`;
	}

	getAttachmentsLayout()
	{
		const attachmentsCount = Object.keys(this.fields.getAttachments()).length;

		return Tag.render`
			<div class="mobile-task-checklist-item-param" id="attachments_${this.getNodeId()}" onclick="${this.onAttachmentsLayoutClick.bind(this)}">
				${attachmentsCount > 0 ? `<div class="mobile-task-checklist-item-param-attach">${attachmentsCount}</div>` : ''}
			</div>
		`;
	}

	getCheckListLayout(children)
	{
		const nodeId = this.getNodeId();
		const settingsLayout = Tag.render`
			<div class="mobile-task-checklist-setting" onclick="${this.onSettingsClick.bind(this)}"></div>
		`;
		const addButtonLayout = Tag.render`
			<div class="mobile-task-checklist-add-button" onclick="${this.onAddCheckListItemClick.bind(this)}">
				<div class="mobile-task-checklist-add-text">
					${Loc.getMessage('TASKS_CHECKLIST_ADD_NEW_ITEM')}
				</div>
			</div>
		`;

		this.progress = new BX.Mobile.Tasks.CheckList.ProgressRound({
			width: 29,
			lineSize: 3,
			value: this.fields.getCompletedCount(),
			maxValue: this.fields.getTotalCount(),
			statusType: BX.UI.ProgressRound.Status.COUNTER,
		});

		this.container = Tag.render`
			<div class="mobile-task-checklist-section" id="${nodeId}" data-role="mobile-task-checklist">
				<div class="mobile-task-checklist-head">
					<div class="mobile-task-checklist-counter">
						<div class="mobile-task-checklist-counter-progress" id="progress_${nodeId}">
							${this.progress.getContainer()}
						</div>
					</div>
					<div class="mobile-task-checklist-head-title" onclick="${this.onHeaderClickDone.bind(this)}">
						${this.getTitleLayout()}
					</div>
					<div class="mobile-task-checklist-controls">
						${this.checkCanUpdate() ? settingsLayout : ''}
						<div class="mobile-task-checklist-visible"
							 onclick="${this.onCollapseButtonClick.bind(this)}"></div>
					</div>
				</div>
				<div class="mobile-task-checklist-wrapper">
					<div class="mobile-task-checklist" id="subItems_${nodeId}">
						${children}
					</div>
					${this.checkCanAdd() ? addButtonLayout : ''}
				</div>
			</div>
		`;

		return this.container;
	}

	getCheckListItemLayout(children)
	{
		const nodeId = this.getNodeId();
		const settingsLayout = Tag.render`
			<div class="mobile-task-checklist-item-setting" onclick="${this.onSettingsClick.bind(this)}"></div>
		`;

		this.progress = new BX.Mobile.Tasks.CheckList.ProgressRound({
			id: `progress_${nodeId}`,
			value: this.fields.getCompletedCount(),
			maxValue: this.fields.getTotalCount(),
			width: 29,
			lineSize: 3,
			fill: false,
			color: BX.UI.ProgressRound.Color.PRIMARY,
		});

		this.container = Tag.render`
			<div class="mobile-task-checklist-item-wrapper" id="${nodeId}">
				<div class="mobile-task-checklist-item ${this.fields.getIsComplete() ? this.checkedClass : ''} ${this.fields.getIsImportant() ? 'mobile-task-checklist-item-important' : ''}"
					 onclick="${this.onInnerContainerClickDone.bind(this)}">
					<div class="mobile-task-checklist-item-checker" id="progress_${nodeId}"
						 onclick="${this.onCompleteButtonClick.bind(this)}">
						${this.progress.getContainer()}
					</div>
					<div class="mobile-task-checklist-item-title">
						<div class="tasks-checklist-item-number" style="display: none">${this.fields.getDisplaySortIndex()}</div>
						${this.getTitleLayout()}
					</div>
					${this.getAttachmentsLayout()}
					<div class="mobile-task-checklist-item-controls">
						${this.checkCanUpdate() ? settingsLayout : ''}
					</div>
				</div>
				<div class="mobile-task-checklist" id="subItems_${nodeId}">
					${children}
				</div>
			</div>
		`;

		return this.container;
	}
}

export {CheckListItem, MobileCheckListItem};