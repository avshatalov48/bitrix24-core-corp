import { Loc, Tag, Dom } from 'main.core';
import { Menu, MenuItem } from 'main.popup';

import type { BitrixVueComponentProps } from 'ui.vue3';

import './css/document-panel.css';

export const DocumentPanel: BitrixVueComponentProps = {
	name: 'DocumentPanel',
	inject: ['uploader', 'userFieldControl', 'getMessage'],
	props: {
		item: {
			type: Object,
			default: {},
		},
	},
	data(): Object
	{
		return {
			isBoardsEnabled: this.userFieldControl.isBoardsEnabled(),
		};
	},
	created(): void
	{
		this.menu = null;
		this.currentServiceNode = null;
	},
	mounted(): void
	{
		const labelText: string = Loc.getMessage('DISK_UF_WIDGET_EDIT_SERVICE_LABEL') || '';
		const macros = '#NAME#';
		const position: number = labelText.indexOf(macros);
		if (position !== -1)
		{
			const preText: string = labelText.substring(0, position);
			const postText: string = labelText.substring(position + macros.length);

			this.currentServiceNode = Tag.render`
				<span class="disk-user-field-document-current-service">${
					this.userFieldControl.getCurrentDocumentService()?.name
				}</span>
			`;

			const label = Tag.render`
				<span>
					<span>${preText}</span>
					${this.currentServiceNode}
					<span>${postText}</span>
				</span>
			`;

			Dom.append(label, this.$refs['document-services']);
		}
	},
	methods: {
		createDocument(documentType: string): void
		{
			// TODO: load disk and disk.document extensions on demand
			if (!BX.Disk.getDocumentService() && BX.Disk.isAvailableOnlyOffice())
			{
				BX.Disk.saveDocumentService('onlyoffice');
			}
			else if (!BX.Disk.getDocumentService())
			{
				BX.Disk.saveDocumentService('l');
			}

			let newTab = null;
			if (documentType === 'board')
			{
				newTab = window.open('', '_blank');
			}

			if (BX.Disk.Document.Local.Instance.isSetWorkWithLocalBDisk() || documentType === 'board')
			{
				BX.Disk.Document.Local.Instance.createFile({ type: documentType }).then((response): void => {
					if (response.status === 'success')
					{
						this.uploader.addFile(
							`n${response.object.id}`,
							{
								name: response.object.name,
								preload: true,
							},
						);

						this.userFieldControl.showUploaderPanel();

						if (newTab !== null && response.openUrl)
						{
							newTab.location.href = response.openUrl;
						}
					}
				});
			}
			else
			{
				const createProcess = new BX.Disk.Document.CreateProcess({
					typeFile: documentType,
					serviceCode: BX.Disk.getDocumentService(),
					onAfterSave: (response, fileData): void => {
						if (response.status !== 'success')
						{
							return;
						}

						if (response.object)
						{
							this.uploader.addFile(
								`n${response.object.id}`,
								{
									name: response.object.name,
									size: response.object.size,
									preload: true,
								},
							);

							this.userFieldControl.showUploaderPanel();
						}
					},
				});

				createProcess.start();
			}
		},

		openMenu(): void
		{
			if (this.menu !== null)
			{
				this.menu.destroy();

				return;
			}

			this.menu = new Menu({
				bindElement: this.currentServiceNode,
				className: 'disk-user-field-settings-popup',
				angle: true,
				autoHide: true,
				offsetTop: 5,
				cacheable: false,
				items: this.getMenuItems(),
				events: {
					onDestroy: (): void => {
						this.menu = null;
					}
				}
			});

			this.menu.show();
		},

		getMenuItems(): Array
		{
			const items = [];
			const currentServiceCode = this.userFieldControl.getCurrentDocumentService()?.code;
			const services: Array<{ name: string, code: string }> = Object.values(
				this.userFieldControl.getDocumentServices(),
			);

			services.forEach((service: { name: string, code: string }): void => {
				items.push({
					text: service.name,
					className: currentServiceCode === service.code ? 'disk-user-field-item-checked' : 'disk-user-field-item-stub',
					onclick: (event, item: MenuItem): void => {
						BX.Disk.saveDocumentService(service.code);
						this.currentServiceNode.textContent = service.name;
						this.menu.close();
					},
				});
			});

			return items;
		},
	},
	// language=Vue
	template: `
		<div class="disk-user-field-panel">
			<div class="disk-user-field-panel-doc-wrap">
				<div class="disk-user-field-panel-card-box" @click="createDocument('docx')">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--doc">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_DOCX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument('xlsx')">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--xls">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_XLSX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument('pptx')">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--ppt">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_PPTX') }}</div>
					</div>
				</div>
				<div class="disk-user-field-panel-card-box" @click="createDocument('board')" v-if="isBoardsEnabled">
					<div class="disk-user-field-panel-card disk-user-field-panel-card--board">
						<div class="disk-user-field-panel-card-icon"></div>
						<div class="disk-user-field-panel-card-btn"></div>
						<div class="disk-user-field-panel-card-name">{{ getMessage('DISK_UF_WIDGET_CREATE_BOARD') }}</div>
					</div>
				</div>
			</div>
			<div class="disk-user-field-create-document-by-service" @click="openMenu" ref="document-services"></div>
		</div>
	`,
};
