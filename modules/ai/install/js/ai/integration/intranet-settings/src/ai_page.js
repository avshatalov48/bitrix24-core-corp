import {Loc, Runtime, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Alert, AlertColor, AlertSize} from 'ui.alerts';
import {BaseField} from 'ui.form-elements.view';
import {Row} from 'ui.section';
import {Checker} from 'ui.form-elements.view';
import {SelectorField} from 'ai.ui.field.selectorfield';
import {SettingsSection, SettingsRow, SettingsField, BaseSettingsPage} from 'ui.form-elements.field';
import {AiSettingsItem, AiSettingsGroup, AiSettingsItemRelations, AiSettingsItemField} from './types';

import './css/ai_page.css';

export class AiPage extends BaseSettingsPage
{
	static #groupIconDefaultSet = 'ui.icon-set.main';
	static #groupIconDefaultIcon = '--copilot-ai';

	titlePage: string = '';
	descriptionPage: string = '';

	#itemRelations: [AiSettingsItemRelations] = [];
	#itemFields: {[string]: AiSettingsItemField} = {};
	#onSaveCheckers: [Checker] = [];

	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_AI');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_AI_DESC');
	}

	getType(): string
	{
		return 'ai';
	}

	appendSections(contentNode: HTMLElement): void
	{
		const groups: ?{[string]: AiSettingsGroup} = this.getValue('fields');
		if (groups)
		{
			this.isOpen = true;
			for (let groupCode in groups)
			{
				const section = this.#buildGroup(groups[groupCode]);
				if (this.isOpen === true)
				{
					this.isOpen = false;
				}
				if (section)
				{
					section.renderTo(contentNode);
				}

				if (groups[groupCode].relations)
				{
					groups[groupCode].relations.forEach(relation => {
						this.#itemRelations.push(relation);
					});
				}
			}
		}

		this.#bindEvents();
	}

	#buildGroup(group: AiSettingsGroup): ?SettingsSection
	{
		if (!group.items)
		{
			return;
		}
		const items = Object.values(group.items);
		if (items.length <= 0)
		{
			return;
		}

		const {title, helpdesk, icon} = group;
		Runtime.loadExtension(icon.set ?? AiPage.#groupIconDefaultSet);

		const section = new SettingsSection({
			parent: this,
			section: {
				title: title,
				titleIconClasses: 'ui-icon-set ' + (icon.code ?? AiPage.#groupIconDefaultIcon),
				isOpen: this.isOpen,
			},
		});

		if (group.description)
		{
			let description = group.description;
			if (helpdesk)
			{
				const helpdeskCode = 'redirect=detail&code=' + helpdesk;
				description += ' <a href="javascript: void();"'
					+ ' onclick="BX.PreventDefault(); top.BX.Helper.show(\'' + helpdeskCode + '\');"'
					+ '>'
					+ Loc.getMessage('INTRANET_SETTINGS_HELPDESK_LINK')
					+ '</a>'
				;
			}

			section.getSectionView().append(
				(new Row({
					content: (new Alert({
						row: {
							separator: 'null',
						},
						text: description,
						inline: true,
						size: AlertSize.SMALL,
						color: AlertColor.PRIMARY,
					})).getContainer(),
				})).render())
			;
		}

		items.forEach(item => {
			const row = this.#buildItem(item);
			if (row.getChildrenElements().length > 0)
			{
				row.setParentElement(section);
			}
		});

		return section;
	}

	#buildItem(item: AiSettingsItem): SettingsRow
	{
		const { code, type, title, header, value, options, recommended } = item;
		const withOnSave = item.onSave && item.onSave.switcher;

		const row = new SettingsRow({
			row: {
				className: withOnSave ? '--with-on-save' : '',
			},
		});

		let field = null;
		if (type === 'boolean')
		{
			field = new Checker({
				inputName: code,
				title: title,
				checked: value,
				hintOn: header,
				hintOff: header,
			});
		}

		else if (type === 'list')
		{
			if (options && value)
			{
				const items = [];
				const additionalItems = [];
				for (const option in options)
				{
					if (Type.isString(options[option]))
					{
						items.push({
							name: options[option],
							value: option,
							selected: option === value,
						});
					}
					else if (Type.isPlainObject(options[option]))
					{
						additionalItems.push(options[option]);
					}
				}

				if (items.length > 0)
				{
					field = new SelectorField({
						inputName: code,
						label: title,
						name: code,
						items,
						additionalItems,
						recommendedItems: recommended,
						current: value,
					});
				}
			}
		}

		if (field)
		{
			this.#addField(code, field, row);
		}

		if (withOnSave)
		{
			const onSaveField = new Checker({
				inputName: code + '_onsave',
				title: item.onSave.switcher,
				checked: false,
				size: 'extra-small',
				noMarginBottom: true,
			});

			this.#addField(code, onSaveField, row);
			this.#onSaveCheckers.push(onSaveField);
		}

		return row;
	}

	#addField(code: string, field: BaseField, row: SettingsRow)
	{
		row.addChild(new SettingsField({
			fieldView: field,
		}));

		this.#itemFields[code] = {
			code,
			field,
			row,
		};
	}

	#bindEvents()
	{
		if (this.#onSaveCheckers.length > 0)
		{
			EventEmitter.subscribe(
				EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onBeforeSave', () => {
					this.#onSaveCheckers.forEach(field => {
						field.switcher?.check(false, false);
					});
				});
		}

		if (this.#itemRelations.length > 0)
		{
			this.#itemRelations.forEach(relation => {
				const parent = this.#itemFields[relation.parent];
				if (parent && parent.field && parent.field instanceof Checker)
				{
					EventEmitter.subscribe(parent.field, 'change', event => {
						const isActive = event.getData();
						relation.children.forEach(child => {
							const node = this.#itemFields[child]?.row?.getRowView();
							if (node)
							{
								isActive ? node.show() : node.hide();
							}
						});
					});
				}
			});
		}
	}
}
