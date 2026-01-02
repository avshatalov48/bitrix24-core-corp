import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { BasePopup } from 'humanresources.company-structure.structure-components';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { EventEmitter } from 'main.core.events';
import type { PopupOptions } from 'main.popup';
import { events } from '../../../consts';
import { EditDepartmentMenuItem } from '../../menu/items/department/edit-department-menu-item';

// @vue/component
export const DepartmentInfoIconButton = {
	name: 'DepartmentInfoIconButton',

	components: {
		BasePopup,
	},

	props: {
		entityId: {
			type: Number,
			required: true,
		},
		canvasZoom: {
			type: Number,
			required: true,
		},
		description: {
			type: [String, null],
			default: null,
		},
	},

	data(): { showPopup: boolean }
	{
		return {
			showPopup: false,
		};
	},

	computed:
	{
		popupConfig(): PopupOptions
		{
			const popupWidth = 340;
			const buttonWidth = 22 * this.canvasZoom;
			const initialPopupOffset = 41 - this.canvasZoom; // subtract 1px * zoom
			const angleWidth = 33;

			return {
				width: popupWidth,
				bindElement: this.$refs.departmentMenuButton,
				bindOptions: { position: 'top' },
				borderRadius: '12px',
				contentNoPaddings: true,
				contentPadding: 0,
				padding: 16,
				offsetTop: -2 * this.canvasZoom,
				offsetLeft: buttonWidth / 2 - popupWidth / 2 + initialPopupOffset,
				angleOffset: popupWidth / 2 - angleWidth / 2,
			};
		},
	},

	created(): void
	{
		this.menuItem = new EditDepartmentMenuItem(EntityTypes.team);
		const permissionChecker = PermissionChecker.getInstance();

		this.canEdit = this.menuItem.hasPermission(permissionChecker, this.entityId);
	},

	methods:
	{
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onClose(): void
		{
			if (this.showPopup)
			{
				this.showPopup = false;
				EventEmitter.unsubscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.onClose);
				EventEmitter.unsubscribe(events.HR_DRAG_ENTITY, this.onClose);
			}
		},
		onOpen(): void
		{
			if (!this.showPopup)
			{
				this.showPopup = true;
				EventEmitter.subscribe(events.HR_DEPARTMENT_MENU_CLOSE, this.onClose);
				EventEmitter.subscribe(events.HR_DRAG_ENTITY, this.onClose);
			}
		},
		addDescription(): void
		{
			this.onClose();
			this.menuItem.invoke({
				entityId: this.entityId,
				analyticSource: AnalyticsSourceType.CARD,
				refToFocus: 'description',
			});
		},
	},

	template: `
		<div
			v-if="canEdit || description"
			class="ui-icon-set --info-1 humanresources-tree__node_department-menu-button"
			:class="{ '--focused': this.showPopup }"
			ref="departmentMenuButton"
			@click.stop="onOpen"
			data-test-id="tree-node-info-button"
		>
		</div>
		<BasePopup
			v-if="showPopup"
			:config="popupConfig"
			:id="'humanresources-tree__node_info-popup' + entityId"
			@close="onClose"
		>
			<div class="humanresources-tree__node_info-popup-content_description" v-if="description">
				{{ description }}
			</div>
			<div v-else class="humanresources-tree__node_info-popup-content">
				<div class="humanresources-tree__node_info-popup-content_left"></div>
				<div class="humanresources-tree__node_info-popup-content_right">
					<div class="humanresources-tree__node_info-popup-content_right_title">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_INFO_POPUP_ADD_DESCRIPTION_TITLE') }}
					</div>
					<div class="humanresources-tree__node_info-popup-content_right_text">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_INFO_POPUP_ADD_DESCRIPTION_TEXT') }}
					</div>
					<div
						class="ui-btn ui-btn-primary ui-btn-round ui-btn-xs ui-btn-no-caps"
						data-test-id="tree-node-info-popup_add-button"
						@click="addDescription"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_INFO_POPUP_ADD_DESCRIPTION_BUTTON') }}
					</div>
				</div>
			</div>
		</BasePopup>
	`,
};
