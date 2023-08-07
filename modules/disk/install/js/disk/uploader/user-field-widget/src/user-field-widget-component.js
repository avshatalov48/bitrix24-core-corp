import { Type, Loc } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { VueUploaderComponent } from 'ui.uploader.vue';
import { TileWidgetComponent, TileWidgetSlot, TileWidgetOptions, TileWidgetItem } from 'ui.uploader.tile-widget';

import UserFieldWidget from './user-field-widget';
import UserFieldControl from './user-field-control';
import ItemMenu from './item-menu';
import SettingsMenu from './settings-menu';

import { ControlPanel } from './components/control-panel';
import { DocumentPanel } from './components/document-panel';
import { InsertIntoTextButton } from './components/insert-into-text-button';

import './css/user-field-widget-component.css';

import type { UploaderOptions } from 'ui.uploader.core';
import type { BitrixVueComponentProps } from 'ui.vue3';
import type { Menu } from 'main.popup';
import { UserFieldWidgetOptions } from './user-field-widget-options';

/**
 * @memberof BX.Disk.Uploader
 */
export const UserFieldWidgetComponent: BitrixVueComponentProps = {
	name: 'UserFieldWidget',
	extends: VueUploaderComponent,
	components: {
		TileWidgetComponent,
		DocumentPanel,
	},
	setup(): Object
	{
		return {
			customUploaderOptions: UserFieldWidget.getDefaultUploaderOptions(),
		};
	},
	data(): Object
	{
		const options: UserFieldWidgetOptions = this.widgetOptions;
		return {
			controlVisibility: Type.isBoolean(options.controlVisibility) ? options.controlVisibility : true,
			uploaderPanelVisibility: Type.isBoolean(options.uploaderPanelVisibility) ? options.uploaderPanelVisibility : true,
			documentPanelVisibility: Type.isBoolean(options.documentPanelVisibility) ? options.documentPanelVisibility : false,
		};
	},
	provide(): Object<string, any>
	{
		return {
			userFieldControl: this.userFieldControl,
			postForm: this.userFieldControl.getMainPostForm(),
			getMessage: this.getMessage,
		}
	},
	beforeCreate(): void
	{
		this.userFieldControl = new UserFieldControl(this);
	},
	methods: {
		getMessage(code: string, replacements?: Object<string, string>): ?string
		{
			return Loc.getMessage(code, replacements);
		},

		show(forceUpdate = false): void
		{
			if (forceUpdate)
			{
				this.$refs.container.style.display = 'block';
			}

			this.controlVisibility = true;
		},

		hide(forceUpdate = false): void
		{
			if (forceUpdate)
			{
				this.$refs.container.style.display = 'none';
			}

			this.controlVisibility = false;
		},

		showUploaderPanel(): void
		{
			this.uploaderPanelVisibility = true;
		},

		hideUploaderPanel(): void
		{
			this.uploaderPanelVisibility = false;
		},

		showDocumentPanel(): void
		{
			this.documentPanelVisibility = true;
		},

		hideDocumentPanel(): void
		{
			this.documentPanelVisibility = false;
		},

		enableAutoCollapse(): void
		{
			this.$refs.tileWidget.enableAutoCollapse();
		},

		getUploaderOptions(): UploaderOptions
		{
			return UserFieldWidget.prepareUploaderOptions(this.uploaderOptions);
		},
	},
	computed: {
		tileWidgetOptions(): TileWidgetOptions {
			const tileWidgetOptions: TileWidgetOptions =
				Type.isPlainObject(this.widgetOptions.tileWidgetOptions)
					? Object.assign({}, this.widgetOptions.tileWidgetOptions)
					: {}
			;

			tileWidgetOptions.slots = Type.isPlainObject(tileWidgetOptions.slots) ? tileWidgetOptions.slots : {};
			tileWidgetOptions.slots[TileWidgetSlot.AFTER_TILE_LIST] = ControlPanel;
			if (this.userFieldControl.getMainPostForm())
			{
				tileWidgetOptions.slots[TileWidgetSlot.ITEM_EXTRA_ACTION] = InsertIntoTextButton;
			}

			tileWidgetOptions.showItemMenuButton = true;
			tileWidgetOptions.events = {
				'TileItem:onMenuCreate': (event: BaseEvent): void => {
					const { item, menu }: { item: TileWidgetItem, menu: Menu } = event.getData();
					const itemMenu: ItemMenu = new ItemMenu(this.userFieldControl, item, menu);
					itemMenu.build();
				},
			};

			const settingsMenu: SettingsMenu = new SettingsMenu(this.userFieldControl);
			if (settingsMenu.hasItems())
			{
				tileWidgetOptions.showSettingsButton = true;
				tileWidgetOptions.events['SettingsButton:onClick'] = (event: BaseEvent): void => {
					const { button } = event.getData();
					settingsMenu.toggle(button);
				};
			}

			return tileWidgetOptions;
		},
	},
	// language=Vue
	template: `
		<div 
			class="disk-user-field-control" 
			:class="[{ '--has-files': this.items.length > 0 }]"
			:style="{ display: controlVisibility ? 'block' : 'none' }"
			ref="container"
		>
			<div 
				class="disk-user-field-uploader-panel"
				:class="[{ '--hidden': !uploaderPanelVisibility }]"
				ref="uploader-container"
			>
				<TileWidgetComponent
					:widgetOptions="tileWidgetOptions" 
					:uploader-adapter="adapter"
					ref="tileWidget"
				/>
			</div>

			<div 
				class="disk-user-field-create-document"
				v-if="this.userFieldControl.canCreateDocuments() && !this.userFieldControl.getMainPostForm() && !documentPanelVisibility"
				@click="documentPanelVisibility = true"
			>{{ getMessage('DISK_UF_WIDGET_CREATE_DOCUMENT') }}</div>

			<div 
				class="disk-user-field-document-panel"
				:class="{ '--single': this.userFieldControl.getMainPostForm() !== null }"
				ref="document-container"
				v-if="this.userFieldControl.canCreateDocuments() && documentPanelVisibility"
			>
				<DocumentPanel />
			</div>
		</div>
		`
	,
};