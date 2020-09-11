import {PopupManager} from "main.popup";
import {Tag, Type, Loc, Text} from "main.core";
import {EventEmitter} from "main.core.events";
import {PresetMenu} from "./preset-menu";
import {Loader} from "main.loader";

export class EntityEditorRequisiteTooltip
{
	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._padding = BX.prop.getInteger(this._settings, 'padding', 20);
		this._showTimer = false;
		this._closeTimer = false;
		this._closeTimeout = BX.prop.getInteger(this._settings, 'closeTimeout', 700);
		this._showTimeout = BX.prop.getInteger(this._settings, 'showTimeout', 500);
		this._popupId = this._id + '_popup';

		this._isReadonly = BX.prop.getBoolean(this._settings, 'readonly', true);
		this._canChangeDefaultRequisite = BX.prop.getBoolean(this._settings, 'canChangeDefaultRequisite', true);

		this._bindElement = null;
		this._wrapper = null;
		this._requisiteList = null;

		this._isLoading = false;
		this._changeRequisistesHandler = this.onChangeRequisites.bind(this);

		this.presetMenu = new PresetMenu(this._id + '_preset_menu', BX.prop.getArray(this._settings, 'presets', []));
		this.presetMenu.subscribe('onSelect', this.onAddRequisite.bind(this));
	}

	static create(id, settings)
	{
		let self = new EntityEditorRequisiteTooltip();
		self.initialize(id, settings);
		return self;
	}

	setRequisites(requisiteList)
	{
		this._requisiteList = requisiteList;
		requisiteList.unsubscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
		requisiteList.subscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
		this.refreshLayout();
	}

	setBindElement(bindElement, wrapper)
	{
		this._bindElement = bindElement;
		if (!Type.isDomNode(wrapper))
		{
			wrapper = this._bindElement.parentNode ? this._bindElement.parentNode : null;
		}
		this._wrapper = wrapper;
	}

	setLoading(isLoading)
	{
		this._isLoading = !!isLoading;
		this.refreshLayout();
	}

	show()
	{
		this.cancelCloseDebounced();

		let success = false;
		if (this._requisiteList && !this._requisiteList.isEmpty())
		{
			let popup = PopupManager.create({
				id: this._popupId,
				cacheable: false,
				autoHide: true,
				padding: this._padding,
				contentPadding: 0,
				content: this.getLayout(),
				closeByEsc: true
			});
			popup.show();
			this.adjustPosition();
			success = true;
		}

		EventEmitter.emit(this, 'onShow', {success});
	}


	showDebounced(delayMultiplier = false)
	{
		delayMultiplier = parseInt(delayMultiplier) > 0 ? parseInt(delayMultiplier) : 1;
		this.cancelShowDebounced();
		this.cancelCloseDebounced();
		this._showTimer = setTimeout(this.show.bind(this), this._showTimeout * delayMultiplier);
	}

	cancelShowDebounced()
	{
		clearTimeout(this._showTimer);
	}

	closeDebounced(delayMultiplier = false)
	{
		this.cancelShowDebounced();
		delayMultiplier = parseInt(delayMultiplier) > 0 ? parseInt(delayMultiplier) : 1;
		this.cancelCloseDebounced();
		this._closeTimer = setTimeout(this.close.bind(this), this._closeTimeout * delayMultiplier);
	}

	cancelCloseDebounced()
	{
		clearTimeout(this._closeTimer);
	}

	adjustPosition()
	{
		let popup = PopupManager.getPopupById(this._popupId);
		if (!popup || !popup.isShown() || !Type.isDomNode(this._bindElement)
			|| !Type.isDomNode(this._wrapper))
		{
			return;
		}
		const wrapperRect = this._wrapper.getBoundingClientRect();
		const itemRect = this._bindElement.getBoundingClientRect();

		let offsetLeft = wrapperRect.width - (itemRect.left - wrapperRect.left);

		let offsetTop = itemRect.height / 2 + this._padding;
		let angleOffset = itemRect.height / 2;

		const popupWidth = popup.getPopupContainer().offsetWidth;
		const popupHeight = popup.getPopupContainer().offsetHeight;
		const popupBottom = itemRect.top + popupHeight;

		const clientWidth = document.documentElement.clientWidth;
		const clientHeight = document.documentElement.clientHeight;

		// let's try to fit a popup to the browser viewport
		const exceeded = popupBottom - clientHeight;
		if (exceeded > 0)
		{
			let roundOffset = Math.ceil(exceeded / itemRect.height) * itemRect.height;
			if (roundOffset > itemRect.top)
			{
				// it cannot be higher than the browser viewport.
				roundOffset -= Math.ceil((roundOffset - itemRect.top) / itemRect.height) * itemRect.height;
			}

			if (itemRect.bottom > (popupBottom - roundOffset))
			{
				// let's sync bottom boundaries.
				roundOffset -= itemRect.bottom - (popupBottom - roundOffset) + this._padding;
			}

			offsetTop += roundOffset;
			angleOffset += roundOffset + this._padding;
		}

		popup.setBindElement(this._bindElement);

		if ((wrapperRect.left + offsetLeft + popupWidth) <= clientWidth)
		{
			popup.setAngle({position: 'left', offset: angleOffset});

			offsetLeft += popup.angle ? popup.angle.element.offsetWidth : 0;
			offsetTop += popup.angle ? Math.ceil(popup.angle.element.offsetHeight / 2) : 0;
			popup.setOffset({offsetLeft: offsetLeft, offsetTop: -offsetTop});
		}
		else
		{
			popup.setAngle(true);
		}
		popup.adjustPosition();
	}

	close()
	{
		let popup = PopupManager.getPopupById(this._popupId);
		popup ? popup.close() : null;
		this.presetMenu.close();
	}

	removeDebouncedEvents()
	{
		this.cancelCloseDebounced();
		this.cancelShowDebounced();
	}

	refreshLayout()
	{
		let popup = PopupManager.getPopupById(this._popupId);
		if (popup)
		{
			popup.setContent(this.getLayout());
		}
	}

	getLayout()
	{
		if (this._isLoading)
		{
			let loader = new Loader();

			let container = Tag.render`<div class="crm-rq-wrapper crm-rq-wrapper-loading"></div>`;
			loader.show(container);
			return container;
		}
		let requisites = Type.isNull(this._requisiteList) ? [] : this._requisiteList.getList();
		let renderRequisiteEditButtonNode = (id) =>
		{
			if (this._isReadonly)
			{
				return Tag.render`
				<div class="crm-rq-org-requisite-btn-container">
					<span class="ui-link ui-link-secondary" onclick="${this.onEditRequisite.bind(this, id)}">${Loc.getMessage('REQUISITE_TOOLTIP_SHOW_DETAILS')}</span>
				</div>
				`;
			}
			else
			{
				return Tag.render`
				<div class="crm-rq-org-requisite-btn-container">
					<span class="ui-link ui-link-secondary" onclick="${this.onEditRequisite.bind(this, id)}">${Loc.getMessage('REQUISITE_TOOLTIP_EDIT')}</span>
					<span class="ui-link ui-link-secondary" data-requisite-id="${id}" onclick="${this.onDeleteRequisite.bind(this)}">${Loc.getMessage('REQUISITE_TOOLTIP_DELETE')}</span>
					<span class="ui-link ui-link-secondary" onclick="${this.onAddBankDetails.bind(this, id)}">${Loc.getMessage('REQUISITE_TOOLTIP_ADD_BANK_DETAILS')}</span>
				</div>
				`;
			}
		};

		let renderRequisiteBankDetails = (requisite, id) =>
		{
			let bandDetails = requisite.getBankDetails();
			let selectedBankDetailId = requisite.getSelectedBankDetailId();
			return bandDetails.length ?
				Tag.render`
					<div class="crm-rq-org-requisite-container">
						<div class="crm-rq-org-requisite-title">${Loc.getMessage('REQUISITE_TOOLTIP_BANK_DETAILS_TITLE')}</div>
						<div class="crm-rq-org-requisite-list">
							${bandDetails.map((bankDetail, index) => Tag.render`
								<label class="crm-rq-org-requisite-item" onclick="${this.onSetSelectedBankDetails.bind(this)}">
									<input type="radio" data-requisite-id="${id}" data-bankdetails-id="${index}" class="crm-rq-org-requisite-btn" ${requisite.isSelected() && (index === selectedBankDetailId) ? ' checked' : ''} ${this._canChangeDefaultRequisite ? '': ' disabled'}>
									<span class="crm-rq-org-requisite-info">${Text.encode(bankDetail.value)}</span>
								</label>`)}
						</div>
						${renderRequisiteEditButtonNode(id)}
					</div>`
				:
				renderRequisiteEditButtonNode(id)
		};

		return Tag.render`
		<div class="crm-rq-wrapper"
			onmouseenter="${this.onMouseEnter.bind(this)}"
			onmouseleave="${this.onMouseLeave.bind(this)}">
			<div class="crm-rq-org-list">
				${requisites.map((requisite, index) => Tag.render`
					<div class="crm-rq-org-item${requisite.isSelected() ? ' crm-rq-org-item-selected' : ''}" onclick="${this.onSetSelectedRequisite.bind(this, index)}">
						<div class="crm-rq-org-info-container">
							<div class="crm-rq-org-name">${Text.encode(requisite.getTitle())}</div>
							${requisite.getSubtitle().length ? Tag.render`<div class="crm-rq-org-description">${Text.encode(requisite.getSubtitle())}</div>` : ''}
							${renderRequisiteBankDetails(requisite, index)}
						</div>
					</div>
				`)}
			</div>
			${this._isReadonly ? '' :
			Tag.render`<div class="crm-rq-btn-container">
				<span class="crm-rq-add-rq" onclick="${this.onStartAddRequisite.bind(this)}">
					<span class="ui-btn crm-rq-btn ui-btn-icon-custom ui-btn-primary ui-btn-round"></span> ${Loc.getMessage('REQUISITE_TOOLTIP_ADD')}
				</span>
			</div>`}
		</div>`;
	}

	setSelectedRequisite(id, bankDetailId)
	{
		let newSelectedRequisite = this._requisiteList.getById(id);
		if (!newSelectedRequisite)
		{
			return false;
		}

		let selectedRequisite = this._requisiteList.getSelected();
		if (id === this._requisiteList.getSelectedId()) // selected the same requisite
		{
			let selectedBankDetails = selectedRequisite.getBankDetails();
			if (selectedBankDetails.length)
			{
				for (let index = 0; index < selectedBankDetails.length; index++)
				{
					let bankDetails = selectedBankDetails[index];
					if (Type.isObject(bankDetails) && bankDetails.selected)
					{
						if (index === bankDetailId || Type.isNull(bankDetailId))
						{
							return false; // selected same requisite and bank details
						}
					}
				}
			}
			else
			{
				return false; // requisite already selected and hasn't bank details
			}
		}
		EventEmitter.emit(this, 'onSetSelectedRequisite', {id, bankDetailId});

		return true;
	}

	onMouseEnter()
	{
		this.cancelCloseDebounced();
	}

	onMouseLeave()
	{
		if (!this.presetMenu.isShown())
		{
			this.closeDebounced(1.5);
		}
	}

	onStartAddRequisite(event)
	{
		event.stopPropagation();
		this.presetMenu.toggle(event.target);

	}

	onAddRequisite(event)
	{
		let data = event.getData();
		this.close();
		EventEmitter.emit(this, 'onAddRequisite', {
			presetId: data.value
		});
	}

	onEditRequisite(id, event)
	{
		event.stopPropagation();
		this.close();

		EventEmitter.emit(this, 'onEditRequisite', {id});
	}

	onDeleteRequisite(event)
	{
		event.stopPropagation();

		let element = event.target;
		if (Type.isDomNode(element))
		{
			let id = element.getAttribute('data-requisite-id');
			EventEmitter.emit(this, 'onDeleteRequisite', {id});
		}
	}

	onAddBankDetails(requisiteId, event)
	{
		event.stopPropagation();
		this.close();

		EventEmitter.emit(this, 'onAddBankDetails', {requisiteId});
	}

	onSetSelectedRequisite(id)
	{
		if (this._canChangeDefaultRequisite)
		{
			this.setSelectedRequisite(id, null);
		}
		return false;
	}

	onSetSelectedBankDetails(event)
	{
		event.stopPropagation();

		let element = event.target;
		if (this._canChangeDefaultRequisite && Type.isDomNode(element))
		{
			if (element.nodeName.toString().toLowerCase() !== 'input')
			{
				element = element.querySelector('input');
				if (!Type.isDomNode(element))
				{
					return;
				}
			}
			element.checked = false;
			let id = parseInt(element.getAttribute('data-requisite-id'));
			let bankDetailsId = parseInt(element.getAttribute('data-bankdetails-id'));
			this.setSelectedRequisite(id, bankDetailsId);
		}
		return false;
	}

	onChangeRequisites(event)
	{
		this.refreshLayout();
	}
}