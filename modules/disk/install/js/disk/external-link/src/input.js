import {Cache, Type, Dom, Text, Tag, Loc, Runtime} from 'main.core';
import {EventEmitter} from 'main.core.events';
import 'ui.design-tokens';
import 'ui.fonts.opensans';
import 'clipboard';
import 'ui.switcher';
import 'ui.layout-form';
import 'main.date';
import {Backend} from './backend';

type exportLinkType = {
	id: ?number,
	link: ?string,
	hash: ?string,
	hasPassword: ?boolean,
	hasDeathTime: ?boolean,
	availableEdit: ?boolean,
	canEditDocument: ?boolean,
	deathTime: ?string,
	deathTimeTimestamp ?: number,
};

export default class Input
{
	cache = new Cache.MemoryCache();
	objectId: Number;
	data: exportLinkType = {};

	constructor(objectId, data: ?exportLinkType)
	{
		this.bindEvents();
		if (Type.isPlainObject(objectId))
		{
			this.objectId = parseInt(objectId['objectId']);
			this.setData(objectId, false);
		}
		else
		{
			this.objectId = parseInt(objectId);
			this.setData(data, false);
		}
	}

	setData(data, fireEvent: boolean = true)
	{
		console.log('data: ', data);

		if (data && Type.isPlainObject(data))
		{
			this.data = Object.assign(this.data, data);
			this.data['id'] = this.data['id'] === null ? this.data['id'] : parseInt(this.data['id']);
		}
		else
		{
			this.data = {
				id: null,
				link: null,
				hash: null,
				hasPassword: null,
				hasDeathTime: null,
				availableEdit: null,
				canEditDocument: null,
				deathTime: null,
				deathTimeTimestamp: null,
			};
		}
		this.adjustData();
		EventEmitter.emit(this, 'Disk:ExternalLink:DataSet', data);

		if (fireEvent !== false)
		{
			EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'Disk:ExternalLink:HasChanged', {
				objectId: this.objectId,
				data: this.data,
				target: this
			});
		}
	}

	adjustData()
	{
		if (this.data.id === null)
		{
			this.getSwitcher().check(false, false);
			this.showUnchecked();
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
		}
	}

	bindEvents()
	{
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'Disk:ExternalLink:HasChanged',
			({data: {objectId, data, target}}: BaseEvent) => {
				if (objectId !== this.objectId || Object.is(target, this))
				{
					return;
				}
				this.setData(data, false);
			}
		);
	}

	getBackend()
	{
		return Backend;
	}

	getContainer()
	{
		return this.cache.remember('main', () => {
			const copyButton = Tag.render`<div class="disk-control-external-link-link-icon"></div>`;
			BX.clipboard.bindCopyClick(copyButton, {text: () => { return this.data.link; }});

			const tune = () => { return this.constructor.showPopup(this.objectId, this.data); };

			return Tag.render`
				<div class="disk-control-external-link-block${this.data.id !== null ? ' disk-control-external-link-block--active' : ''}">
					<div class="disk-control-external-link">
						<div class="disk-control-external-link-btn">
							${this.getSwitcher().getNode()}
						</div>
						<div class="disk-control-external-link-main">
							<div class="disk-control-external-link-link-box">
								${this.getLinkContainer()}
								${copyButton}
							</div>
							<div class="disk-control-external-link-subtitle" onclick="${tune}">${this.getDeathTimeContainer()}, ${this.getPasswordContainer()}${this.getRightsContainer()}</div>
							<div class="disk-control-external-link-text">${Loc.getMessage('DISK_EXTENSION_EXTERNAL_LINK_IS_NOT_PUBLISHED')}</div>
							<div class="disk-control-external-link-skeleton"></div>
						</div>
					</div>
				</div>
				`;
		});
	}

	getSwitcher()
	{
		return this.cache.remember('switcher', () => {
			const switcherNode = document.createElement('span');
			switcherNode.className = 'ui-switcher';
			let switcher = new BX.UI.Switcher({
				node: switcherNode,
				checked: this.data.id !== null,
				inputName: 'ACTIVE',
				color: 'green',
			});
			switcher.handlers = {toggled: this.toggle.bind(this, {target: switcher})};

			return switcher;
		});
	}

	getLinkContainer()
	{
		return this.cache.remember('link', () => {
			return Tag.render`<a href="${Text.encode(this.data.link)}" class="disk-control-external-link-link" target="_blank">${Text.encode(this.data.link)}</a>`;
		});
	}

	getRightsContainer()
	{
		return this.cache.remember('rights', () => {
			return document.createElement('span');
		});
	}

	getDeathTimeContainer()
	{
		return this.cache.remember('deathTime', () => {
			return document.createElement('span');
		});
	}

	getPasswordContainer()
	{
		return this.cache.remember('password', () => {
			return document.createElement('span');
		});
	}

	toggle({target}: BaseEvent)
	{
		if (target.checked)
		{
			this.showLoader();
			this.getBackend().generateExternalLink(this.objectId)
				.then(({data: {externalLink}}) => {
					this.setData(externalLink);
					this.hideLoader();
				});
		}
		else
		{
			this.getBackend().disableExternalLink(this.objectId);
			this.setData(null);
		}
	}

	showChecked()
	{
		const baseClassName = this.getContainer().classList.item(0);
		const activeClassName = [baseClassName, '--active'].join('');
		this.getContainer().classList.add(activeClassName);
	}

	showUnchecked()
	{
		const baseClassName = this.getContainer().classList.item(0);
		const activeClassName = [baseClassName, '--active'].join('');
		this.getContainer().classList.remove(activeClassName);
	}

	showLoader()
	{
		Dom.addClass(this.getContainer(), 'disk-control-external-link-skeleton--active');
	}

	hideLoader()
	{
		Dom.removeClass(this.getContainer(), 'disk-control-external-link-skeleton--active');
	}

	reload()
	{
		this.showLoader();
		return this.getBackend().getExternalLink(this.objectId)
			.then(({data}) => {
				this.setData(data && data.externalLink ? data.externalLink : null);
				this.hideLoader();
			});
	}
}
