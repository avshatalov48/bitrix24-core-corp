import {Dom, Loc, Tag, Text} from "main.core";
import {Button} from "ui.buttons";
import type {CommonWarningOptions} from "./types";

export default class CustomErrorControl
{
	showWhenTooLarge(fileName: string, container: HTMLElement, targetNode: HTMLElement, linkToDownload: string): void
	{
		this.showCommonWarning({
			container: container,
			targetNode: targetNode,
			title: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CUSTOM_ERROR_LARGE_FILE_TITLE'),
			description: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CUSTOM_ERROR_LARGE_FILE_DESCR'),
			fileName: fileName,
			linkToDownload: linkToDownload,
		});
	}

	showWhenNotFound(container: HTMLElement, targetNode: HTMLElement): void
	{
		this.showCommonWarning({
			container: container,
			targetNode: targetNode,
			title: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CUSTOM_ERROR_FILE_TITLE'),
			description: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CUSTOM_ERROR_RIGHTS_OR_NOT_FOUND_DESCR'),
		});
	}

	showCommonWarning(options: CommonWarningOptions): void
	{
		const containerClass = 'disk-fe-office-warning--popup';

		let fileNameNode = '';
		if (options.fileName)
		{
			fileNameNode = Tag.render`<div class="disk-fe-office-warning-file-name">${Text.encode(options.fileName)}</div>`;
		}

		let downloadButtonNode = '';
		if (options.linkToDownload)
		{
			const downloadButton = new Button({
				text: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_DOWNLOAD'),
				round: true,
				tag: Button.Tag.LINK,
				link: options.linkToDownload,
				color: Button.Color.SUCCESS,
				className: 'disk-fe-office-warning-btn',
				props: {
					target: '_blank',
				}
			});
			downloadButtonNode = downloadButton.render();
		}

		const errorControl = Tag.render`
			<div class="disk-fe-office-warning-wrap">
				<div class="disk-fe-office-warning-overlay"></div>
				<div class="disk-fe-office-warning-box">
					<div class="disk-fe-office-warning-icon"></div>
					<div class="disk-fe-office-warning-title">${options.title}</div>
					${fileNameNode}
					<div class="disk-fe-office-warning-desc">${options.description}</div>
					${downloadButtonNode}
				</div>
			</div>
		`;

		Dom.addClass(options.container, containerClass);
		Dom.prepend(errorControl, options.targetNode);
	}
}
