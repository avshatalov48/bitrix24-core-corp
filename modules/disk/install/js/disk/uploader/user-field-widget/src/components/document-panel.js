import { Loc } from 'main.core';
import { RichLoc } from 'ui.vue3.components.rich-loc';
import { Menu, type MenuItemOptions } from 'ui.system.menu';
import { FileIcon } from 'ui.icons.generator';
import type { BitrixVueComponentProps } from 'ui.vue3';

import { DocumentType } from '../const';
import { createDocumentDialog } from '../helpers';
import { userFieldSettings } from '../user-field-settings';
import './css/document-panel.css';

// @vue/component
export const DocumentPanel: BitrixVueComponentProps = {
	name: 'DocumentPanel',
	components: {
		RichLoc,
	},
	inject: ['uploader', 'userFieldControl', 'getMessage'],
	setup(): { isBoardsEnabled: boolean }
	{
		return {
			DocumentType,
			isBoardsEnabled: userFieldSettings.isBoardsEnabled(),
		};
	},
	data(): Object
	{
		return {
			currentServiceName: this.userFieldControl.getCurrentDocumentService()?.name,
		};
	},
	computed: {
		createServiceFormatted(): string
		{
			const labelText = Loc.getMessage('DISK_UF_WIDGET_EDIT_SERVICE_LABEL');
			const macros = '#NAME#';
			const position = labelText.indexOf(macros);
			const preText = labelText.slice(0, position);
			const postText = labelText.slice(position + macros.length);

			return `${preText}[link]${this.currentServiceName}[/link]${postText}`;
		},
	},
	methods: {
		renderSvg(documentType: string): string
		{
			return new FileIcon({ name: documentType, size: 36, align: 'center' }).generate().outerHTML;
		},
		createDocument(documentType: string): void
		{
			createDocumentDialog({
				uploader: this.uploader,
				documentType,
				documentHandlers: Object.values(userFieldSettings.getDocumentServices()),
				onAddFile: () => this.userFieldControl.showUploaderPanel(),
			});
		},
		openMenu(): void
		{
			this.menu ??= new Menu({
				bindElement: this.$refs.service,
				autoHide: true,
				offsetTop: 5,
				items: this.getMenuItems(),
			});

			this.menu.show();
		},
		getMenuItems(): MenuItemOptions[]
		{
			const services = Object.values(userFieldSettings.getDocumentServices());
			const currentServiceCode = this.userFieldControl.getCurrentDocumentService()?.code;

			return services.map((service: { name: string, code: string }) => ({
				title: service.name,
				isSelected: currentServiceCode === service.code,
				onClick: (): void => {
					BX.Disk.saveDocumentService(service.code);
					this.currentServiceName = service.name;
					this.menu.updateItems(this.getMenuItems());
				},
			}));
		},
	},
	template: `
		<div class="disk-user-field-panel">
			<div class="disk-user-field-panel-doc-wrap">
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Docx)">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--doc">
						<div class="disk-user-field-panel-card-icon" v-html="renderSvg(DocumentType.Docx)"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_DOCX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Xlsx)">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--xls">
						<div class="disk-user-field-panel-card-icon" v-html="renderSvg(DocumentType.Xlsx)"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_XLSX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Pptx)">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--ppt">
						<div class="disk-user-field-panel-card-icon" v-html="renderSvg(DocumentType.Pptx)"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_PPTX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument(DocumentType.Board)" v-if="isBoardsEnabled">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--board">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_BOARD') }}</div>
					</div>
				</div>
			</div>
			<div class="disk-user-field-create-document-by-service" @click="openMenu">
				<RichLoc :text="createServiceFormatted" placeholder="[link]">
					<template #link="{ text }">
						<span class="disk-user-field-document-current-service" ref="service">{{ text }}</span>
					</template>
				</RichLoc>
			</div>
		</div>
	`,
};
