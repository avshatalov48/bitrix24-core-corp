import {Cache, Type, Dom, Text, Tag, Loc, Runtime} from 'main.core';
import {EventEmitter} from 'main.core.events';
import 'main.date';
import Input from './input';
import {SaveButton, CloseButton, ButtonState} from 'ui.buttons';
import {Popup} from 'main.popup';

export default class InputExtended extends Input
{
	constructor(objectId, data)
	{
		super(objectId, data);
	}

	adjustData()
	{
		if (this.data.id === null)
		{
			this.getSwitcher().check(false, false);
			this.showUnchecked();
			if (this.cache.get('popup'))
			{
				this.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsSet', 'N');
			}
		}
		else
		{
			this.getSwitcher().check(true, false);
			this.showChecked();

			this.getLinkContainer().innerHTML = Text.encode(this.data.link);
			this.getLinkContainer().href = Text.encode(this.data.link);

			this.getPasswordContainer().innerHTML = this.data.hasPassword
				? Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_WITH_PASSWORD')
				: Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_WITHOUT_PASSWORD');

			this.getDeathTimeContainer().innerHTML = this.data.hasDeathTime
				? Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_BEFORE')
					.replace(
						'#deathTime#',
						BX.Main.Date.format(
							BX.Main.Date.convertBitrixFormat(Loc.getMessage('FORMAT_DATETIME').replace(':SS', '')),
							new Date(this.data.deathTimeTimestamp * 1000)
						)
					)
				: Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_FOREVER');

			if (this.data.availableEdit === true)
			{
				this.getRightsContainer().innerHTML = ', ' + (this.data.canEditDocument
					? Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_RIGHTS_CAN_EDIT')
					: Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_RIGHTS_CAN_READ'));
				this.getRightsContainer().style.display = '';
			}
			else
			{
				this.getRightsContainer().style.display = 'none';
			}

			if (this.cache.get('popup'))
			{
				this.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsSet', 'Y');
			}
		}
	}

	getContainer()
	{
		return this.cache.remember('main', () => {
			const copyButton = Tag.render`<div class="disk-control-external-link-link-icon"></div>`;
			BX.clipboard.bindCopyClick(copyButton, {text: () => { return this.data.link; }});
			this.showSettings = this.showSettings.bind(this);
			return Tag.render`
				<div class="disk-control-external-link-block${this.data.id !== null ? ' disk-control-external-link-block--active' : ''} disk-control-external-link-block--tunable">
					<div class="disk-control-external-link">
						<div class="disk-control-external-link-btn">
							${this.getSwitcher().getNode()}
						</div>
						<div class="disk-control-external-link-main">
							<div class="disk-control-external-link-link-box">
								${this.getLinkContainer()}
								${copyButton}
							</div>
							<div class="disk-control-external-link-subtitle" onclick="${this.showSettings}">${this.getDeathTimeContainer()}<span>, </span>${this.getPasswordContainer()}${this.getRightsContainer()}</div>
							<div class="disk-control-external-link-text">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_IS_NOT_PUBLISHED')}</div>
							<div class="disk-control-external-link-skeleton"></div>
						</div>
						<div class="disk-public-link-config" onclick="${this.showSettings}"></div>
					</div>
					<div class="disk-control-external-link-settings">
						${this.getDeathTimeSettingsContainer()}
						${this.getPasswordSettingsContainer()}
						${this.getEditSettingsContainer()}
					</div>
				</div>
			`;
		});
	}

	showSettings()
	{
		this.cache.set('settingsAreShown', 'Y');
		if (this.cache.get('popup'))
		{
			this.cache.get('popup').getPopupContainer().setAttribute('settingsAreShown', 'Y');
		}
	}

	hideSettings()
	{
		this.cache.set('settingsAreShown', 'N');
		if (this.cache.get('popup'))
		{
			this.cache.get('popup').getPopupContainer().setAttribute('settingsAreShown', 'N');
		}
	}

	getDeathTimeSettingsContainer()
	{
		return this.cache.remember('deathTimeSettings', () => {
			const deathTimeSettings = Tag.render`
			<div class="ui-form-line">
				<input type="checkbox" name="hasDeathTime">
				<div class="ui-form-row">
					<label class="ui-ctl ui-ctl-checkbox">
						<input type="checkbox" class="ui-ctl-element" name="enableDeathTime">
						<div class="ui-ctl-label-text">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_DEATHTIME_LIMIT_CHECKBOX')}</div>
					</label>
				</div>
				<div class="ui-form-row-inline" name="deathTimeIsNotSaved">
					<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-textbox ui-ctl-w25 ui-ctl-inline">
							<input type="number" min="1" name="deathTimeValue" class="ui-ctl-element" value="10" size="4">
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-inline ui-ctl-w50">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element" name="deathTimeMeasure">
								<option value="60" selected>${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_MINUTES')}</option>
								<option value="3600">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_HOURS')}</option>
								<option value="86400">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_DAYS')}</option>
							</select>
						</div>
					</div>
				</div>
				<div class="ui-form-row-inline" name="deathTimeIsSaved">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_DEATHTIME_LIMIT_PREPOSITION')}</div>
					</div>
					<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-no-border">
							<div class="ui-ctl-element" name="deathTimeParsed">14.10.2014 16:33</div>
							<button name="deathTimeButtonUnset" class="ui-ctl-after ui-ctl-icon-clear"></button>
						</div>
					</div>
				</div>
			</div>`;

			/*region bind settings form */
			const onDeathTimeHasChanged = () => {
				if (!(this.data['id'] > 0))
				{
					return;
				}

				if (deathTimeSettings.querySelector('input[name=enableDeathTime]').checked === true)
				{
					deathTimeSettings.querySelector('input[name=deathTimeValue]').disabled = false;
					deathTimeSettings.querySelector('[name=deathTimeMeasure]').disabled = false;
				}
				else
				{
					deathTimeSettings.querySelector('input[name=deathTimeValue]').disabled = true;
					deathTimeSettings.querySelector('input[name=deathTimeValue]').value = '10';
					deathTimeSettings.querySelector('[name=deathTimeMeasure]').disabled = true;
					deathTimeSettings.querySelector('[name=deathTimeMeasure]').value = '60';
				}
			};
			deathTimeSettings.querySelector('input[name=enableDeathTime]').addEventListener('click', () => {
				onDeathTimeHasChanged();
				deathTimeSettings.querySelector('input[name=enableDeathTime]').dataset.changed = 'Y';
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Change', {field: 'deathTime'});
			});
			deathTimeSettings.querySelector('button[name=deathTimeButtonUnset]').addEventListener('click', () => {
				deathTimeSettings.querySelector('input[name=hasDeathTime]').checked = false;
				deathTimeSettings.querySelector('input[name=enableDeathTime]').dataset.changed = 'Y';
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Change', {field: 'deathTime'});
			});

			const adjustSettings = () => {
				if (!(this.data['id'] > 0))
				{
					return;
				}

				deathTimeSettings.querySelector('input[name=enableDeathTime]').dataset.changed = 'N';
				if (this.data['hasDeathTime'])
				{
					deathTimeSettings.querySelector('input[name=hasDeathTime]').checked = true;
					deathTimeSettings.querySelector('div[name=deathTimeParsed]').innerHTML = BX.Main.Date.format(
						BX.Main.Date.convertBitrixFormat(Loc.getMessage('FORMAT_DATETIME').replace(':SS', '')),
						new Date(this.data.deathTimeTimestamp * 1000)
					);
					deathTimeSettings.querySelector('input[name=enableDeathTime]').checked = true;
				}
				else
				{
					deathTimeSettings.querySelector('input[name=hasDeathTime]').checked = false;
					deathTimeSettings.querySelector('input[name=enableDeathTime]').checked = false;
				}
				onDeathTimeHasChanged();
			};
			EventEmitter.subscribe(this, 'Disk:ExternalLink:DataSet', adjustSettings);
			adjustSettings();
			/*endregion*/

			return deathTimeSettings;
		});
	}

	getPasswordSettingsContainer()
	{
		return this.cache.remember('passwordSettings', () => {
			const passwordSettings = Tag.render`
			<div class="ui-form-line">
				<input type="checkbox" name="hasPassword">
				<div class="ui-form-row">
					<label class="ui-ctl ui-ctl-checkbox">
						<input type="checkbox" class="ui-ctl-element" name="enablePassword">
						<div class="ui-ctl-label-text">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_PASSWORD_CHECKBOX')}</div>
					</label>
				</div>
				<div class="ui-form-row-inline" name="passwordIsNotSaved">
					<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon">
							<input type="password" name="passwordValue" class="ui-ctl-element" placeholder="${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_PASSWORD_PLACEHOLDER')}" autocomplete="nope">
							<button class="ui-ctl-after ui-ctl-icon-angle disk-external-link-setting-popup-password-show" name="passwordTypeSwitcher"></button>
						</div>
					</div>
				</div>
				<div class="ui-form-row-inline" name="passwordIsSaved">
					<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-disabled ui-ctl-after-icon">
							<input type="password" class="ui-ctl-element" readonly value="some password">
							<button name="passwordButtonUnset" class="ui-ctl-after ui-ctl-icon-clear"></button>
						</div>
					</div>
				</div>
			</div>
			`;

			/*region bind settings form */
			const passwordValue = passwordSettings.querySelector('input[name=passwordValue]');

			const onPasswordHasChanged = () => {
				if (!(this.data['id'] > 0))
				{
					return;
				}

				if (passwordSettings.querySelector('input[name=enablePassword]').checked === true)
				{
					passwordValue.disabled = false;
				}
				else
				{
					passwordValue.disabled = true;
					passwordValue.value = '';
					passwordValue.type = 'password';
				}
			};
			passwordSettings.querySelector('input[name=enablePassword]').addEventListener('click', () => {
				onPasswordHasChanged();
				passwordSettings.querySelector('input[name=enablePassword]').dataset.changed = 'Y';
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Change', {field: 'password'});
			});
			passwordSettings.querySelector('button[name=passwordButtonUnset]').addEventListener('click', () => {
				passwordSettings.querySelector('input[name=hasPassword]').checked = false;
				passwordSettings.querySelector('input[name=enablePassword]').dataset.changed = 'Y';
				passwordValue.value = '';
				passwordValue.type = 'password';
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Change', {field: 'password'});
			});
			passwordSettings.querySelector('button[name=passwordTypeSwitcher]').addEventListener('click', () => {
				passwordValue.type = (passwordValue.type === 'text' ? 'password' : 'text');});

			const adjustSettings = () => {
				if (!(this.data['id'] > 0))
				{
					return;
				}
				passwordSettings.querySelector('input[name=enablePassword]').dataset.changed = 'N';
				passwordSettings.querySelector('input[name=hasPassword]').checked = this.data['hasPassword'] === true;
				passwordSettings.querySelector('input[name=enablePassword]').checked = this.data['hasPassword'] === true;
				onPasswordHasChanged();
			};
			EventEmitter.subscribe(this, 'Disk:ExternalLink:DataSet', adjustSettings);
			adjustSettings();
			/*endregion*/

			return passwordSettings;
		});
	}

	getEditSettingsContainer()
	{
		return this.cache.remember('editSettings', () => {
			const editSettings = Tag.render`
			<div class="ui-form-line">
				<div class="ui-form-row">
					<label class="ui-ctl ui-ctl-checkbox">
						<input type="checkbox" class="ui-ctl-element" name="canEditDocument">
						<div class="ui-ctl-label-text">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_ALLOW_EDITING')}</div>
					</label>
				</div>
			</div>
			`;
			/*region bind settings form */
			const canEditDocument = editSettings.querySelector('input[name=canEditDocument]');
			canEditDocument.addEventListener('click', () => {
				canEditDocument.dataset.changed = 'Y';
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Change', {field: 'canEditDocument'});
			});
			const adjustSettings = () => {
				canEditDocument.checked = (this.data['canEditDocument'] === true);
				canEditDocument.dataset.changed = 'N';
				if (this.data['availableEdit'] !== true)
				{
					editSettings.style.display = 'none';
					canEditDocument.disable = true;
				}
				else
				{
					editSettings.style.display = '';
					delete editSettings.style.display;
					delete canEditDocument.disable;
				}
			};
			EventEmitter.subscribe(this, 'Disk:ExternalLink:DataSet', adjustSettings);
			adjustSettings();
			/*endregion*/

			return editSettings;
		})
	}

	saveSettings()
	{
		if (!(this.data.id > 0))
		{
			return;
		}

		const settings = this.getContainer();
		/*region DeathTime */
		if (settings.querySelector('input[name=enableDeathTime]').dataset.changed === 'Y')
		{
			const deathTimer = parseInt(settings.querySelector('input[name=deathTimeValue]').value)
				* parseInt(settings.querySelector('[name=deathTimeMeasure]').value);
			const enableDeathTime = (settings.querySelector('input[name=enableDeathTime]').checked === true && deathTimer > 0);
			if (enableDeathTime === true)
			{
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', () => {});
				const deathTimeTimestamp = Math.floor(Date.now() / 1000) + deathTimer;
				this.getBackend()
					.setDeathTime(this.data.id, deathTimeTimestamp)
					.then(({data: {externalLink: {hasDeathTime, deathTimeTimestamp, deathTime}}}) => {
						this.setData({
							hasDeathTime: hasDeathTime,
							deathTimeTimestamp: deathTimeTimestamp,
							deathTime: deathTime,
						});
					})
					.finally(() => {
						EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Saved', () => {});
					});
			}
			else if (enableDeathTime !== this.data.hasDeathTime)
			{
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', () => {});
				this.getBackend()
					.revokeDeathTime(this.data['id'])
					.then(() => {
						this.setData({
							hasDeathTime: false,
							deathTimeTimestamp: null,
							deathTime: null,
						});
					})
					.finally(() => {
						EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Saved', () => {});
					});
			}
		}
		/*endregion*/
		/*region Password*/
		if (settings.querySelector('input[name=enablePassword]').dataset.changed === 'Y')
		{
			const passwordValue = settings.querySelector('input[name=passwordValue]').value.trim();
			const enablePassword = (settings.querySelector('input[name=enablePassword]').checked === true && passwordValue.length > 0);
			if (enablePassword === true)
			{
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', () => {});
				this.getBackend()
					.setPassword(this.data['id'], passwordValue)
					.then(() => {
						this.setData({ hasPassword: true });
					})
					.finally(() => {
						EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Saved', () => {});
					});
			}
			else if (enablePassword !== this.data.hasPassword)
			{
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', () => {});
				this.getBackend()
					.revokePassword(this.data['id'])
					.then(() => {
						this.setData({ hasPassword: false });
					})
					.finally(() => {
						EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Saved', () => {});
					});
			}
		}
		/*endregion*/
		/*region editing rights */
		const canEditDocumentNode =settings.querySelector('input[name=canEditDocument]');
		if (canEditDocumentNode
			&& canEditDocumentNode.dataset.changed === 'Y'
			&& canEditDocumentNode.checked !== this.data.canEditDocument)
		{
			if (canEditDocumentNode.checked)
			{
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', () => {});
				this.getBackend()
					.allowEditDocument(this.data['id'])
					.then(() => {
						this.setData({ canEditDocument: true });
					})
					.finally(() => {
						EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Saved', () => {});
					});
			}
			else
			{
				EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Save', () => {});
				this.getBackend()
					.disallowEditDocument(this.data['id'])
					.then(() => {
						this.setData({ canEditDocument: false });
					})
					.finally(() => {
						EventEmitter.emit(this, 'Disk:ExternalLink:Settings:Saved', () => {});
					});
			}
		}
		/*endregion*/
	}

	getPopup()
	{
		return this.cache.remember('popup', () => {
			const popupSave = new SaveButton({
				state: ButtonState.DISABLED,
				onclick: () => {
					this.saveSettings();
				}
			});
			popupSave.saveCounter = 0;
			EventEmitter.subscribe(this, 'Disk:ExternalLink:Settings:Save', () => {
				this.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsWaiting', 'Y');
				popupSave.saveCounter++;
				popupSave.setWaiting();
			});
			EventEmitter.subscribe(this, 'Disk:ExternalLink:Settings:Saved', () => {
				popupSave.saveCounter--;
				if (popupSave.saveCounter <= 0)
				{
					this.cache.get('popup').getPopupContainer().setAttribute('externalLinkIsWaiting', 'N');
					popupSave.setDisabled(true);
				}
			});
			EventEmitter.subscribe(this, 'Disk:ExternalLink:Settings:Change', () => {
				popupSave.setDisabled(false);
			});
			EventEmitter.subscribe(this, 'Disk:ExternalLink:DataSet', () => {
				popupSave.setDisabled(true);
			});

			const popup = new Popup({
				uniquePopupId: 'disk-external-link',
				className: 'disk-external-link-popup',
				titleBar: Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_TITLE'),
				content: this.getContainer(),
				autoHide: true,
				closeIcon: true,
				closeByEsc: true,
				overlay: true,
				cacheable: false,
				minWidth: 410,
				events: {
					onClose: () => {
						this.cache.delete('popup');
					}
				},
				buttons: [
					popupSave,
					new CloseButton({
						events: {
							click: function () {
								popup.close();
							}
						}
					})
				]
			});
			popup.getPopupContainer().setAttribute('externalLinkIsSet', this.data.id > 0 ? 'Y' : 'N');
			popup.getPopupContainer().setAttribute('settingsAreShown', this.cache.get('settingsAreShown') === 'Y' ? 'Y' : 'N');
			return popup;
		});
	}

	show()
	{
		this.getPopup().show();
	}
}
