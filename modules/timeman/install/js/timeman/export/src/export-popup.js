import {PopupManager} from 'main.popup';
import {Loc, Tag, Text} from 'main.core';
import {Button} from 'ui.buttons';
import {Export} from './export';
import {ExportState} from './export-state';

import './css/export.css';

export class ExportPopup
{
	constructor(options)
	{
		options = {...{
			exportManager: null,
			exportState: null
		}, ...options};

		this.exportManager = (options.exportManager instanceof Export ? options.exportManager : null);
		this.exportState = (options.exportState instanceof ExportState ? options.exportState : new ExportState());

		this.popup = null;
		this.popupIsShown = false;

		this.popupContentId = 'timeman-export-popup-content';

		this.createProgressBar();

		this.subscribeToState();
	}

	createPopup()
	{
		this.popup = PopupManager.create(
			Text.getRandom(),
			null,
			{
				autoHide: false,
				bindOptions: { forceBindPosition: false },
				buttons: this.getPopupButtons(),
				closeByEsc: false,
				closeIcon: false,
				content: this.getPopupContent(),
				draggable: true,
				events: {
					onPopupClose: this.onPopupClose.bind(this)
				},
				offsetLeft: 0,
				offsetTop: 0,
				titleBar: Loc.getMessage('TIMEMAN_EXPORT_POPUP_TITLE_EXCEL'),
				overlay: true
			}
		);
	}

	showPopup()
	{
		if (this.popupIsShown || !this.popup)
		{
			return;
		}

		this.popup.adjustPosition();

		if (!this.popup.isShown())
		{
			this.popup.show();
		}

		this.popupIsShown = this.popup.isShown();
	}

	adjustPosition()
	{
		if (this.popup)
		{
			this.popup.adjustPosition();
		}
	}

	createProgressBar()
	{
		/* eslint-disable */

		BX.loadExt('ui.progressbar').then(() =>
		{
			this.progressBar = new BX.UI.ProgressBar({
				statusType: BX.UI.ProgressBar.Status.COUNTER,
				size: BX.UI.ProgressBar.Size.LARGE,
				fill: true
			});

			this.progressBarContainer = Tag.render`
				<div class="timeman-export-progress-bar-container">
					${this.progressBar.getContainer()}
				</div>
			`;

			this.progressBarHide();
		});

		/* eslint-enable */
	}

	subscribeToState()
	{
		this.exportState
			.subscribe('running', () => {
				this.hideCloseButton();
			})
			.subscribe('intermediate', () => {
				this.showCloseButton();
			})
			.subscribe('stopped', () => {
				this.showCloseButton();
			})
			.subscribe('completed', () => {
				this.showCloseButton();
				this.progressBarHide();
			})
			.subscribe('error', () => {
				this.showCloseButton();
				this.progressBarSetDanger();
			});
	}

	showCloseButton()
	{
		if (this.buttons['close'] !== 'undefined')
		{
			this.buttons['close'].button.style.display = '';
		}
	}

	hideCloseButton()
	{
		if (this.buttons['close'] !== 'undefined')
		{
			this.buttons['close'].button.style.display = 'none';
		}
	}

	progressBarShow()
	{
		if (this.progressBarContainer)
		{
			this.progressBarContainer.style.display = '';
		}
	}

	progressBarHide()
	{
		if (this.progressBarContainer)
		{
			this.progressBarContainer.style.display = 'none';
		}
	}

	progressBarSetDanger()
	{
		if (this.progressBar)
		{
			// eslint-disable-next-line
			this.progressBar.setColor(BX.UI.ProgressBar.Color.DANGER);
		}
	}

	getPopupButtons()
	{
		this.buttons = {};

		//todo stop

		this.buttons['close'] = new Button({
			text: Loc.getMessage('TIMEMAN_EXPORT_POPUP_CLOSE'),
			color: Button.Color.LINK,
			events: { click : this.handleCloseButtonClick.bind(this) }
		});

		return [
			this.buttons['close']
		];
	}

	getDownloadButton(text, downloadUrl)
	{
		return new Button({
			text: text,
			color: Button.Color.SUCCESS,
			icon: Button.Icon.DOWNLOAD,
			tag: Button.Tag.LINK,
			link: downloadUrl
		});
	}

	getDeleteButton(text)
	{
		return new Button({
			text: text,
			icon: Button.Icon.REMOVE,
			onclick: (btn, event) => {
				this.exportManager.clearRequest();
			}
		});
	}

	setPopupContent(data)
	{
		const popupContent = document.getElementById(this.popupContentId);
		popupContent.innerHTML = data['SUMMARY_HTML'];
		if (data['DOWNLOAD_LINK'])
		{
			popupContent.appendChild(this.renderFinalButtons(data));
		}
	}

	getPopupContent()
	{
		this.popupContent = `<div id="${this.popupContentId}"></div>`
		return Tag.render`
			<div>
				${this.popupContent}
				${this.progressBarContainer}
			</div>
		`;
	}

	renderFinalButtons(data)
	{
		return Tag.render`
			<div class="timeman-export-content-final-buttons">
				${this.getDownloadButton(data['DOWNLOAD_LINK_NAME'], data['DOWNLOAD_LINK']).render()}
				${this.getDeleteButton(data['CLEAR_LINK_NAME']).render()}
			</div>
		`;
	}

	onPopupClose()
	{
		if (this.popup)
		{
			this.popup.destroy();
			this.popup = null;
		}

		this.popupIsShown = false;
	}

	handleCloseButtonClick()
	{
		if (this.popup && !this.exportState.isRunning())
		{
			this.popup.close();
		}
	}

	setProgressBar(processedItems, totalItems)
	{
		if (totalItems)
		{
			if (this.progressBar)
			{
				this.progressBarShow();
				this.progressBar.setMaxValue(totalItems);
				this.progressBar.update(processedItems);
			}
		}
		else
		{
			this.progressBarHide();
		}
	}
}