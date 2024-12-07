import { Event, Runtime, Reflection, Dom, Loc } from 'main.core';
import { MenuManager } from 'main.popup';
import {UI} from 'ui.notification';
import { Guide } from "sign.tour";

import { Preview, PreviewOptions } from './preview';

import '../sign.css';
import 'ui.info-helper';

type loadCrmEntityEditorData = {
	id: number,
	documentId: number,
	categoryId: number,
	configId: string,
	guid: string,
	entityTypeId: string,
	stageId: string,
	documentEditorUrl: string,
	container: HTMLElement,
	contactsCount: number,
}

type MemberCommunicationItem = {
	type: string,
	value: string
};

type MemberCommunication = {
	[memberId: number]: Array<MemberCommunicationItem>
};

type InitCommunicationsSelectorType = {
	containers: NodeList,
	attrMemberIdName: string,
	attrMemberUrlName: string,
	communications: MemberCommunication,
	smsAllowed: boolean,
	smsNotAllowedCallback: () => {}
};

const EntityEditor = Reflection.namespace('BX.Crm.EntityEditor');

export class Master
{
	static #loadedElementsInPartnerStep = {
		preview: false,
		crmItemEditor: false,
	};

	static nextStepHandler(
		attrInputFileSingle: string,
		attrInputFileMulti: string,
		attrItemsFileElements: string
	)
	{
		if (!attrInputFileSingle || !attrInputFileMulti || !attrItemsFileElements)
		{
			return;
		}

		const inputFileSingleNode = document.querySelector(attrInputFileSingle);
		const inputFileMultiNode = document.querySelector(attrInputFileMulti);
		const itemFileElements = document.querySelectorAll(attrItemsFileElements);
		let checkedInput = null;
		let activeElementItem = null;

		// load file controller
		const onChangeFileInput = (inputNode: HTMLInputElement) => {
			if (inputNode)
			{
				inputNode.onchange = () => {
					if (inputNode.value !== '')
					{
						if (activeElementItem)
						{
							activeElementItem.classList.add('--active');
						}
						Master.unLockNavigation();
					}

					if (inputNode.value === '')
					{
						if (activeElementItem)
						{
							activeElementItem.classList.remove('--active');
						}
						Master.lockNavigation();
					}
				}
			}
		}

		onChangeFileInput(inputFileSingleNode);
		onChangeFileInput(inputFileMultiNode);

		for (let i = 0; i < itemFileElements.length; i++)
		{
			itemFileElements[i].addEventListener('click', () => {
				inputFileSingleNode.value = null;
				inputFileMultiNode.value = null;
				Master.lockNavigation();

				if (checkedInput)
				{
					checkedInput.checked = false;
				}

				if (activeElementItem)
				{
					activeElementItem.classList.remove('--active');
				}

				activeElementItem = itemFileElements[i];

				checkedInput = itemFileElements[i].querySelector('[type="radio"]');
				if (checkedInput)
				{
					checkedInput.checked = true;
					activeElementItem.classList.add('--active');
					Master.unLockNavigation();
				}
			});
		}
	}

	/**
	 * Handler for file selectors.
	 * @param {string} attrSelector Attribute name for file elector.
	 */
	static loadFileHandler(attrSelector: string)
	{
		const actionButton = document.querySelectorAll(attrSelector);
		let inputFile = document.querySelector('[data-action="inputFile"]');
		let inputFileMulti = document.querySelector('[data-action="inputFileMulti"]');
		const form = inputFile ? inputFile.closest('form') : null;

		if (!actionButton || !inputFile || !form)
		{
			return;
		}

		[...actionButton].map(node => {
			node.addEventListener('click', e => {
				if (node.getAttribute('data-multiple') === 'Y' && inputFileMulti)
				{
					inputFile = inputFileMulti;
				}

				inputFile.addEventListener('change', (e) => {

					if (e.target.files.length > 10)
					{
						const errorBlock = document.getElementById('sign-master__too_many_pics');
						if (errorBlock)
						{
							errorBlock.style.display = 'block';
						}

						window.scrollTo({
							top: 0,
							left: 0,
							behavior: 'smooth',
						});

						return;
					}

					const nextStepButton = document.querySelector('[data-master-next-step-button]')
							? document.querySelector('[data-master-next-step-button]')
							: null;
					Master.lockNavigation(nextStepButton);
					Master.lockContent();
					form.submit();
				});
				inputFile.click();
				e.preventDefault();
			});
		});
	}

	/**
	 * Handler for blank select.
	 * @param {NodeList} radios List of radio elements to blank select.
	 */
	static selectBlankHandler(radios: NodeList)
	{
		[...radios].map((radio: HTMLElement) => {
			radio.addEventListener('click', () => {
				radio.closest('form').submit();
			});
		});
	}

	/**
	 * Creates entity editor for company selector.
	 * @param userData
	 */
	static loadCrmEntityEditor(userData: loadCrmEntityEditorData)
	{
		Master.#getNextStepBtn().addEventListener('click', Master.onNextBtnClickAtPartnerStep);

		Master.adjustLockNavigation(
			Master.#getNextStepBtn(),
			Master.#getPreviousStepBtn(),
		);

		BX.addCustomEvent('BX.Sign.Preview:firstImageIsLoaded', Master.onPreviewFirstImageIsLoadedInPartnerStep)

		Master.reloadCrmElementsList();

		const actionButton = document.querySelector('[data-action="changePartner"]');
		const initiatorName = document.querySelector('[name="initiatorName"]');

		Master.lockNavigation();

		let loader = new BX.Loader({
			target: userData.container
		});

		loader.show();

		BX.ajax.runAction('crm.api.item.getEditor', {
			data: {
				id: userData.id,
				entityTypeId: userData.entityTypeId,
				guid: userData.guid,
				stageId: userData.stageId,
				categoryId: userData.categoryId,
				params: {
					'ENABLE_PAGE_TITLE_CONTROLS': false,
					'ENABLE_MODE_TOGGLE': true,
					'IS_EMBEDDED': 'N',
					'forceDefaultConfig': 'Y',
					'enableSingleSectionCombining': 'N',
				}
			}
		}).then(function (response){
			loader.destroy();
			Master.#loadedElementsInPartnerStep.crmItemEditor = true;
			if (Master.#loadedElementsInPartnerStep.preview)
			{
				Master.unLockNavigation();
				const btn = Master.#getNextStepBtn();
				if (btn)
				{
					btn.title = '';
				}
			}

			if (!response?.data?.html)
			{
				return;
			}

			Runtime.html(userData.container, response.data.html).then(() => {
				const editor = Master.getEditor(userData.guid);
				if (!editor)
				{
					return;
				}

				const myCompanyField = editor.getControlById('MYCOMPANY_ID');
				if (myCompanyField)
				{
					const myCompanySection = editor.getControlById('myCompany');

					if (myCompanyField.hasCompanies())
					{
						if (myCompanySection)
						{
							editor.switchControlMode(myCompanySection, BX.UI.EntityEditorMode.view);
						}
					}
					else if (myCompanySection)
					{
						myCompanySection.enableToggling(false);
					}

					myCompanyField.isRequired = () => true;
					myCompanyField.switchToSingleEditMode_prev = myCompanyField.switchToSingleEditMode;
					myCompanyField.switchToSingleEditMode = (params) =>
					{
						if (myCompanySection && myCompanySection.getMode() === BX.UI.EntityEditorMode.view)
						{
							editor.switchControlMode(myCompanySection, BX.UI.EntityEditorMode.edit);
						}
						myCompanyField.switchToSingleEditMode_prev(params);
					};
				}

				const clientSection = editor.getControlById('client');
				const contactsField = editor.getControlById('CLIENT');
				if (contactsField)
				{
					if (contactsField.hasContacts())
					{
						if (clientSection)
						{
							editor.switchControlMode(clientSection, BX.UI.EntityEditorMode.view);
						}
					}
					else
					{
						if (clientSection)
						{
							clientSection.enableToggling(false);
						}
						Dom.remove(contactsField._addContactButton);
					}

					contactsField.isRequired = () => true;
					if (contactsField._contactSearchBoxes[0])
					{
						contactsField._contactSearchBoxes[0]._isRequired = true;
					}

					contactsField.switchToSingleEditMode_prev = contactsField.switchToSingleEditMode;
					contactsField.switchToSingleEditMode = (params) =>
					{
						if (clientSection && clientSection.getMode() === BX.UI.EntityEditorMode.view)
						{
							editor.switchControlMode(clientSection, BX.UI.EntityEditorMode.edit);
						}
						contactsField.switchToSingleEditMode_prev(params);
					};
					contactsField.layout_prev = contactsField.layout;

					contactsField.layout = (options) =>
					{
						contactsField.layout_prev(options);

						setTimeout(() => {
							Dom.remove(contactsField._addContactButton);
						}, 10);
					};
				}
			});
		})
		.catch(error => {
			console.log('error', error);
		});

		const form = actionButton ? actionButton.closest('form') : null;

		if (!actionButton || !form)
		{
			return;
		}

		actionButton.addEventListener('click', e => {
			e.preventDefault();
			Master.hideErrors();

			const showError = message => {
				if (loader)
				{
					loader.destroy();
				}
				Master.unLockNavigation();
				Master.unLockContent();
				Master.showError(message);
			}

			if (initiatorName && initiatorName.value.trim() === '')
			{
				showError(Loc.getMessage('SIGN_CMP_MASTER_TPL_ERROR_WRONG_INITIATOR'));
			}
			else if (Master.checkFilledContacts(userData))
			{
				BX.Crm.EntityEditor.getDefault().save();
			}
			else
			{
				showError(Loc.getMessage('SIGN_CMP_MASTER_TPL_ERROR_WRONG_CONTACTS_NUMBER'));
			}
		});

		BX.addCustomEvent(window, 'BX.Crm.EntityEditor:onFailedValidation', (data) => {
			Master.unLockNavigation();
			Master.unLockContent();
		});
		BX.addCustomEvent(window, 'onCrmEntityUpdateError', (data) => {
			Master.unLockNavigation();
			Master.unLockContent();
		});
		BX.addCustomEvent(window, 'BX.Crm.EntityEditor:onEntitySaveFailure', (data) => {
			Master.unLockNavigation();
			Master.unLockContent();
			if (data.errors)
			{
				UI.Notification.Center.notify({
					content: data.errors.join(', ')
				});
			}
		});
		BX.addCustomEvent(window, 'onCrmEntityUpdate', data => {
			Master.reloadCrmElementsList();
			const entityData = data.entityData;

			if (!entityData?.CONTACT_ID)
			{
				Master.unLockNavigation();
				Master.unLockContent();
			}

			if (entityData?.CONTACT_ID)
			{
				BX.Sign.Backend.controller({
					command: 'internal.document.assignMembers',
					postData: {
						documentId: userData.documentId
					}
				}).then(() => {
						Master.openEditor(userData.documentEditorUrl, form);
				}).catch((response) =>
				{
					Master.unLockNavigation();
					Master.unLockContent();
					this.showError(response.errors[0].message, response.errors[0].customData);
				});
			}
		});
	}

	static showInfoHelperSlider(code: string)
	{
		Master.lockContent();
		Master.lockNavigation();

		const currentSlider = BX.SidePanel.Instance.getTopSlider();
		const infoHelper = top.BX.UI.InfoHelper;

		infoHelper.show(code);

		top.BX.addCustomEvent(
			infoHelper.getSlider(),
			'SidePanel.Slider:onCloseComplete',
			() => currentSlider?.close()
		);
	}

	/**
	 * Saves new document title.
	 * @param {number} documentId
	 */
	static saveTitle(documentId: number)
	{
		const actionButton = document.querySelector('[data-action="saveTitle"]');
		const cancelButton = document.querySelector('[data-action="cancel"]');
		const newTitleContainer = document.querySelector('[data-param="saveTitleValue"]');
		const newTitleWrapper = document.querySelector('[data-wrapper="title"]');
		const inputWrapper = document.querySelector('[data-wrapper="inputWrapper"]');
		const editButton = document.querySelector('[data-wrapper="edit"]');
		const editorWrapper = document.querySelector('[data-wrapper="titleEditor"]');

		if (editButton && editorWrapper)
		{
			editButton.addEventListener('click', () => {
				Dom.addClass(editorWrapper, '--edit');
			});
		}

		if (actionButton && newTitleContainer)
		{
			newTitleContainer.addEventListener('keydown', (ev) => {
				if (ev.code.toLowerCase() === 'escape')
				{
					Dom.removeClass(editorWrapper, '--edit');
					newTitleContainer.value = newTitleWrapper.innerText;
					ev.stopPropagation();
				}

				if (ev.code.toLowerCase() === 'enter')
				{
					saveTitle();
					ev.stopPropagation();
					ev.preventDefault();
				}
			});

			cancelButton.addEventListener('click', () => {
				Dom.removeClass(editorWrapper, '--edit');
				newTitleContainer.value = newTitleWrapper.innerText;
			});

			actionButton.addEventListener('click', () => saveTitle());

			let saveTitle = () => {
				Dom.addClass(actionButton, 'ui-btn-wait');
				Dom.addClass(inputWrapper, 'ui-ctl-disabled');

				const newTitle = newTitleContainer.value;

				BX.Sign.Backend.controller({
						command: 'document.setTitle',
						postData: {
							documentId: documentId,
							title: newTitle
						}
					})
					.then((result) => {
						if (result && newTitleWrapper)
						{
							newTitleWrapper.innerHTML = BX.Text.encode(newTitle);
						}

						Dom.removeClass(actionButton, 'ui-btn-wait');
						Dom.removeClass(inputWrapper, 'ui-ctl-disabled');
						Dom.removeClass(editorWrapper, '--edit');
					})
					.catch(() => {})
			}
		}
	}

	static adjustLockNavigation(buttonNext: HTMLElement, buttonPrev: HTMLElement): void
	{
		buttonNext = buttonNext || null;
		buttonPrev = buttonPrev || null;

		if (buttonNext)
		{
			buttonNext.addEventListener('click', () => {
				Master.lockContent();
				Master.lockNavigation(buttonNext, buttonPrev);
			})
		}

		if (buttonPrev)
		{
			buttonPrev.addEventListener('click', () => {
				Master.lockContent();
				Master.lockNavigation(buttonPrev, buttonNext);
			});
		}
	}

	static lockNavigation(buttonClick: HTMLElement, button: HTMLElement): void
	{
		if (buttonClick)
		{
			buttonClick.classList.add('ui-btn-wait');
			buttonClick.classList.add('ui-btn-disabled');
		}

		if (button)
		{
			button.classList.add('ui-btn-disabled');
		}

		let disabled = (node) => { node.classList.add('ui-btn-disabled') };

		if (!buttonClick)
		{
			const buttonNext = document.querySelector('[data-master-next-step-button]');
			buttonNext
				? disabled(buttonNext)
				: null;
		}

		if (!button)
		{
			const buttonPrev = document.querySelector('[data-master-prev-step-button]');
			buttonPrev
				? disabled(buttonPrev)
				: null;
		}
	}

	static unLockNavigation(): void
	{
		const buttonNext = document.querySelector('[data-master-next-step-button]');
		const buttonPrev = document.querySelector('[data-master-prev-step-button]');

		if (buttonNext)
		{
			buttonNext.classList.remove('ui-btn-wait');
			buttonNext.classList.remove('ui-btn-disabled');
		}

		if (buttonPrev)
		{
			buttonPrev.classList.remove('ui-btn-wait');
			buttonPrev.classList.remove('ui-btn-disabled');
		}
	}

	static lockContent(): void
	{
		const contentNode = document.querySelector('[data-role="sign-master__content"]');

		if (contentNode)
		{
			contentNode.classList.add('--lock');
		}
	}

	static unLockContent(): void
	{
		const contentNode = document.querySelector('[data-role="sign-master__content"]');

		if (contentNode)
		{
			contentNode.classList.remove('--lock');
		}
	}

	/**
	 * Checks contacts fields.
	 * @param {loadCrmEntityEditorData} userData
	 * @return {boolean}
	 */
	static checkFilledContacts(userData: loadCrmEntityEditorData): boolean
	{
		let isSaveAllowed = true;
		if (Number(userData.contactsCount) <= 0)
		{
			return isSaveAllowed;
		}
		const editor = Master.getEditor(userData.guid);
		if (!editor)
		{
			return isSaveAllowed;
		}
		const contactsField = editor.getControlById('CLIENT');
		if (!contactsField)
		{
			return isSaveAllowed;
		}

		const myCompanyField = editor.getControlById('MYCOMPANY_ID');
		if (!myCompanyField)
		{
			return isSaveAllowed;
		}

		const filledContactsCount = contactsField._contactInfos.length() + (myCompanyField.hasCompanies() ? 1 : 0);
		if (filledContactsCount < userData.contactsCount)
		{
			isSaveAllowed = false;
		}

		return isSaveAllowed;
	}

	/**
	 * Returns editor instance by GUID.
	 * @param {string }guid
	 * @return {EntityEditor|null}
	 */
	static getEditor(guid: string): ?EntityEditor
	{
		if (EntityEditor)
		{
			return EntityEditor.get(guid);
		}

		return null;
	}

	/**
	 * Opens editor in slider.
	 * @param {string} editorUrl
	 * @param {HTMLElement} formElement
	 */
	static openEditor(editorUrl: string, formElement: HTMLElement)
	{
		if (
			typeof BX.SidePanel !== 'undefined' &&
			typeof BX.SidePanel.Instance !== 'undefined'
		)
		{
			BX.SidePanel.Instance.open(
				editorUrl,
				{
					data: {
						bxSignEditorAllSaved: true,
					},
					events: {
						onClose: (event) => {
							if (event.getSlider().getData().get('bxSignEditorAllSaved') === true)
							{
								if (formElement)
								{
									formElement.submit();
								}
								else
								{
									window.location.reload();
								}
							}
							else
							{
								event.denyAction();
							}
						}
					},
					width: 1200,
					cacheable: false,
					allowChangeHistory: false
				}
			);
		}
	}

	/**
	 * Toggles some areas when mute-checkbox was checked.
	 * @param {NodeList} checkboxes List of checkboxes to mute.
	 * @param {string} closestSelector Closest selector of checkbox which contain member.
	 * @param {string} attrName Attribute name which toggle on click.
	 */
	static initMuteCheckbox(checkboxes: NodeList, closestSelector: string, attrName: string)
	{
		if (!attrName)
		{
			return;
		}

		[...checkboxes].map((checkbox: HTMLElement) => {
			Event.bind(checkbox, 'click', () => {
				const toToggle = checkbox.closest(closestSelector).querySelectorAll('[' + attrName + '="1"]');

				[...toToggle].map((element: HTMLElement) => {
					element.hidden = checkbox.checked;
				});
			});
		});
	}

	/**
	 * Shows popup menu to select communication type with member.
	 * @param {InitCommunicationsSelectorType} data
	 */
	static initCommunicationsSelector(data: InitCommunicationsSelectorType)
	{
		const {
			containers,
			attrMemberIdName,
			attrMemberUrlName,
			communications,
			smsAllowed,
			smsNotAllowedCallback,
		} = data;

		Array.from(containers).map((container: HTMLElement) => {

			const memberId = parseInt(container.getAttribute(attrMemberIdName));
			const memberUrl = container.getAttribute(attrMemberUrlName);
			const communicationsLocal = communications[memberId].length ? communications[memberId] : [];

			let menuManager;
			const menuId = 'sign-member-communications-' + memberId;
			const input = container.querySelector('input');
			const span = container.querySelector('.sign-master__send-member--communications-arrow span');

			const menuItems = [];
			const openItemFunc = () => BX.SidePanel.Instance.open(memberUrl, {
				cacheable: false,
				allowChangeHistory: false,
				events: {
					onClose: function onClose() {
						window.location.reload();
						if (menuManager)
						{
							menuManager.close();
						}
					}
				},
			});

			communicationsLocal.map((communication: MemberCommunicationItem) => {
				menuItems.push({
					text: communication.value,
					onclick: () => {

						if (communication.type === 'PHONE' && !smsAllowed)
						{
							smsNotAllowedCallback();
						}
						else
						{
							input.setAttribute('value', communication.type + '|' + communication.value);
							span.innerText = communication.value;
							span.parentNode.setAttribute('title', communication.value)
						}

						MenuManager.getMenuById(menuId).close();
					}
				});
			});

			if (menuItems.length <= 0)
			{
				Event.bind(container, 'click', () => {
					openItemFunc();
				});
				return;
			}

			// add new communication item
			menuItems.push({
				text: Loc.getMessage('SIGN_CMP_MASTER_TPL_MEMBER_NEW_COMMUNICATION'),
				onclick: () => {
					openItemFunc();
				}
			});

			menuManager = MenuManager.create({
				id: menuId,
				bindElement: container,
				items: menuItems
			});

			Event.bind(container, 'click', () => {
				menuManager.show();
			});
		});

		const guide = new Guide({
			steps: [
				{
					target: document.querySelector('.sign-master__send-member--communications'),
					title: Loc.getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TITLE'),
					text: Loc.getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TEXT'),
					position: 'right',
				},
			],
			id: 'sign-tour-guide-onboarding-master-member-communication',
			autoSave: true,
			simpleMode: true,
		});
		guide.startOnce();
	}

	/**
	 * Shows document preview.
	 * @param options
	 */
	static showPreview(options: PreviewOptions)
	{
		new Preview(options);
	}

	/**
	 * Shows error block with message.
	 * @param {string} error
	 * @param link
	 */
	static showError(error: string, link): void
	{
		const errorsContainer = document.querySelector('[data-role="sign-error-container"]');
		if (errorsContainer)
		{
			Dom.style(errorsContainer.parentNode, 'display', 'block');
			errorsContainer.innerHTML = BX.util.htmlspecialchars(error);
			if (link?.href)
			{
				const href = BX.util.htmlspecialchars(link.href);
				const linkStart = '<a href="'+href+'" target="_blank">';
				const linkEnd = '</a>';
				const button = BX.util.htmlspecialchars(link.button);
				errorsContainer.innerHTML += ' ' + button.replace('#LINK_START#', linkStart).replace('#LINK_END#', linkEnd);
			}
			window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
		}
	}

	/**
	 * Hides error block.
	 */
	static hideErrors(): void
	{
		const errorsContainer = document.querySelector('[data-role="sign-error-container"]');
		if (errorsContainer)
		{
			errorsContainer.parentNode.style.display = 'none';
			errorsContainer.innerText = '';
		}
	}

	static reloadCrmElementsList()
	{
		top.BX?.CRM?.Kanban?.Grid?.getInstance()?.reload();
		top.BX?.Main?.gridManager?.data[0]?.instance?.reload();
	}

	static #getNextStepBtn(): Element
	{
		return document.querySelector('[data-master-next-step-button]');
	}

	static #getPreviousStepBtn(): Element
	{
		return document.querySelector('[data-master-prev-step-button]');
	}

	static onPreviewFirstImageIsLoadedInPartnerStep()
	{
		Master.#loadedElementsInPartnerStep.preview = true;
		if (Master.#loadedElementsInPartnerStep.crmItemEditor)
		{
			Master.unLockNavigation();
			const btn = Master.#getNextStepBtn();
			if (btn)
			{
				btn.title = '';
			}
		}
	}

	static onNextBtnClickAtPartnerStep(event: PointerEvent)
	{
		if (!Master.#loadedElementsInPartnerStep.preview || !Master.#loadedElementsInPartnerStep.crmItemEditor)
		{
			event.stopImmediatePropagation();
		}
	}
}
