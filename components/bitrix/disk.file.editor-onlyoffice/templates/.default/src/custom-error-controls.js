import {Dom, Loc, Tag, Text} from "main.core";
import {Button} from "ui.buttons";

export default class CustomErrorControl
{
	showWhenTooLarge(fileName: string, container: HTMLElement, targetNode: HTMLElement, linkToDownload: string): void
	{
		const containerClass = 'disk-fe-office-warning--popup';

		const downloadButton = new Button({
			text: Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_DOWNLOAD'),
			round: true,
			tag: Button.Tag.LINK,
			link: linkToDownload,
			color: Button.Color.SUCCESS,
			className: 'disk-fe-office-warning-btn',
			props: {
				target: '_blank',
			}
		})

		const errorControl = Tag.render`
			<div class="disk-fe-office-warning-wrap">
				<div class="disk-fe-office-warning-overlay"></div>
				<div class="disk-fe-office-warning-box">
					<div class="disk-fe-office-warning-icon"></div>
					<div class="disk-fe-office-warning-title">${Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CUSTOM_ERROR_LARGE_FILE_TITLE')}</div>
					<div class="disk-fe-office-warning-file-name">${Text.encode(fileName)}</div>
					<div class="disk-fe-office-warning-desc">${Loc.getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CUSTOM_ERROR_LARGE_FILE_DESCR')}</div>
					${downloadButton.render()}
				</div>
			</div>
		`;

		Dom.addClass(container, containerClass);
		Dom.prepend(errorControl, targetNode);
	}
}
