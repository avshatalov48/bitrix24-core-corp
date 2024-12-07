/* eslint-disable */

import { Editor, FilledPlaceholder } from 'crm.template.editor';
import { ajax as Ajax, Dom, Loc, Tag, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import WithEditor from './../witheditor';

/** @memberof BX.Crm.Timeline.MenuBar */
export default class Sms extends WithEditor
{
	tplEditor: ?Editor;
	placeholders: string[];
	filledPlaceholders: FilledPlaceholder[];

	isFetchedConfig: boolean = false;
	fetchConfigPromise: ?Promise = null;

	/**
	 * @override
	 * */
	createLayout(): HTMLElement
	{
		return Tag.render`<div class="crm-entity-stream-content-new-detail crm-entity-stream-content-sms --skeleton --hidden"></div>`;
	}

	#renderEditor(): HTMLElement
	{
		const config = this.getSetting('smsConfig', {})
		const enableSalesCenter = BX.prop.getBoolean(config, 'isSalescenterEnabled', false);
		const enableDocuments = BX.prop.getBoolean(config, 'isDocumentsEnabled', false);
		const enableFiles = this.getSetting('enableFiles', false);

		this._saveButton = Tag.render`<button onclick="${this.onSaveButtonClick.bind(this)}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round" >${Loc.getMessage('CRM_TIMELINE_SEND')}</button>`;
		this._cancelButton = Tag.render`<span onclick="${this.onCancelButtonClick.bind(this)}"  class="ui-btn ui-btn-xs ui-btn-link">${Loc.getMessage('CRM_TIMELINE_CANCEL_BTN')}</span>`;
		this._input = Tag.render`<textarea class="crm-entity-stream-content-new-sms-textarea" rows='1' placeholder="${Loc.getMessage('CRM_TIMELINE_SMS_ENTER_MESSAGE')}"></textarea>`;

		return Tag.render`<div class="crm-entity-stream-content-sms-buttons-container">
			${enableSalesCenter ? Tag.render`
				<div class="crm-entity-stream-content-sms-button" data-role="salescenter-starter">
					<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${Loc.getMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')}</div>
				</div>` : null}
			${enableFiles ? Tag.render`
				<div class="crm-entity-stream-content-sms-button" data-role="sms-file-selector">
					<div class="crm-entity-stream-content-sms-file-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${Loc.getMessage('CRM_TIMELINE_SMS_SEND_FILE')}</div>
				</div>` : null}
			${enableDocuments ? Tag.render`
				<div class="crm-entity-stream-content-sms-button" data-role="sms-document-selector">
					<div class="crm-entity-stream-content-sms-document-icon"></div>
					<div class="crm-entity-stream-content-sms-button-text">${Loc.getMessage('CRM_TIMELINE_SMS_SEND_DOCUMENT')}</div>
				</div>` : null}
				<div class="crm-entity-stream-content-sms-detail-toggle" data-role="sms-detail-switcher">
					${Loc.getMessage('CRM_TIMELINE_DETAILS')}
				</div>
			</div>
			<div class="crm-entity-stream-content-sms-conditions-container hidden" data-role="sms-detail">
				<div class="crm-entity-stream-content-sms-conditions">
					<div class="crm-entity-stream-content-sms-conditions-text">
						${Loc.getMessage('CRM_TIMELINE_SMS_SENDER')}
						<a href="#" data-role="sender-selector">sender</a><span data-role="from-container">${Loc.getMessage('CRM_TIMELINE_SMS_FROM')}
						<a data-role="from-selector" href="#">from_number</a></span>
						<span data-role="client-container"> ${Loc.getMessage('CRM_TIMELINE_SMS_TO')}
						<a data-role="client-selector" href="#">client_caption</a> <a data-role="to-selector" href="#">to_number</a></span>
					</div>
				</div>
			</div>
			${this._input}
			${this.#renderTemplatesContainer()}
			${this.#renderFilesSelector()}

			<div class="crm-entity-stream-content-new-sms-btn-container">
				${this._saveButton}
				${this._cancelButton}

				<div class="crm-entity-stream-content-sms-symbol-counter" data-role="message-length-counter-wrap">
					${Loc.getMessage('CRM_TIMELINE_SMS_SYMBOLS')}
					<span class="crm-entity-stream-content-sms-symbol-counter-number" data-role="message-length-counter" data-length-max="200">0</span>
					${Loc.getMessage('CRM_TIMELINE_SMS_SYMBOLS_FROM')}
					<span class="crm-entity-stream-content-sms-symbol-counter-number">200</span>
				</div>
			</div>
		`;
	}

	#renderSetupText(): HTMLElement
	{
		const enableSalesCenter = BX.prop.getBoolean(this.getSetting('smsConfig', {}), 'isSalescenterEnabled', false);

		return Tag.render`<div class="crm-entity-stream-content-sms-conditions-container">
			<div class="crm-entity-stream-content-sms-conditions">
				<div class="crm-entity-stream-content-sms-conditions-text">
					<strong>${Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_1')}</strong><br>
					${Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_2')}<br>
					${Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_TEXT_3_MSGVER_1')}
				</div>
			</div>
		</div>
		<div class="crm-entity-stream-content-new-sms-btn-container">
			<a href="#" data-role="sender-selector" target="_top" class="crm-entity-stream-content-new-sms-connect-link">${Loc.getMessage('CRM_TIMELINE_SMS_MANAGE_URL')}</a>
			${enableSalesCenter ? Tag.render`<div class="crm-entity-stream-content-sms-salescenter-container-absolute" data-role="salescenter-starter">
	<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
	<div class="crm-entity-stream-content-sms-button-text">${Loc.getMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')}</div>
</div>` : null}
		</div>`;
	}

	#renderTemplatesContainer(): HTMLElement
	{
		this._templatesContainer = Tag.render`<div class="crm-entity-stream-content-new-sms-templates">
				<div class="ui-ctl-label-text">
					${Loc.getMessage('CRM_TIMELINE_SMS_TEMPLATE_LIST_TITLE')}<span class="ui-hint" data-role="hint"><span class="ui-hint-icon"></span></span>
				</div>
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100" data-role="template-selector">
					<div class="ui-ctl-element" data-role="template-title"></div>
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				</div>
				<div class="crm-entity-stream-content-new-sms-preview" data-role="preview"></div>
			</div>`;

		return this._templatesContainer;
	}

	#renderFilesSelector(): ?HTMLElement
	{
		const config = this.getSetting('smsConfig', {});

		const showFiles = this.getSetting('showFiles', false);
		const enableFilesExternalLink = BX.prop.getBoolean(config, 'isFilesExternalLinkEnabled', false);

		if (enableFilesExternalLink)
		{
			const fileInputPrefix = 'crm-' + this.getEntityTypeId() + '-' + this.getEntityId();
			const fileInputName = fileInputPrefix + '-sms-files';
			const fileUploaderInputName = fileInputPrefix + '-sms-files-uploader';
			const fileUploaderZoneId = 'diskuf-selectdialog-' + fileInputPrefix;

			return Tag.render`<div class="crm-entity-stream-content-sms-file-uploader-zone" data-role="sms-file-upload-zone" data-node-id="${fileInputPrefix}">
				<div id="${fileUploaderZoneId}" class="diskuf-files-entity diskuf-selectdialog bx-disk">
					<div class="diskuf-files-block checklist-loader-files">
						<div class="diskuf-placeholder">
							<table class="files-list">
								<tbody class="diskuf-placeholder-tbody"></tbody>
							</table>
						</div>
					</div>
					<div class="diskuf-extended">
						<input type="hidden" name="${fileInputName}[]" value="" />
					</div>
					<div class="diskuf-extended-item">
						<label for="${fileUploaderInputName}" data-role="sms-file-upload-label"></label>
						<input class="diskuf-fileUploader" id="${fileUploaderInputName}" type="file" data-role="sms-file-upload-input" />
					</div>
					<div class="diskuf-extended-item">
						<span class="diskuf-selector-link" data-role="sms-file-selector-bitrix">
						</span>
					</div>
				</div>
			</div>`;
		}
		if (showFiles)
		{
			return Tag.render`<div class="crm-entity-stream-content-sms-file-external-link-popup" data-role="sms-file-external-link-disabled">
				<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-container">
					<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-inner">
						<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-desc">
							<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-img">
								<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-img-lock"></div>
							</div>
							<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-desc-text">
								${Loc.getMessage('CRM_TIMELINE_SMS_FILE_EXTERNAL_LINK_FEATURE')}
							</div>
						</div>
					</div>
				</div>
			</div>`;
		}

		return null;
	}

	doInitialize()
	{
		this._isRequestRunning = false;
		this._isLocked = false;

		this._senderId = null;
		this._from = null;
		this._commEntityTypeId = null;
		this._commEntityId = null;
		this._to = null;

		this._fromList = [];
		this._toList = [];
		this._defaults = {};
		this._communications = [];

		this._menu = null;
		this._isMenuShown = false;
		this._shownMenuId = null;
		this._documentSelector = null;
		this._source = null;

		this._paymentId = null;
		this._shipmentId = null;

		this._compilationProductIds = [];

		this._templateId = null;
		this.templateOriginalId = null;
		this._templateFieldHintNode = null;
		this._templateSelectorNode = null;
		this._templateTemplateTitleNode = null;
		this._templatePreviewNode = null;
		this._templateSelectorMenuId = 'CrmTimelineSmsEditorTemplateSelector';
		this._templateFieldHintHandler = BX.delegate(this.onTemplateHintIconClick, this);
		this._templateSeletorClickHandler = BX.delegate(this.onTemplateSelectClick, this);
		this._selectTemplateHandler = BX.delegate(this.onSelectTemplate, this);

		this._serviceUrl = BX.util.remove_url_param(
			this.getSetting("serviceUrl", ""),
			['sessid', 'site']
		);

		const config = this.getSetting( 'smsConfig', {});

		this._canUse = BX.prop.getBoolean(config, "canUse", false);
		this._canSendMessage = BX.prop.getBoolean(config, "canSendMessage", false);
		this._manageUrl = BX.prop.getString(config, "manageUrl", '');
		this._senders = BX.prop.getArray(config, "senders", []);
		this._defaults = BX.prop.getObject(config, "defaults", {senderId:null,from:null});
		this._communications = BX.prop.getArray(config, "communications", []);
		this._isSalescenterEnabled = BX.prop.getBoolean(config, "isSalescenterEnabled", false);
		this._isDocumentsEnabled = BX.prop.getBoolean(config, "isDocumentsEnabled", false);
		if(this._isDocumentsEnabled)
		{
			this._documentsProvider = BX.prop.getString(config, "documentsProvider", '');
			this._documentsValue = BX.prop.getString(config, "documentsValue", '');
		}
		this._isFilesEnabled = BX.prop.getBoolean(config, "isFilesEnabled", false);
		if(this._isFilesEnabled)
		{
			this._diskUrls = BX.prop.getObject(config, "diskUrls");
			this._isFilesExternalLinkEnabled = BX.prop.getBoolean(config, "isFilesExternalLinkEnabled", true);
		}

		this._senderSelectorNode = this.getContainer().querySelector('[data-role="sender-selector"]');
		this._fromContainerNode = this.getContainer().querySelector('[data-role="from-container"]');
		this._fromSelectorNode = this.getContainer().querySelector('[data-role="from-selector"]');
		this._clientContainerNode = this.getContainer().querySelector('[data-role="client-container"]');
		this._clientSelectorNode = this.getContainer().querySelector('[data-role="client-selector"]');
		this._toSelectorNode = this.getContainer().querySelector('[data-role="to-selector"]');
		this._messageLengthCounterWrapperNode = this.getContainer().querySelector('[data-role="message-length-counter-wrap"]');
		this._messageLengthCounterNode = this.getContainer().querySelector('[data-role="message-length-counter"]');
		this._salescenterStarter = this.getContainer().querySelector('[data-role="salescenter-starter"]');
		this._smsDetailSwitcher = this.getContainer().querySelector('[data-role="sms-detail-switcher"]');
		this._smsDetail = this.getContainer().querySelector('[data-role="sms-detail"]');
		this._documentSelectorButton = this.getContainer().querySelector('[data-role="sms-document-selector"]');
		this._fileSelectorButton = this.getContainer().querySelector('[data-role="sms-file-selector"]');
		this._fileUploadZone = this.getContainer().querySelector('[data-role="sms-file-upload-zone"]');
		this._fileUploadLabel = this.getContainer().querySelector('[data-role="sms-file-upload-label"]');
		this._fileSelectorBitrix = this.getContainer().querySelector('[data-role="sms-file-selector-bitrix"]');
		this._fileExternalLinkDisabledContent = this.getContainer().querySelector('[data-role="sms-file-external-link-disabled"]');

		if (this._templatesContainer)
		{
			this._templateFieldHintNode = this._templatesContainer.querySelector('[data-role="hint"]');
			this._templateSelectorNode = this._templatesContainer.querySelector('[data-role="template-selector"]');
			this._templateTemplateTitleNode = this._templatesContainer.querySelector('[data-role="template-title"]');
			this._templatePreviewNode = this._templatesContainer.querySelector('[data-role="preview"]');
		}
		if (this._templateFieldHintNode)
		{
			BX.bind(this._templateFieldHintNode, "click", this._templateFieldHintHandler);
		}
		if (this._templateSelectorNode)
		{
			BX.bind(this._templateSelectorNode, "click", this._templateSeletorClickHandler);
		}

		if (this._canUse && this._senders.length > 0)
		{
			this.initSenderSelector();
		}
		if (this._canUse && this._canSendMessage)
		{
			this.initDetailSwitcher();
			this.initFromSelector();
			this.initClientContainer();
			this.initClientSelector();
			this.initToSelector();
			this.initMessageLengthCounter();
			this.setMessageLengthCounter();
			if(this._isDocumentsEnabled)
			{
				this.initDocumentSelector();
			}
			if(this._isFilesEnabled)
			{
				this.initFileSelector();
			}
		}

		this.#subscribeToReceiversChanges();

		if(this._isSalescenterEnabled)
		{
			this.initSalescenterApplication();
		}
	}

	initDetailSwitcher()
	{
		BX.bind(this._smsDetailSwitcher, 'click', function()
		{
			if(this._smsDetail.classList.contains('hidden'))
			{
				this._smsDetail.classList.remove('hidden');
				this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_COLLAPSE');
			}
			else
			{
				this._smsDetail.classList.add('hidden');
				this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_DETAILS');
			}
		}.bind(this));
	}

	initSenderSelector()
	{
		const defaultSenderId = this._defaults.senderId;
		let defaultSender = this._senders[0].canUse ? this._senders[0] : null;
		let restSender = null;
		const menuItems = [];
		const handler = this.onSenderSelectorClick.bind(this);

		for (let i = 0; i < this._senders.length; ++i)
		{
			if (this._senders[i].canUse && this._senders[i].fromList.length && (this._senders[i].id === defaultSenderId || !defaultSender))
			{
				defaultSender = this._senders[i];
			}

			if (this._senders[i].id === 'rest')
			{
				restSender = this._senders[i];
				continue;
			}

			menuItems.push({
				text: this._senders[i].name,
				sender: this._senders[i],
				onclick: handler,
				className: (!this._senders[i].canUse || !this._senders[i].fromList.length)
					? 'crm-timeline-popup-menu-item-disabled menu-popup-no-icon' : ''
			});
		}

		if (restSender)
		{
			if (restSender.fromList.length > 0)
			{
				menuItems.push({delimiter: true});
				for (let i = 0; i < restSender.fromList.length; ++i)
				{
					menuItems.push({
						text: restSender.fromList[i].name,
						sender: restSender,
						from: restSender.fromList[i],
						onclick: handler
					});
				}
			}
			menuItems.push({delimiter: true}, {
				text: BX.message('CRM_TIMELINE_SMS_REST_MARKETPLACE'),
				href: BX.message('MARKET_BASE_PATH') + 'category/crm_robot_sms/',
				target: '_blank'
			});
		}

		if (defaultSender)
		{
			this.setSender(defaultSender);
		}

		BX.bind(this._senderSelectorNode, 'click', this.openMenu.bind(this, 'sender', this._senderSelectorNode, menuItems));
	}

	onSenderSelectorClick(e, item)
	{
		if (item.sender)
		{
			if (!item.sender.canUse || !item.sender.fromList.length)
			{
				const url = BX.Uri.addParam(item.sender.manageUrl, {'IFRAME': 'Y'});
				const slider = BX.SidePanel.Instance.getTopSlider();
				const options = {
					events: {
						onClose: function () {
							if (slider)
							{
								slider.reload();
							}
						},
						onCloseComplete: function () {
							if (!slider)
							{
								document.location.reload();
							}
						}
					}
				};
				if (item.sender.id === 'ednaru')
				{
					options.width = 700;
				}
				BX.SidePanel.Instance.open(url, options);
				return;
			}

			this.setSender(item.sender, true);
			const from = item.from ? item.from : item.sender.fromList[0];
			this.setFrom(from, true);
		}
		this._menu.close();
	}

	setSender(sender, setAsDefault)
	{
		this._senderId = sender.id;
		this._fromList = sender.fromList;
		this._senderSelectorNode.textContent = sender.shortName ? sender.shortName : sender.name;

		this._templateId = null;
		this.templateOriginalId = null;
		if (sender.isTemplatesBased)
		{
			this.showNode(this._templatesContainer);
			this.hideNode(this._messageLengthCounterWrapperNode);
			this.hideNode(this._fileSelectorButton);
			this.hideNode(this._documentSelectorButton);
			this.hideNode(this._input);
			this.toggleTemplateSelectAvailability();
			this.toggleSaveButton();
			this._hideButtonsOnBlur = false;
			this.onFocus();
		}
		else
		{
			this.hideNode(this._templatesContainer);
			this.showNode(this._messageLengthCounterWrapperNode);
			this.showNode(this._fileSelectorButton);
			this.showNode(this._documentSelectorButton);
			this.showNode(this._input);
			this.setMessageLengthCounter();
			this._hideButtonsOnBlur = true;
		}

		const visualFn = sender.id === 'rest' ? 'hide' : 'show';
		BX[visualFn](this._fromContainerNode);

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "senderId", this._senderId);
		}
	}

	initFromSelector()
	{
		if (this._fromList.length > 0)
		{
			const defaultFromId = this._defaults.from || this._fromList[0].id;
			let defaultFrom = null;
			for (let i = 0; i < this._fromList.length; ++i)
			{
				if (this._fromList[i].id === defaultFromId || !defaultFrom)
				{
					defaultFrom = this._fromList[i];
				}
			}
			if (defaultFrom)
			{
				this.setFrom(defaultFrom);
			}
		}

		BX.bind(this._fromSelectorNode, 'click', this.onFromSelectorClick.bind(this));
	}

	onFromSelectorClick(e)
	{
		const menuItems = [];
		const handler = this.onFromSelectorItemClick.bind(this);

		for (let i = 0; i < this._fromList.length; ++i)
		{
			menuItems.push({
				text: this._fromList[i].name,
				from: this._fromList[i],
				onclick: handler
			});
		}

		this.openMenu('from_'+this._senderId, this._fromSelectorNode, menuItems, e);
	}

	onFromSelectorItemClick(e, item)
	{
		if (item.from)
		{
			this.setFrom(item.from, true);
		}
		this._menu.close();
	}

	setFrom(from, setAsDefault)
	{
		this._from = from.id;

		if (this._senderId === 'rest')
		{
			this._senderSelectorNode.textContent = from.name;
		}
		else
		{
			this._fromSelectorNode.textContent = from.name;
		}

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "from", this._from);
		}
	}

	#subscribeToReceiversChanges(): void
	{
		EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', (event: BaseEvent) => {
			const { item, current } = event.getData();

			if (this.getEntityTypeId() !== item?.entityTypeId || this.getEntityId() !== item?.entityId)
			{
				return;
			}

			if (!Type.isArray(current))
			{
				return;
			}

			const phoneReceivers = current.filter(receiver => receiver.address.typeId === 'PHONE');

			const newCommunications: {[addressSourceHash: string]: Object} = {};
			for (const receiver of phoneReceivers)
			{
				let communication = newCommunications[receiver.addressSource.hash];

				if (!communication)
				{
					communication = {
						entityTypeId: receiver.addressSource.entityTypeId,
						entityTypeName: BX.CrmEntityType.resolveName(receiver.addressSource.entityTypeId),
						entityId: receiver.addressSource.entityId,
						caption: receiver.addressSourceData?.title,
						phones: [],
					};
				}

				communication.phones.push({
					type: receiver.address.typeId,
					value: receiver.address.value,
					valueFormatted: receiver.address.valueFormatted,
				});

				newCommunications[receiver.addressSource.hash] = communication;
			}

			this._communications = Object.values(newCommunications);

			const oldSelectedClient = this._communications.find(communication => {
				return communication.entityTypeId === this._commEntityTypeId && communication.entityId === this._commEntityId;
			});
			this.setClient(oldSelectedClient ?? this._communications[0]);

			this.initClientContainer();
		});
	}

	initClientContainer()
	{
		if (!Type.isDomNode(this._clientContainerNode))
		{
			return;
		}
		if (this._communications.length === 0)
		{
			BX.hide(this._clientContainerNode);
		}
		else
		{
			BX.show(this._clientContainerNode);
		}
	}

	initClientSelector()
	{
		const defaultClient = this._communications[0];
		if (defaultClient)
		{
			this.setClient(defaultClient);
		}

		const handler = this.onClientSelectorClick.bind(this);

		BX.bind(this._clientSelectorNode, 'click', (event) => {
			const menuItems = [];

			for (const communication of this._communications)
			{
				menuItems.push({
					text: communication.caption,
					client: communication,
					onclick: handler,
				});
			}

			this.openMenu('comm', this._clientSelectorNode, menuItems, event);
		});
	}

	onClientSelectorClick(e, item)
	{
		if (item.client)
		{
			this.setClient(item.client);
		}
		this._menu.close();
	}

	setClient(client: ?Object)
	{
		this._commEntityTypeId = client?.entityTypeId;
		this._commEntityId = client?.entityId;
		if (Type.isDomNode(this._clientSelectorNode))
		{
			this._clientSelectorNode.textContent = client?.caption ?? '';
		}
		this._toList = client?.phones ?? [];
		this.setTo(client?.phones[0] ?? {});
	}

	initToSelector()
	{
		BX.bind(this._toSelectorNode, 'click', this.onToSelectorClick.bind(this));
	}

	onToSelectorClick(e)
	{
		const menuItems = [];
		const handler = this.onToSelectorItemClick.bind(this);

		for (let i = 0; i < this._toList.length; ++i)
		{
			menuItems.push({
				text: this._toList[i].valueFormatted || this._toList[i].value,
				to: this._toList[i],
				onclick: handler
			});
		}

		this.openMenu('to_'+this._commEntityTypeId+'_'+this._commEntityId, this._toSelectorNode, menuItems, e);
	}

	onToSelectorItemClick(e, item)
	{
		if (item.to)
		{
			this.setTo(item.to);
		}
		this._menu.close();
	}

	setTo(to: ?Object)
	{
		this._to = to?.value;
		if (Type.isDomNode(this._toSelectorNode))
		{
			this._toSelectorNode.textContent = (to?.valueFormatted || to?.value) ?? '';
		}
	}

	openMenu(menuId, bindElement, menuItems, e)
	{
		if (this._shownMenuId === menuId)
		{
			return;
		}

		if(this._shownMenuId !== null && this._menu)
		{
			this._menu.close();
			this._shownMenuId = null;
		}

		BX.PopupMenu.show(
			this._id + menuId,
			bindElement,
			menuItems,
			{
				cacheable: false,
				offsetTop: 0,
				offsetLeft: 36,
				angle: { position: "top", offset: 0 },
				events:
					{
						onPopupClose: BX.delegate(this.onMenuClose, this)
					}
			}
		);

		this._menu = BX.PopupMenu.currentItem;
		e.preventDefault();
	}

	onMenuClose()
	{
		this._shownMenuId = null;
		this._menu = null;
	}

	initMessageLengthCounter()
	{
		this._messageLengthMax = parseInt(this._messageLengthCounterNode.getAttribute('data-length-max'));
		BX.bind(this._input, 'keyup', this.setMessageLengthCounter.bind(this));
		BX.bind(this._input, 'cut', this.setMessageLengthCounterDelayed.bind(this));
		BX.bind(this._input, 'paste', this.setMessageLengthCounterDelayed.bind(this));
	}

	setMessageLengthCounterDelayed()
	{
		setTimeout(this.setMessageLengthCounter.bind(this), 0);
	}

	setMessageLengthCounter()
	{
		const length = this._input.value.length;
		this._messageLengthCounterNode.textContent = length;

		const classFn = length >= this._messageLengthMax ? 'addClass' : 'removeClass';
		BX[classFn](this._messageLengthCounterNode, 'crm-entity-stream-content-sms-symbol-counter-number-overhead');

		this.toggleSaveButton();
	}

	toggleSaveButton(): void
	{
		const sender = this.getSelectedSender();
		let enabled;

		if (!sender || !sender.isTemplatesBased)
		{
			enabled = this._input.value.length > 0;
		}
		else
		{
			enabled = !!this._templateId;
		}

		if (enabled)
		{
			BX.removeClass(this._saveButton, 'ui-btn-disabled');
		}
		else
		{
			BX.addClass(this._saveButton, 'ui-btn-disabled');
		}
	}

	save()
	{
		const sender = this.getSelectedSender();

		const { text, templateId } = this.getSendData();

		if (text === '')
		{
			return;
		}

		if (!this._communications.length)
		{
			alert(BX.message('CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS'));

			return;
		}

		if (this._isRequestRunning || this._isLocked)
		{
			return;
		}

		this._isRequestRunning = true;
		this._isLocked = true;

		return new Promise((resolve, reject) => {
			BX.ajax({
				url: this.getSendUrl(),
				method: 'POST',
				dataType: 'json',
				data: {
					site: BX.message('SITE_ID'),
					sessid: BX.bitrix_sessid(),
					source: this._source,
					ACTION: 'SAVE_SMS_MESSAGE',
					SENDER_ID: this._senderId,
					MESSAGE_FROM: this._from,
					MESSAGE_TO: this._to,
					MESSAGE_BODY: text,
					MESSAGE_TEMPLATE: templateId,
					MESSAGE_TEMPLATE_WITH_PLACEHOLDER: this.isCurrentSenderTemplateHasPlaceholders(),
					OWNER_TYPE_ID: this._ownerTypeId,
					OWNER_ID: this._ownerId,
					TO_ENTITY_TYPE_ID: this._commEntityTypeId,
					TO_ENTITY_ID: this._commEntityId,
					PAYMENT_ID: this._paymentId,
					SHIPMENT_ID: this._shipmentId,
					COMPILATION_PRODUCT_IDS: this._compilationProductIds,
				},
				onsuccess: () => {
					this.onSaveSuccess();
					resolve();
				},
				onfailure: () => {
					this.onSaveFailure();
					reject();
				},
			});
		});
	}

	getSendData(): ?Object
	{
		if (!this.isFetchedConfig)
		{
			return {
				text: '',
				templateId: null,
			};
		}

		let text = '';
		let templateId = null;

		if (this.isCurrentSenderIsTemplatesBased())
		{
			const template = this.getSelectedTemplate();
			if (!template)
			{
				return null;
			}

			if (this.tplEditor)
			{
				const tplEditorData = this.tplEditor.getData();
				if (Type.isPlainObject(tplEditorData))
				{
					text = tplEditorData.body; // @todo check position: body or preview
				}
			}

			if (text === '')
			{
				text = template.PREVIEW;
			}

			templateId = template.ID;
		}
		else
		{
			text = this._input.value;
		}

		return {
			text,
			templateId,
		}
	}

	getSendUrl(): string
	{
		return BX.util.add_url_param(
			this._serviceUrl,
			{
				'action': 'save_sms_message',
				'sender': this._senderId,
			}
		);
	}

	getSelectedSender(): ?Object
	{
		return this._senders.find((sender) => sender.id === this._senderId);
	}

	cancel()
	{
		this._input.value = "";
		this.setMessageLengthCounter();
		this._input.style.minHeight = "";
		this.release();
	}

	onSaveSuccess(data)
	{
		this._isRequestRunning = this._isLocked = false;

		const error = BX.prop.getString(data, "ERROR", "");
		if(error !== "")
		{
			alert(error);
			return;
		}

		this._input.value = "";
		this.setMessageLengthCounter();
		this._input.style.minHeight = "";
		this.emitFinishEditEvent();
		this.release();
	}

	onSaveFailure()
	{
		this._isRequestRunning = this._isLocked = false;
	}

	initSalescenterApplication()
	{
		BX.bind(this._salescenterStarter, 'click', this.startSalescenterApplication.bind(this));
	}

	startSalescenterApplication()
	{
		const isSalescenterToolEnabled = BX.prop.getBoolean(this.getSetting('smsConfig', {}), 'isSalescenterToolEnabled', false);
		if (!isSalescenterToolEnabled)
		{
			BX.loadExt('salescenter.tool-availability-manager').then(() =>
			{
				BX.Salescenter.ToolAvailabilityManager.openSalescenterToolDisabledSlider();
			});

			return;
		}

		BX.loadExt('salescenter.manager').then(function()
		{
			BX.Salescenter.Manager.openApplication({
				disableSendButton: this._canSendMessage ? '' : 'y',
				context: 'sms',
				ownerTypeId: this._ownerTypeId,
				ownerId: this._ownerId,
				mode: this._ownerTypeId === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment',
				st: {
					tool: 'crm',
					category: 'payments',
					event: 'payment_create_click',
					c_section: 'crm_sms',
					c_sub_section: 'web',
					type: 'delivery_payment',
				}
			}).then(function(result)
			{
				if(result && result.get('action'))
				{
					if(result.get('action') === 'sendPage' && result.get('page') && result.get('page').url)
					{
						this._input.focus();
						this._input.value = this._input.value + result.get('page').name + ' ' + result.get('page').url;
						this.setMessageLengthCounter();
					}
					else if (result.get('action') === 'sendPayment' && result.get('order'))
					{
						this._input.focus();
						this._input.value = this._input.value + result.get('order').title;
						this.setMessageLengthCounter();
						this._source = 'order';
						this._paymentId = result.get('order').paymentId;
						this._shipmentId = result.get('order').shipmentId;
					}
					else if(result.get('action') === 'sendCompilation' && result.get('compilation'))
					{
						this._input.focus();
						this._input.value = this._input.value + result.get('compilation').title;
						this.setMessageLengthCounter();
						this._source = 'deal';
						this._compilationProductIds = result.get('compilation').productIds;
					}
				}
			}.bind(this));
		}.bind(this));
	}

	initDocumentSelector()
	{
		BX.bind(this._documentSelectorButton, 'click', this.onDocumentSelectorClick.bind(this));
	}

	onDocumentSelectorClick()
	{
		if(!this._documentSelector)
		{
			BX.loadExt('documentgenerator.selector').then(function()
			{
				this._documentSelector = new BX.DocumentGenerator.Selector.Menu({
					node: this._documentSelectorButton,
					moduleId: 'crm',
					provider: this._documentsProvider,
					value: this._documentsValue,
					analyticsLabelPrefix: 'crmTimelineSmsEditor'
				});
				this.selectPublicUrl();
			}.bind(this));
		}
		else
		{
			this.selectPublicUrl();
		}
	}

	selectPublicUrl()
	{
		if(!this._documentSelector)
		{
			return;
		}
		this._documentSelector.show().then(function(object)
		{
			if(object instanceof BX.DocumentGenerator.Selector.Template)
			{
				this._documentSelector.createDocument(object).then(function(document)
				{
					this.pasteDocumentUrl(document);
				}.bind(this)).catch(function(error)
				{
					console.error(error);
				}.bind(this));
			}
			else if(object instanceof BX.DocumentGenerator.Selector.Document)
			{
				this.pasteDocumentUrl(object);
			}
		}.bind(this)).catch(function(error)
		{
			console.error(error);
		}.bind(this));
	}

	pasteDocumentUrl(document)
	{
		this._documentSelector.getDocumentPublicUrl(document).then(function(publicUrl)
		{
			this._input.focus();
			this._input.value = this._input.value + ' ' + document.getTitle() + ' ' + publicUrl;
			this.setMessageLengthCounter();
			this._source = 'document';
		}.bind(this)).catch(function(error)
		{
			console.error(error);
		}.bind(this));
	}

	initFileSelector()
	{
		BX.bind(this._fileSelectorButton, 'click', this.onFileSelectorClick.bind(this));
	}

	closeFileSelector()
	{
		BX.PopupMenu.destroy('sms-file-selector');
	}

	onFileSelectorClick()
	{
		BX.PopupMenu.show('sms-file-selector', this._fileSelectorButton, [
			{
				text: BX.message('CRM_TIMELINE_SMS_UPLOAD_FILE'),
				onclick: this.uploadFile.bind(this),
				className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
			},
			{
				text: BX.message('CRM_TIMELINE_SMS_FIND_FILE'),
				onclick: this.findFile.bind(this),
				className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
			}
		])
	}

	getFileUploadInput()
	{
		return document.getElementById(this._fileUploadLabel.getAttribute('for'));
	}

	uploadFile()
	{
		this.closeFileSelector();
		if(this._isFilesExternalLinkEnabled)
		{
			this.initDiskUF();
			BX.fireEvent(this.getFileUploadInput(), 'click');
		}
		else
		{
			this.showFilesExternalLinkFeaturePopup();
		}
	}

	findFile()
	{
		this.closeFileSelector();
		if(this._isFilesExternalLinkEnabled)
		{
			this.initDiskUF();
			BX.fireEvent(this._fileSelectorBitrix, 'click');
		}
		else
		{
			this.showFilesExternalLinkFeaturePopup();
		}
	}

	getLoader()
	{
		if(!this.loader)
		{
			this.loader = new BX.Loader(
				{
					size: 50
				});
		}

		return this.loader;
	}

	showLoader(node)
	{
		if(node && !this.getLoader().isShown())
		{
			this.getLoader().show(node);
		}
	}

	hideLoader()
	{
		if(this.getLoader().isShown())
		{
			this.getLoader().hide();
		}
	}

	initDiskUF()
	{
		if(this.isDiskFileUploaderInited || !this._isFilesEnabled)
		{
			return;
		}
		this.isDiskFileUploaderInited = true;
		BX.addCustomEvent(this._fileUploadZone, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
		BX.addCustomEvent(this._fileUploadZone, 'DiskDLoadFormControllerInit', function(uf)
		{
			uf._onUploadProgress = function()
			{
				this.showLoader(this._fileSelectorButton.parentNode.parentNode);
			}.bind(this);
		}.bind(this));

		BX.Disk.UF.add({
			UID: this._fileUploadZone.getAttribute('data-node-id'),
			controlName: this._fileUploadLabel.getAttribute('for'),
			hideSelectDialog: false,
			urlSelect: this._diskUrls.urlSelect,
			urlRenameFile: this._diskUrls.urlRenameFile,
			urlDeleteFile: this._diskUrls.urlDeleteFile,
			urlUpload: this._diskUrls.urlUpload
		});

		BX.onCustomEvent(
			this._fileUploadZone,
			'DiskLoadFormController',
			['show']
		);
	}

	OnFileUploadSuccess(fileResult, uf, file, uploaderFile)
	{
		this.hideLoader();
		const diskFileId = parseInt(fileResult.element_id.replace('n', ''));
		const fileName = fileResult.element_name;
		this.pasteFileUrl(diskFileId, fileName);
	}

	pasteFileUrl(diskFileId, fileName)
	{
		this.showLoader(this._fileSelectorButton.parentNode.parentNode);
		BX.ajax.runAction('disk.file.generateExternalLink', {
			analyticsLabel: 'crmTimelineSmsEditorGetFilePublicUrl',
			data: {
				fileId: diskFileId
			}
		}).then(function(response)
		{
			this.hideLoader();
			if(response.data.externalLink && response.data.externalLink.link)
			{
				this._input.focus();
				this._input.value = this._input.value + ' ' + fileName + ' ' + response.data.externalLink.link;
				this.setMessageLengthCounter();
				this._source = 'file';
			}
		}.bind(this)).catch(function(response)
		{
			console.error(response.errors.pop().message);
		});
	}

	getFeaturePopup(content)
	{
		if(this.featurePopup != null)
		{
			return this.featurePopup;
		}
		this.featurePopup = new BX.PopupWindow('bx-popup-crm-sms-editor-feature-popup', null, {
			zIndex: 200,
			autoHide: true,
			closeByEsc: true,
			closeIcon: true,
			overlay : true,
			events : {
				onPopupDestroy : function()
				{
					this.featurePopup = null;
				}.bind(this)
			},
			content : content,
			contentColor: 'white'
		});

		return this.featurePopup;
	}

	showFilesExternalLinkFeaturePopup()
	{
		this.getFeaturePopup(this._fileExternalLinkDisabledContent).show();
	}

	onTemplateHintIconClick()
	{
		if (this._senderId === 'ednaru')
		{
			top.BX.Helper.show("redirect=detail&code=14214014");
		}
	}

	showTemplateSelectDropdown(items)
	{
		const menuItems = [];
		if (Type.isArray(items))
		{
			if (items.length)
			{
				items.forEach((item) => {
					menuItems.push({
						templateId: item.ORIGINAL_ID ?? null,
						value: item.ID,
						text: item.TITLE,
						onclick: this._selectTemplateHandler,
					});
				});

				BX.PopupMenu.show({
					id: this._templateSelectorMenuId,
					bindElement: this._templateSelectorNode,
					items: menuItems,
					angle: false,
					width: this._templateSelectorNode.offsetWidth,
				});
			}
		}
		else if (this._senderId)
		{
			const loaderMenuId = this._templateSelectorMenuId + 'loader';
			const loaderMenuLoaderId = this._templateSelectorMenuId + 'loader';

			BX.PopupMenu.show({
				id: loaderMenuId,
				bindElement: this._templateSelectorNode,
				items: [{
					html: '<div id="' + loaderMenuLoaderId + '"></div>',
				}],
				angle: false,
				width: this._templateSelectorNode.offsetWidth,
				height: 60,
				events: {
					onDestroy: function() {
						this.hideLoader();
					}.bind(this)
				}
			});
			this.showLoader(BX(loaderMenuLoaderId));

			if (!this._isRequestRunning)
			{
				this._isRequestRunning = true;
				const senderId = this._senderId;

				BX.ajax.runAction(
					'crm.activity.sms.getTemplates',
					{
						data: {
							senderId,
							context: {
								module: 'crm',
								entityTypeId: this.getEntityTypeId(),
								entityCategoryId: this.getEntityCategoryId(),
								entityId: this.getEntityId(),
							}
						}
					}
				)
					.then(function(response)
					{
						this._isRequestRunning = false;
						const sender = this._senders.find(function (sender) {
							return (sender.id === senderId);
						}.bind(this));

						if (sender)
						{
							sender.templates = response.data.templates;
							this.toggleTemplateSelectAvailability();
							if (BX.PopupMenu.getMenuById(loaderMenuId))
							{
								BX.PopupMenu.getMenuById(loaderMenuId).close();

								if (this.isVisible())
								{
									this.showTemplateSelectDropdown(sender.templates);
								}
							}
						}
					}.bind(this))
					.catch(function(response)
					{
						this._isRequestRunning = false;
						if (BX.PopupMenu.getMenuById(loaderMenuId))
						{
							if (response && response.errors && response.errors[0] && response.errors[0].message)
							{
								alert(response.errors[0].message);
							}
							BX.PopupMenu.getMenuById(loaderMenuId).close();
						}
					}.bind(this))
				;
			}
		}

	}

	getSelectedTemplate(): ?Object
	{
		const sender = this.getSelectedSender();
		if (!this._templateId || !sender || !sender.templates)
		{
			return null;
		}

		const template = sender.templates.find((template) => template.ID === this._templateId);

		return template ?? null;
	}

	preparePlaceholdersFromTemplate(template: Object): void
	{
		const templatePlaceholders = template.PLACEHOLDERS ?? null;
		if (!Type.isPlainObject(templatePlaceholders))
		{
			this.placeholders = null;
			this.filledPlaceholders = null;

			return;
		}

		this.placeholders = templatePlaceholders;
		if (!Type.isArray(template.FILLED_PLACEHOLDERS))
		{
			template.FILLED_PLACEHOLDERS = [];
		}

		this.filledPlaceholders = template.FILLED_PLACEHOLDERS;
	}

	onTemplateSelectClick()
	{
		const sender = this.getSelectedSender();

		if (sender)
		{
			this.showTemplateSelectDropdown(sender.templates);
		}
	}

	onSelectTemplate(e, item)
	{
		this._templateId = item.value;
		this.templateOriginalId = item.templateId;
		this.applySelectedTemplate();
		this.toggleSaveButton();
		const menu = BX.PopupMenu.getMenuById(this._templateSelectorMenuId);
		if (menu)
		{
			menu.close();
		}
	}

	toggleTemplateSelectAvailability()
	{
		const sender = this.getSelectedSender();
		if (sender && Type.isArray(sender.templates) && !sender.templates.length)
		{
			BX.addClass(this._templateSelectorNode, 'ui-ctl-disabled');
			this._templateTemplateTitleNode.textContent = BX.message('CRM_TIMELINE_SMS_TEMPLATES_NOT_FOUND');
		}
		else
		{
			BX.removeClass(this._templateSelectorNode, 'ui-ctl-disabled');
			this.applySelectedTemplate();
		}
	}

	applySelectedTemplate(): void
	{
		if (!this.isCurrentSenderHasTemplates())
		{
			this.hideTemplatePreviewNodeAndClearTitle();

			return;
		}

		const template = this.getSelectedTemplate();
		if (!Type.isPlainObject(template))
		{
			this.hideTemplatePreviewNodeAndClearTitle();

			return;
		}

		this.preparePlaceholdersFromTemplate(template);

		this.setTemplateNodeTitle(template.TITLE);
		this.initTemplateEditor(template);

		this.showNode(this._templatePreviewNode);
	}

	showNode(node): void
	{
		Dom.style(node, { display: ''});
	}

	isCurrentSenderTemplateHasPlaceholders(): boolean
	{
		return (this.isCurrentSenderIsTemplatesBased() && Type.isPlainObject(this.placeholders));
	}

	isCurrentSenderIsTemplatesBased(): boolean
	{
		const sender = this.getSelectedSender();

		return (sender && sender.isTemplatesBased);
	}

	isCurrentSenderHasTemplates(): boolean
	{
		const sender = this.getSelectedSender();

		return (sender && sender.templates);
	}

	hideTemplatePreviewNodeAndClearTitle(): void
	{
		this.hideNode(this._templatePreviewNode);
		this.setTemplateNodeTitle();
	}

	hideNode(node): void
	{
		Dom.style(node, { display: 'none'});
	}

	setTemplateNodeTitle(title: string = ''): void
	{
		this._templateTemplateTitleNode.textContent = title;
	}

	initTemplateEditor(template: Object): void
	{
		// @todo will support other positions too, not only Preview
		const preview = Text.encode(template.PREVIEW).replaceAll('\n', '<br>');

		const params = {
			target: this._templatePreviewNode,
			entityId: this._ownerId,
			entityTypeId: this._ownerTypeId,
			categoryId: this._ownerCategoryId,
			onSelect: (params) => this.createOrUpdatePlaceholder(params),
			//onDeselect: (params) => this.deletePlaceholder(params),
		};
		this.tplEditor = (new BX.Crm.Template.Editor(params))
			.setPlaceholders(this.placeholders)
			.setFilledPlaceholders(this.filledPlaceholders)
		;

		// @todo will support other positions too, not only Preview
		this.tplEditor.setBody(preview);
	}

	createOrUpdatePlaceholder(params: Object): void
	{
		const { id, value, entityType, text } = params;
		BX.ajax.runAction(
			'crm.activity.smsplaceholder.createOrUpdatePlaceholder',
			{
				data: {
					placeholderId: id,
					fieldName: Type.isStringFilled(value) ? value : null,
					entityType: Type.isStringFilled(entityType) ? entityType : null,
					fieldValue:  Type.isStringFilled(text) ? text : null,
					...this.getCommonPlaceholderData(),
				},
			},
		);
	}

	/* deletePlaceholder({ placeholderId }): void
	{
		BX.ajax.runAction(
			'crm.activity.smsplaceholder.deletePlaceholder',
			{
				data: {
					placeholderId,
					...this.getCommonPlaceholderData(),
				},
			},
		);
	} */

	getCommonPlaceholderData(): Object
	{
		return {
			templateId: this.templateOriginalId,
			entityTypeId: this._ownerTypeId,
			entityCategoryId: this._ownerCategoryId,
		}
	}

	/**
	 * @override
	 * */
	activate()
	{
		super.activate();

		// fetch config
		if (this.isFetchedConfig || !this.getEntityId())
		{
			return;
		}

		this.isFetchedConfig = false;
		this.fetchConfigPromise = new Promise((resolve) => {
			Ajax.runAction('crm.api.timeline.sms.getConfig', {
				json: {
					entityTypeId: this.getEntityTypeId(),
					entityId: this.getEntityId(),
				},
			}).then(({ data }) => {
				this.isFetchedConfig = true;
				this.setSettings(data);

				setTimeout(() => {
					const canSend = this.getSetting('canSendMessage', false);

					this.setContainer(Tag.render`
						<div class="crm-entity-stream-content-new-detail --focus">
							${canSend ? this.#renderEditor() : this.#renderSetupText()}
						</div>
					`);

					if (this.isCurrentSenderIsTemplatesBased() && !this.getSelectedSender().templates)
					{
						this.onTemplateSelectClick();
					}

					resolve();
				}, 50);
			}).catch(() => {
				this.showNotify(Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONFIG_ERROR'));

				setTimeout(() => this.cancel(), 50);
			});
		});
	}

	tryToResend(senderId: string, fromId: string, clientData: Object, rawDescription: ?string): void
	{
		if (this.isFetchedConfig)
		{
			this.#prepareToResend(senderId, fromId, clientData, rawDescription);
		}
		else
		{
			// eslint-disable-next-line promise/catch-or-return
			this.fetchConfigPromise.then(() => this.#prepareToResend(senderId, fromId, clientData, rawDescription));
		}
	}

	#prepareToResend(senderId: string, fromId: string, clientData: Object, rawDescription: ?string): void
	{
		const sender = this._senders.find((sender: Object) => sender.id === senderId);
		if (sender?.canUse && Type.isArrayFilled(sender?.fromList))
		{
			this.setSender(sender);

			const from = sender.fromList.find((from: Object) => String(from.id) === fromId);
			if (from)
			{
				this.setFrom(from);
			}
			else
			{
				console.warn('Unable to resend SMS with selected from');
			}
		}
		else
		{
			console.warn('Unable to resend SMS with sender ID "' + senderId + '"');
		}

		const client = this._communications
			.find((communication: Object) => communication.entityId === clientData.entityId
				&& communication.entityTypeId === clientData.entityTypeId
			)
		;
		if (client)
		{
			this.setClient(client);

			const to = client.phones.find((phone: Object) => phone.value === clientData.value);
			if (to)
			{
				this.setTo(to);
			}
		}
		else
		{
			console.warn('Unable to resend SMS with selected client');
		}

		if (Type.isStringFilled(rawDescription))
		{
			this._input.value = rawDescription;
			this.setMessageLengthCounter();

			setTimeout(this.resizeForm.bind(this), 0);
		}

		if (this._smsDetail.classList.contains('hidden'))
		{
			setTimeout(() => this._smsDetailSwitcher.click(), 50);
		}
	}

	static create(id, settings)
	{
		const self = new Sms();
		self.initialize(id, settings);
		Sms.items[self.getId()] = self;

		return self;
	}

	static items = {};
}
