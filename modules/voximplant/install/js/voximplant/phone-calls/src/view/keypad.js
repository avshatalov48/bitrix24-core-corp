import {Dom, Type, Text, Loc} from 'main.core';
import {Popup, Menu} from 'main.popup';
import {baseZIndex, nop} from './common';

const digits = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#']

type KeypadParams = {
	bindElement: ?HTMLElement,
	offsetTop: ?number,
	offsetLeft: ?number,
	anglePosition: ?string,
	angleOffset: ?number,
	history: string[],
	defaultLineId: string,
	lines: Object,
	availableLines: string[],
	callInterceptAllowed: boolean,
	hideDial: boolean,
	onButtonClick: () => void,
	onDial: () => void,
	onIntercept: () => void,
	onClose: () => void,
}

export class Keypad
{
	constructor(params: KeypadParams)
	{
		if (!Type.isPlainObject(params))
		{
			params = {}
		}

		this.bindElement = params.bindElement || null;
		this.offsetTop = params.offsetTop || 0;
		this.offsetLeft = params.offsetLeft || 0;
		this.anglePosition = params.anglePosition || '';
		this.angleOffset = params.angleOffset || 0;
		this.history = params.history || [];

		this.selectedLineId = params.defaultLineId;
		this.lines = params.lines || {}
		this.availableLines = params.availableLines || [];

		this.callInterceptAllowed = params.callInterceptAllowed === true;

		this.zIndex = baseZIndex + 200;

		//flags
		this.hideDial = (params.hideDial === true);
		this.plusEntered = false;

		this.callbacks = {
			onButtonClick: Type.isFunction(params.onButtonClick) ? params.onButtonClick : nop,
			onDial: Type.isFunction(params.onDial) ? params.onDial : nop,
			onIntercept: Type.isFunction(params.onIntercept) ? params.onIntercept : nop,
			onClose: Type.isFunction(params.onClose) ? params.onClose : nop
		}

		this.elements = {
			inputContainer: null,
			input: null,
			lineSelector: null,
			lineName: null,
			interceptButton: null,
			historyButton: null
		}
		this.plusKeyTimeout = null;

		this.popup = this.createPopup();
	}

	createPopup()
	{
		let popupOptions = {
			id: 'phone-call-view-popup-keypad',
			bindElement: this.bindElement,
			targetContainer: document.body,
			darkMode: true,
			closeByEsc: true,
			autoHide: true,
			zIndex: this.zIndex,
			content: this.render(),
			noAllPaddings: true,

			offsetTop: this.offsetTop,
			offsetLeft: this.offsetLeft,

			overlay: {
				backgroundColor: 'white',
				opacity: 0
			},
			events: {
				onPopupClose: this.onPopupClose.bind(this)
			}
		}

		if (this.anglePosition !== '')
		{
			popupOptions.angle = {
				position: this.anglePosition,
				offset: this.angleOffset
			}
		}

		return new Popup(popupOptions);
	}

	onPopupClose()
	{
		this.callbacks.onClose();
		if (this.popup)
		{
			this.popup.destroy();
		}
	}

	canSelectLine()
	{
		return this.availableLines.length > 1;
	}

	setSelectedLineId(lineId)
	{
		this.selectedLineId = lineId;

		if (this.elements.lineName)
		{
			this.elements.lineName.innerText = this.getLineName(lineId)
		}
	}

	getLineName(lineId)
	{
		return this.lines.hasOwnProperty(lineId) ? this.lines[lineId].SHORT_NAME : '';
	}

	render()
	{
		return Dom.create("div", {
			props: {className: "bx-messenger-calc-wrap"},
			children: [
				Dom.create("div", {
					props: {className: "bx-messenger-calc-body"}, children: [
						this.elements.inputContainer = Dom.create("div", {
							props: {className: 'bx-messenger-calc-panel'}, children: [
								Dom.create("span", {
									props: {className: "bx-messenger-calc-panel-delete"}, events: {
										click: this._onDeleteButtonClick.bind(this)
									}
								}),
								this.elements.input = Dom.create("input", {
									attrs: {
										'readonly': this.hideDial,
										type: "text",
										value: '',
										placeholder: Loc.getMessage(this.hideDial ? 'IM_PHONE_PUT_DIGIT' : 'IM_PHONE_PUT_NUMBER')
									},
									props: {className: "bx-messenger-calc-panel-input"},
									events: {
										keydown: this._onInputKeydown.bind(this),
										keyup: () => this._onAfterNumberChanged()
									}
								})
							]
						}),
						Dom.create("div", {
							props: {className: "bx-messenger-calc-btns-block"},
							children: digits.map(
								digit => renderNumber(digit, this._onKeyMouseDown.bind(this), this._onKeyMouseUp.bind(this))
							)
						})
					]
				}),
				this.hideDial ? null : Dom.create("div", {
					props: {className: "bx-messenger-call-btn-wrap"}, children: [
						this.elements.lineSelector = !this.canSelectLine() ? null : Dom.create("div", {
							props: {className: "im-phone-select-line"},
							children: [
								this.elements.lineName = Dom.create("span", {
									props: {className: "im-phone-select-line-name"},
									text: this.getLineName(this.selectedLineId)
								}),
								Dom.create("span", {props: {className: "im-phone-select-line-select"}})
							],
							events: {
								click: this._onLineSelectClick.bind(this)
							}
						}),
						Dom.create("span", {
							props: {className: "bx-messenger-call-btn-separate"},
							children: [
								Dom.create("span", {
									props: {className: "bx-messenger-call-btn"},
									children: [
										Dom.create("span", {
											props: {className: "bx-messenger-call-btn-text"},
											text: Loc.getMessage('IM_PHONE_CALL')
										})
									],
									events: {
										click: this._onDialButtonClick.bind(this)
									}
								}),
								this.elements.historyButton = Dom.create("span", {
									props: {className: "bx-messenger-call-btn-arrow"},
									events: {click: this._onShowHistoryButtonClick.bind(this)}
								})
							]
						}),
						this.elements.interceptButton = Dom.create("span", {
							props: {className: "im-phone-intercept-button" + (this.callInterceptAllowed ? "" : " im-phone-intercept-button-locked")},
							text: Loc.getMessage("IM_PHONE_CALL_VIEW_INTERCEPT"),
							events: {click: this._onInterceptButtonClick.bind(this)}
						})
					]
				})
			]
		});
	}

	_onInputKeydown(e: KeyboardEvent)
	{
		if (e.keyCode == 13)
		{
			this.callbacks.onDial({
				phoneNumber: this.elements.input.value,
				lineId: this.selectedLineId
			});
		}
		else if (e.keyCode == 37 || e.keyCode == 39 || e.keyCode == 8 || e.keyCode == 107 || e.keyCode == 46 || e.keyCode == 35 || e.keyCode == 36) // left, right, backspace, num plus, home, end
		{}
		else if (e.key === '+' || e.key === '#' || e.key === '*') // +
		{}
		else if ((e.keyCode == 67 || e.keyCode == 86 || e.keyCode == 65 || e.keyCode == 88) && (e.metaKey || e.ctrlKey)) // ctrl+v/c/a/x
		{}
		else if (e.keyCode >= 48 && e.keyCode <= 57 && !e.shiftKey) // 0-9
		{
			insertAtCursor(this.elements.input, e.key);

			e.preventDefault();
			this.callbacks.onButtonClick({
				key: e.key
			});
		}
		else if (e.keyCode >= 96 && e.keyCode <= 105 && !e.shiftKey) // extra 0-9
		{
			insertAtCursor(this.elements.input, e.key);

			e.preventDefault();
			this.callbacks.onButtonClick({
				key: e.key
			});
		}
		else if (!e.ctrlKey && !e.metaKey && !e.altKey)
		{
			e.preventDefault();
		}
	}

	_onAfterNumberChanged()
	{
		this.elements.inputContainer.classList.toggle('bx-messenger-calc-panel-active', this.elements.input.value.length > 0);

		this.elements.input.focus();
	}

	_onDeleteButtonClick()
	{
		this.elements.input.value = this.elements.input.value.substr(0, this.elements.input.value.length - 1);
		this._onAfterNumberChanged();
	}

	_onDialButtonClick()
	{
		this.callbacks.onDial({
			phoneNumber: this.elements.input.value,
			lineId: this.selectedLineId
		});
	}

	_onInterceptButtonClick()
	{
		this.callbacks.onIntercept({
			interceptButton: this.elements.interceptButton
		})
	}

	_onKeyMouseDown(key)
	{
		if (key === '0')
		{
			this.plusEntered = false;
			this.plusKeyTimeout = setTimeout(() =>
			{
				if (!this.elements.input.value.startsWith('+'))
				{
					this.plusEntered = true;
					this.elements.input.value = '+' + this.elements.input.value;
				}
			}, 500);
		}
	}

	_onKeyMouseUp(key)
	{
		if (key === '0')
		{
			clearTimeout(this.plusKeyTimeout);
			if (!this.plusEntered)
			{
				insertAtCursor(this.elements.input, '0');
			}

			this.plusEntered = false;
		}
		else
		{
			insertAtCursor(this.elements.input, key);
		}
		this._onAfterNumberChanged();
		this.callbacks.onButtonClick({
			key: key
		});
	}

	_onShowHistoryButtonClick()
	{
		let menuItems = [];

		if (!Type.isArray(this.history) || this.history.length === 0)
		{
			return;
		}

		this.history.forEach((phoneNumber, index) =>
		{
			menuItems.push({
				id: "history_" + index,
				text: Text.encode(phoneNumber),
				onclick: () =>
				{
					this.historySelectMenu.close();
					this.callbacks.onDial({
						phoneNumber: phoneNumber,
						lineId: this.selectedLineId
					});
				}
			})
		});

		this.historySelectMenu = new Menu(
			'phoneCallViewDialHistory',
			this.elements.historyButton,
			menuItems,
			{
				autoHide: true,
				offsetTop: 0,
				offsetLeft: 0,
				zIndex: baseZIndex + 300,
				bindOptions: {
					position: 'top'
				},
				angle: {
					offset: 33
				},
				closeByEsc: true,
				overlay: {
					backgroundColor: 'white',
					opacity: 0
				},
				events: {
					onPopupClose: () => this.historySelectMenu.destroy(),
					onPopupDestroy: () => this.historySelectMenu = null,
				}
			}
		);
		this.historySelectMenu.show();
	}

	_onLineSelectClick(e)
	{
		let menuItems = [];
		this.availableLines.forEach((lineId) =>
		{
			menuItems.push({
				id: "selectLine_" + lineId,
				text: Text.encode(this.getLineName(lineId)),
				onclick: () =>
				{
					this.lineSelectMenu.close();
					this.setSelectedLineId(lineId);
				}
			})
		});

		this.lineSelectMenu = new Menu(
			'phoneCallViewSelectLine',
			this.elements.lineSelector,
			menuItems,
			{
				autoHide: true,
				zIndex: this.zIndex + 100,
				closeByEsc: true,
				bindOptions: {
					position: 'top'
				},
				offsetLeft: 35,
				angle: {
					offset: 33
				},
				overlay: {
					backgroundColor: 'white',
					opacity: 0
				},
				maxHeight: 600,
				events: {
					onPopupClose: () => this.lineSelectMenu.destroy(),
					onPopupDestroy: () => this.lineSelectMenu = null
				}
			}
		);
		this.lineSelectMenu.show();
	}

	show()
	{
		if (this.popup)
		{
			this.popup.show();
			this.elements.input.focus();
		}
	}

	close()
	{
		if (this.lineSelectMenu)
		{
			this.lineSelectMenu.destroy();
		}

		if (this.historySelectMenu)
		{
			this.historySelectMenu.destroy();
		}

		if (this.popup)
		{
			this.popup.close();
		}
	}

	destroy()
	{
		if (this.lineSelectMenu)
		{
			this.lineSelectMenu.destroy();
		}

		if (this.historySelectMenu)
		{
			this.historySelectMenu.destroy();
		}

		if (this.popup)
		{
			this.popup.destroy();
		}

		this.popup = null;
	}
}

function renderNumber(number: string, onMouseDown, onMouseUp)
{
	let classSuffix;
	if (number == '*')
	{
		classSuffix = '10';
	}
	else if (number == '#')
	{
		classSuffix = '11';
	}
	else
	{
		classSuffix = number;
	}
	return Dom.create("span", {
		dataset: {'digit': number},
		props: {className: "bx-messenger-calc-btn bx-messenger-calc-btn-" + classSuffix},
		children: [
			Dom.create("span", {props: {className: 'bx-messenger-calc-btn-num'}})
		],
		events: {
			mousedown: (e) => onMouseDown(e.currentTarget.dataset.digit),
			mouseup: (e) => onMouseUp(e.currentTarget.dataset.digit)
		}
	});
}

function insertAtCursor(inputElement, value)
{
	if (inputElement.selectionStart || inputElement.selectionStart == '0')
	{
		var startPos = inputElement.selectionStart;
		var endPos = inputElement.selectionEnd;
		inputElement.value = inputElement.value.substring(0, startPos)
			+ value
			+ inputElement.value.substring(endPos, inputElement.value.length);
		inputElement.selectionStart = startPos + value.length;
		inputElement.selectionEnd = startPos + value.length;
	}
	else
	{
		inputElement.value += value;
	}
}