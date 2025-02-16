import { Designer, FileSelector, getGlobalContext, SelectorContext } from 'bizproc.automation';
import { ColorSelector, ColorSelectorEvents } from 'crm.field.color-selector';
import { ajax, Dom, Event, Loc, Reflection, Type, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { PopupMenu } from 'main.popup';
import { TagSelector } from 'ui.entity-selector';
import { CreateTodoActivityOptions } from './create-todo-activity-options';
import { Icon, Main } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmCreateTodoActivity
{
	#isRobot: boolean;
	#colorSelector;
	#colorId;
	#locationSelectorWrapper: HTMLElement;
	#additionalSettingsButton: HTMLElement;
	#documentFields: Object;
	#additionalSettingsWrapper: HTMLElement;
	#documentType: [];
	#additionalSettingsFields: {};
	#dataConfig: string;
	#fileSelector: FileSelector;
	#fileControl: Element;
	#diskControl: Element;
	#diskControlItems: Element;
	#attachmentType: ?string;

	constructor(options: CreateTodoActivityOptions)
	{
		this.#documentFields = options.documentFields;
		this.#isRobot = options.isRobot === true;
		this.#documentType = options.documentType;

		if (this.#isRobot)
		{
			this.#assertValidOptions(options);

			this.#additionalSettingsWrapper = options.additionalSettingsWrapper;
			this.#additionalSettingsButton = options.additionalSettingsButton;
			Event.bind(this.#additionalSettingsButton, 'click', this.#onAdditionalSettingsButtonClick.bind(this));

			this.#dataConfig = options.dataConfig;

			this.#colorSelector = this.#getColorSelector(
				options.colorSelectorWrapper,
				options.colorSettings,
				options.isAvailableColor,
			);
			this.#colorId = options.colorSettings.selectedValueId;
			EventEmitter.subscribe(
				this.#colorSelector,
				ColorSelectorEvents.EVENT_COLORSELECTOR_VALUE_CHANGE,
				this.#onColorChanged.bind(this),
			);

			this.#setOnBeforeSaveSettingsCallback();
			this.#additionalSettingsFields = {};
		}
		else
			if (options.attachmentType)
			{
				this.#fileControl = options.fileControl;
				this.#diskControl = options.diskControl;
				this.#diskControlItems = options.diskControlItems;
				this.#attachmentType = options.attachmentType.value;
				Event.bind(options.attachmentType, 'change', (event) => this.#onChangeAttachmentTypeHandler(event.target.value));
				Event.bind(options.showDiskFileDialogButton, 'click', this.#openDiskFileDialog.bind(this));
			}
	}

	#setOnBeforeSaveSettingsCallback(): void
	{
		if (!this.#isRobot)
		{
			return;
		}

		const dialog = Designer.getInstance()?.getRobotSettingsDialog();
		if (dialog?.robot)
		{
			dialog.robot.setOnBeforeSaveRobotSettings(this.#onBeforeSaveRobotSettings.bind(this));
		}
	}

	#onBeforeSaveRobotSettings(): Object
	{
		const data = {
			color_id: this.#colorId,
		};
		if (this.#additionalSettingsFields.LocationId)
		{
			data.location_id = this.locationSelectorDialog.getTags()[0]?.id;
		}

		return data;
	}

	#onColorChanged({ data }): void
	{
		this.#colorId = data.value;
	}

	#getLocationSelectorDialog(locationId: ?number): ?TagSelector
	{
		if (this.locations === null)
		{
			return null;
		}

		if (Type.isNil(this.locationSelectorDialog))
		{
			const tabs = [
				{
					id: 'location',
					title: Loc.getMessage('CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_ENTITY_TITLE'),
				},
			];

			const items = [];

			this.locations.rooms?.forEach((room) => {
				items.push({
					id: room.ID,
					title: room.NAME,
					subtitle: this.#getCapacityTitle(room.CAPACITY ?? null),
					entityId: 'location',
					tabs: 'location',
					avatarOptions: {
						bgColor: room.COLOR,
						bgSize: '22px',
						bgImage: 'none',
					},
					customData: {
						locationId: room.LOCATION_ID,
					},
				});
			});
			this.locationSelectorDialog = new TagSelector({
				multiple: false,
				textBoxAutoHide: true,
				dialogOptions: {
					id: 'todo-robot-calendar-room-selector-dialog',
					targetNode: this.#locationSelectorWrapper,
					context: 'CRM_ACTIVITY_TODO_ROBOT_CALENDAR_ROOM',
					multiple: false,
					dropdownMode: true,
					showAvatars: true,
					enableSearch: true,
					width: 450,
					height: 300,
					zIndex: 2500,
					items,
					tabs,
				},
			});
			if (locationId)
			{
				const locationTag = (
					items.find((location) => location.id === locationId)
					?? this.#getLocationExpressionTag(locationId)
				);

				this.locationSelectorDialog.addTag(locationTag);
			}
		}

		return this.locationSelectorDialog;
	}

	#getLocationExpressionTag(expression: string): Object
	{
		return {
			id: expression,
			title: expression,
			entityId: 'location',
		};
	}

	async #renderLocation(value: ?number): HTMLDivElement
	{
		this.renderControl('Duration');

		this.locations = await this.#fetchRoomsManagerData();
		const wrapper = Tag.render`<div id="id_location"></div>`;
		this.#getLocationSelectorDialog(value).renderTo(wrapper);

		return wrapper;
	}

	async #fetchRoomsManagerData(): Object
	{
		return new Promise((resolve) => {
			ajax
				.runAction('calendar.api.locationajax.getRoomsManagerData')
				.then((response) => {
					resolve(response.data);
				})
				.catch((errors) => {
					console.log(errors);
				})
			;
		});
	}

	#prepareMenuItems(): Array
	{
		const menuItems = [];
		// eslint-disable-next-line unicorn/no-this-assignment
		const createTodo = this;
		menuItems.push(
			{
				html: this.#getActionItemHtml(
					'CALENDAR_1',
					Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_CALENDAR'),
				),
				onclick()
				{
					this.popupWindow.close();
					createTodo.renderControl('Duration');
				},
			},
			{
				html: this.#getActionItemHtml(
					'PERSON',
					Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_CLIENT'),
				),
				onclick()
				{
					this.popupWindow.close();
					createTodo.renderControl('Client', 'Y');
				},
			},
			{
				html: this.#getActionItemHtml(
					'PERSONS_2',
					Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_COLLEAGUE'),
				),
				fieldName: 'Colleagues',
				onclick()
				{
					this.popupWindow.close();
					createTodo.renderControl('Colleagues');
				},
			},
			{
				html: this.#getActionItemHtml(
					'LOCATION_1',
					Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_ADDRESS'),
				),
				onclick()
				{
					this.popupWindow.close();
					createTodo.renderControl('Address');
				},
			},
		);
		if ('LocationId' in this.#documentFields)
		{
			menuItems.push({
				html: this.#getActionItemHtml(
					'CHATS_PERSONS',
					Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_ROOM'),
				),
				onclick()
				{
					this.popupWindow.close();
					createTodo.renderControl('LocationId');
				},
			});
		}

		menuItems.push({
			html: this.#getActionItemHtml(
				'INSERT_HYPERLINK',
				Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_LINK'),
			),
			onclick()
			{
				this.popupWindow.close();
				createTodo.renderControl('Link');
			},
		});

		if ('Attachment' in this.#documentFields)
		{
			menuItems.push({
				html: this.#getActionItemHtml(
					'ATTACH',
					Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_FILE'),
				),
				text: this.#documentFields.Attachment.Name,
				onclick()
				{
					this.popupWindow.close();
					createTodo.renderControl('Attachment');
				},
			});
		}

		return menuItems;
	}

	#onAdditionalSettingsButtonClick()
	{
		const menuId = `bp-create-todo-activity-${Math.random()}`;
		PopupMenu.show(
			menuId,
			this.#additionalSettingsButton,
			this.#prepareMenuItems(),
			{
				autoHide: true,
				offsetLeft: (Dom.getPosition(this).width / 2),
				angle: { position: 'top', offset: 0 },
				className: 'bizproc-automation-inline-selector-menu',
				overlay: { backgroundColor: 'transparent' },
			},
		);
	}

	async renderControl(fieldName: string, value = null)
	{
		if (this.#additionalSettingsFields[fieldName] || !(fieldName in this.#documentFields))
		{
			return;
		}

		if (fieldName === 'Colleagues')
		{
			this.renderControl('Duration');
		}

		const newRow = Dom.create('div', { attrs: { className: 'bizproc-automation-popup-settings' } });

		Dom.append(
			Dom.create(
				'span',
				{
					text: this.#documentFields[fieldName].Name,
					attrs: {
						className: 'bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete',
					},
				},
			),
			newRow,
		);

		const deleteButton = Dom.create('a', {
			attrs: {
				className: 'bizproc-automation-popup-settings-delete bizproc-automation-popup-settings-link bizproc-automation-popup-settings-link-light',
			},
			props: { href: '#' },
			events: {
				click: (e) => this.#removeControl(fieldName, e),
			},
			text: Loc.getMessage('CRM_BP_CREATE_TODO_ADDITIONAL_FIELD_DELETE'),
		});
		Dom.append(deleteButton, newRow);

		// eslint-disable-next-line init-declarations
		let node;
		if (fieldName === 'LocationId')
		{
			node = await this.#renderLocation(value);
		}
		else if (fieldName === 'Attachment')
		{
			node = this.#renderFile();
		}
		else
		{
			node = this.#renderBaseControl(fieldName, value);
		}

		Dom.append(node, newRow);
		this.#additionalSettingsFields[fieldName] = newRow;
		Dom.append(this.#additionalSettingsFields[fieldName], this.#additionalSettingsWrapper);
	}

	#renderBaseControl(fieldName, value = null): HTMLElement
	{
		return BX.Bizproc.FieldType.renderControl(
			this.#documentType,
			this.#documentFields[fieldName],
			this.#documentFields[fieldName].FieldName,
			value,
		);
	}

	#renderFile(): HTMLElement
	{
		const wrapper = Dom.create('div', {
			attrs: {
				'data-role': 'file-selector',
				'data-config': this.#dataConfig,
			},
		});

		this.#fileSelector = new FileSelector({
			context:
				new SelectorContext({
					fields: getGlobalContext().document.getFields(),
					rootGroupTitle: getGlobalContext().document.title,
				}),
		});

		this.#fileSelector.renderTo(wrapper);

		const template = Designer.getInstance().getRobotSettingsDialog()?.template;
		if (template)
		{
			template.robotSettingsControls.push(this.#fileSelector);
		}

		return wrapper;
	}

	#removeControl(fieldName: string, e = null)
	{
		if (!this.#additionalSettingsFields[fieldName])
		{
			return;
		}

		if (fieldName === 'Attachment')
		{
			this.#fileSelector.destroy();
		}

		if (fieldName === 'Duration')
		{
			this.#removeControl('Colleagues');
			this.#removeControl('LocationId');
		}

		e?.preventDefault();
		Dom.remove(this.#additionalSettingsFields[fieldName]);
		delete this.#additionalSettingsFields[fieldName];
	}

	#getCapacityTitle(value: ?number): string
	{
		if (Type.isNil(value) || value <= 0)
		{
			return '';
		}

		return Loc.getMessage(
			'CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_CAPACITY',
			{ '#CAPACITY_VALUE#': value },
		);
	}

	#getActionItemHtml(iconKey: string, message: string): string
	{
		const icon = new Icon({
			icon: Main[iconKey],
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-50'),
			size: 25,
		});

		return Tag.render`
			<span class="bizproc_automation-todo-activity-actions-menu-item">
				<span class="bizproc_automation-todo-activity-actions-menu-item-icon">${icon.render()}</span>
				${message}
			</span>
		`;
	}

	#getColorSelector(wrapper, settings, isAvailableColor): ColorSelector
	{
		return new ColorSelector(
			{
				target: wrapper,
				colorList: settings.valuesList,
				selectedColorId: isAvailableColor ? settings.selectedValueId : 'default',
				readOnlyMode: settings.readOnlyMode,
			},
		);
	}

	#onChangeAttachmentTypeHandler(value)
	{
		this.#fileControl.hidden = value === 'disk';
		this.#diskControl.hidden = value === 'file';

		const disableInputs = BX(`BPMA-${this.#attachmentType}-control`).querySelectorAll('input');
		for (const disableInput of disableInputs)
		{
			disableInput.disable = true;
		}
		const enableInputs = BX(`BPMA-${value}-control`).querySelectorAll('input');
		for (const enableInput of enableInputs)
		{
			enableInput.disabled = false;
		}
	}

	#openDiskFileDialog()
	{
		const urlSelect = `/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=${Loc.getMessage('SITE_ID')}`;
		const dialogName = 'BPMA';
		BX.ajax.get(
			urlSelect,
			`multiselect=Y&dialogName=${dialogName}`,
			() => {
				setTimeout(() => {
					BX.DiskFileDialog.obCallback[dialogName] = {
						saveButton: (tab, path, selected) => this.#onSaveButtonClickHandler(tab, path, selected),
					};
					BX.DiskFileDialog.openDialog(dialogName);
				}, 10);
			},
		);
	}

	#onSaveButtonClickHandler(tab, path, selected)
	{
		for (const file of Object.values(selected))
		{
			if (file.type === 'file')
			{
				Dom.append(this.#renderAttachmentFile(file), this.#diskControlItems);
			}
		}
	}

	#renderAttachmentFile(file): Element
	{
		const fileWrapper = Tag.render`
			<div>
				<input type="hidden" name="attachment[]" value="${(file.id).toString().slice(1)}"/>
				<span style="color: grey">${BX.util.htmlspecialchars(file.name)}</span>
			</div>
		`;
		const deleteButton = Tag.render`
			<a style="color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
		`;
		Event.bind(deleteButton, 'click', () => Dom.remove(fileWrapper));
		Dom.append(deleteButton, fileWrapper);

		return fileWrapper;
	}

	#assertValidOptions(options: CreateTodoActivityOptions)
	{
		if (!Type.isObject(options.documentFields))
		{
			throw new TypeError('documentFields must be a object');
		}

		if (!Type.isElementNode(options.additionalSettingsWrapper))
		{
			throw new Error('additionalSettingsWrapper must be HTMLElement');
		}

		if (!Type.isElementNode(options.additionalSettingsButton))
		{
			throw new Error('additionalSettingsButton must be HTMLElement');
		}

		if (!Type.isArrayFilled(options.documentType))
		{
			throw new Error('documentType must be filled array');
		}

		if (!Type.isElementNode(options.colorSelectorWrapper))
		{
			throw new Error('colorSelectorWrapper must be HTMLElement');
		}

		if (!Type.isStringFilled(options.formName))
		{
			throw new Error('formName must be filled string');
		}

		if (!Type.isStringFilled(options.dataConfig))
		{
			throw new Error('dataConfig must be a filled string');
		}

		if (!Type.isObject(options.colorSettings))
		{
			throw new TypeError('colorSettings must be a object');
		}
	}
}

namespace.CrmCreateTodoActivity = CrmCreateTodoActivity;
