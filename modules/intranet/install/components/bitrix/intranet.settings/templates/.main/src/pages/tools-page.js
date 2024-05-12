import { EventEmitter } from 'main.core.events';
import { Draggable } from 'ui.draganddrop.draggable';
import { Section, Row } from 'ui.section';
import { SwitcherNestedItem, SwitcherNested } from 'ui.switcher-nested';
import { Loc, Type, Event as EventBX, Dom, Tag } from 'main.core';
import { SettingsSection, SettingsRow, BaseSettingsPage } from 'ui.form-elements.field';

export class ToolsPage extends BaseSettingsPage
{
	#inputForSaveSortTools: HTMLElement;
	#toolsWrapperRow: Row;
	#draggable: Draggable;
	#mainSection: Section;
	#settingsSection: SettingsSection;

	constructor()
	{
		super();
		this.titlePage = Loc.getMessage('INTRANET_SETTINGS_TITLE_PAGE_TOOLS');
		this.descriptionPage = Loc.getMessage('INTRANET_SETTINGS_DESCRIPTION_PAGE_TOOLS');
	}

	getType(): string
	{
		return 'tools';
	}

	appendSections(contentNode: HTMLElement): void
	{
		const description = new Row({
			content: this.getDescription().getContainer(),
		});
		new SettingsRow({
			row: description,
			parent: this.#getSettingsSection(),
		});

		if (this.hasValue('tools'))
		{
			this.#renderToolsSelectors();
		}

		this.#getSettingsSection().renderTo(contentNode);
	}

	#renderToolsSelectors(): void
	{
		const tools = this.getValue('tools');
		const startSort = [];
		const toolSelectors = [];

		Object.keys(tools).forEach((item) => {
			startSort.push(tools[item].menuId)
			const tool = tools[item];
			const subgroups = tool.subgroups;
			let toolSelectorItems = [];

			if (Object.keys(subgroups).length > 0)
			{
				toolSelectorItems = this.#getToolsSelectorsItems(subgroups, tool);
			}

			const toolSelector = new SwitcherNested({
				id: tool.code,
				title: tool.name,
				link: this.getPermission().canEdit() ? tool['settings-path'] : null,
				infoHelperCode: this.getPermission().canEdit() ? tool['infohelper-slider'] : null,
				linkTitle: tool['settings-title'] ?? Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_LINK_SETTINGS'),
				isChecked: tool.enabled,
				mainInputName: tool.code,
				isOpen: false,
				items: toolSelectorItems,
				isDisabled: !this.getPermission().canEdit(),
				isDefault: tool.default,
				helpMessage: !this.getPermission().canEdit()
					? Loc.getMessage('INTRANET_SETTINGS_ELEMENT_PERMISSION_MSG')
					: Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_DISABLED', {'#TOOL#': tool.name}),
			});
			toolSelectors.push(toolSelector);

			const toolSelectorSection = new SettingsSection({
				section: toolSelector,
			});

			Dom.style(toolSelectorSection.getSectionView().render(), 'margin-bottom', '8px');
			Dom.attr(toolSelectorSection.getSectionView().render(), 'data-menu-id', tool.menuId);
			this.#getToolsWrapperRow().append(toolSelectorSection.getSectionView().render());
			new SettingsRow({
				row: this.#getToolsWrapperRow(),
				parent: this.#getSettingsSection(),
				child: toolSelectorSection
			});
		});
	}

	#getSubToolHelpMessage(tool: Object, parentToolName: ?string): string
	{
		if (!this.getPermission().canEdit())
		{
			return Loc.getMessage('INTRANET_SETTINGS_ELEMENT_PERMISSION_MSG');
		}

		if (tool.disabled)
		{
			return Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_DISABLED', {'#TOOL#': tool.name});
		}

		if (tool.default)
		{
			return Loc.getMessage('INTRANET_SETTINGS_FIELD_HELP_MESSAGE_MAIN_TOOL', {'#TOOL#': parentToolName ?? ''});
		}

		return '';
	}

	#getToolsSelectorsItems(subgroups: Object, tool: Object): Array<SwitcherNestedItem>
	{
		const toolSelectorItems = [];

		Object.keys(subgroups).forEach((item) => {
			const subgroupConfig = subgroups[item];

			if (
				Type.isNull(subgroupConfig.name)
				|| Type.isNull(subgroupConfig.code)
				|| Type.isNull(subgroupConfig.enabled)
			)
			{
				return;
			}

			const toolSelectorItem = new SwitcherNestedItem({
				title: subgroupConfig.name,
				id: subgroupConfig.code,
				inputName: subgroupConfig.code,
				isChecked: subgroupConfig.enabled,
				settingsPath: this.getPermission().canEdit() ? subgroupConfig['settings_path'] : null,
				settingsTitle: subgroupConfig['settings_title'] ?? Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_LINK_SETTINGS'),
				infoHelperCode: this.getPermission().canEdit() ? subgroupConfig['infohelper-slider'] : null,
				isDisabled: !this.getPermission().canEdit(),
				isDefault: subgroupConfig.default ?? subgroupConfig.disabled ?? false,
				helpMessage: this.#getSubToolHelpMessage(subgroupConfig, tool.name),
			});

			if (subgroupConfig.disabled)
			{
				Dom.style(toolSelectorItem.getSwitcher().getNode(), {opacity: '0.4'});
			}
			else
			{
				EventEmitter.subscribe(
					toolSelectorItem.getSwitcher(),
					'toggled',
					() => {
						this.getAnalytic()?.addEventToggleTools(
							subgroupConfig.code,
							toolSelectorItem.getSwitcher().isChecked()
						);
					}
				);
			}

			if (subgroupConfig.code === 'tool_subgroup_team_work_instant_messenger')
			{
				EventBX.bind(
					toolSelectorItem.getSwitcher().getNode(),
					'click',
					() => {
						if (!toolSelectorItem.getSwitcher().isChecked())
						{
							this.#getWarningMessage(subgroupConfig.code, toolSelectorItem.getSwitcher().getNode(), Loc.getMessage('INTRANET_SETTINGS_WARNING_TOOL_INSTANT_MESSENGER')).show();
						}
					}
				);
			}

			toolSelectorItems.push(toolSelectorItem);
		});

		return toolSelectorItems;
	}

	#getMainSection(): Section
	{
		if (this.#mainSection)
		{
			return this.#mainSection;
		}

		this.#mainSection = new Section(this.getValue('sectionTools'));

		return this.#mainSection;
	}

	#getSettingsSection(): SettingsSection
	{
		if (this.#settingsSection)
		{
			return this.#settingsSection;
		}

		this.#settingsSection = new SettingsSection({
			section: this.#getMainSection(),
			parent: this,
		});

		return this.#settingsSection;
	}

	getDescription(): BX.UI.Alert
	{
		const descriptionText = `
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_TOOLS_DESCRIPTION')}
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=18213196')">
				${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		`;

		return new BX.UI.Alert({
			text: descriptionText,
			inline: true,
			size: BX.UI.Alert.Size.SMALL,
			color: BX.UI.Alert.Color.PRIMARY,
			animated: true,
		});
	}

	#getInputForSaveSortTools(): HTMLElement
	{
		if (this.#inputForSaveSortTools)
		{
			return this.#inputForSaveSortTools;
		}

		this.#inputForSaveSortTools = Tag.render`
			<input type="hidden" name="tools-sort">
		`;

		return this.#inputForSaveSortTools;
	}

	#getDraggable(): Draggable
	{
		if (this.#draggable)
		{
			return this.#draggable;
		}

		this.#draggable = new Draggable({
			container: [this.#getToolsWrapperRow().render()],
			draggable: '.--tool-selector',
			dragElement: '.ui-section__dragdrop-icon-wrapper',
			type: Draggable.CLONE,
		});

		return this.#draggable;
	}

	#getToolsWrapperRow(): Row
	{
		if (this.#toolsWrapperRow)
		{
			return this.#toolsWrapperRow;
		}

		this.#toolsWrapperRow = new Row({});

		return this.#toolsWrapperRow;
	}

	#getWarningMessage(toolId, bindElement, message)
	{
		return BX.PopupWindowManager.create(
			toolId,
			bindElement,
			{
				content: message,
				darkMode: true,
				autoHide: true,
				angle: true,
				offsetLeft: 14,
				bindOptions: {
					position: 'bottom',
				},
				closeByEsc: true,
			}
		);
	}
}
