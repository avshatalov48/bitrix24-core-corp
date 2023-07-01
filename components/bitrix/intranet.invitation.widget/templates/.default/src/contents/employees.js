import { Content } from "./content";
import { Tag, Loc, ajax, Text } from "main.core";
import type { EmployeesContentOptions } from "../types/options";
import type { ConfigContent } from "../types/content";
import { Menu } from "main.popup";
import { EventEmitter, BaseEvent } from 'main.core.events';

export class EmployeesContent extends Content
{
	#rightType: string;

	constructor(options: EmployeesContentOptions) {
		super(options);
		this.setEventNamespace('BX.Intranet.InvitationWidget.EmployeesContent');
	}

	getConfig(): ConfigContent
	{
		return {
			html: this.getOptions().awaitData.then((response) => {
				this.setOptions({
					...response.data.users,
					...this.getOptions(),
				});
				return this.getLayout();
			}),
			flex: 5,
			sizeLoader: 55,
		};
	}

	getLayout(): HTMLDivElement
	{
		return this.cache.remember('layout', () => {
			return Tag.render`
				<div class="intranet-invitation-widget-item intranet-invitation-widget-item--emp ${this.getOptions().isLimit ? 'intranet-invitation-widget-item--emp-alert' : null}">
					<div class="intranet-invitation-widget-inner">
						<div class="intranet-invitation-widget-content">
							<div class="intranet-invitation-widget-item-content">
								<div class="intranet-invitation-widget-item-progress ${this.getOptions().isLimit ? 'intranet-invitation-widget-item-progress--crit' : 'intranet-invitation-widget-item-progress--full'}"/>
								<div class="intranet-invitation-widget-employees">
									<div class="intranet-invitation-widget-item-name">
										<span>
											${Loc.getMessage('INTRANET_INVITATION_WIDGET_EMPLOYEES')}
										</span>
									</div>
									<div class="intranet-invitation-widget-item-num">
										${this.getOptions().currentUserCountMessage}
									</div>
								</div>
							</div>
							${this.getDetail()}
							${this.getOptions().isAdmin ? this.getSelectorRights() : null}
						</div>
					</div>
				</div>
			`;
		});
	}

	getDetail(): HTMLDivElement
	{
		return this.cache.remember('detail', () => {
			let content = '';

			if (Number(this.getOptions().maxUserCount) === 0)
			{
				content = Loc.getMessage('INTRANET_INVITATION_WIDGET_EMPLOYEES_NO_LIMIT');
			}
			else if (this.getOptions().isLimit)
			{
				content = Loc.getMessage('INTRANET_INVITATION_WIDGET_EMPLOYEES_LIMIT');
			}
			else
			{
				content = this.getOptions().leftCountMessage;
			}

			return Tag.render`
				<div class="intranet-invitation-widget-item-detail">
					<span class="intranet-invitation-widget-item-link-text">
						${content}
					</span>
				</div>
			`;
		});
	}

	getSelectorRights(): HTMLDivElement
	{
		return this.cache.remember('selector-rights', () => {
			const showMenu = (e) => {
				e.stopPropagation();
				this.getRightsMenu(e.target).toggle();
			}

			const button = Tag.render`
				<div onclick="${showMenu}" class="intranet-invitation-widget-item-menu"></div>
			`;

			this.subscribe('right-selected', (event) => {
				const menu = this.getRightsMenu(button);
				menu.close();
				menu.destroy();
				if (event.data.type)
				{
					this.cache.delete('menu-rights');
					this.#rightType = event.data.type;
				}
			});

			return button;
		});
	}

	getRightsMenu(element: HTMLElement): Menu
	{
		return this.cache.remember('menu-rights', () => {
			return new Menu(`menu-rights-${Text.getRandom()}`, element, this.getMenuRightsItems(), {
				autoHide: true,
				offsetLeft: 10,
				offsetTop: 0,
				angle: true,
				className: 'license-right-popup-men',
				events: {
					onPopupShow: (popup) => {
						EventEmitter.emit(
							EventEmitter.GLOBAL_TARGET,
							this.getEventNamespace() + ':showRightMenu',
							new BaseEvent({
								data: {
									popup: popup,
								}
							})
						);
					},
					onPopupClose: (popup) => {
						EventEmitter.emit(
							EventEmitter.GLOBAL_TARGET,
							this.getEventNamespace() + ':closeRightMenu',
							new BaseEvent({
								data: {
									popup: popup,
								}
							})
						);
					},
					onPopupFirstShow: (popup) => {
						EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onOpenStart', () => {
							popup.close();
						});
					},
				}
			});
		});
	}

	getMenuRightsItems(): Array
	{
		if (!this.#rightType)
		{
			this.#rightType = this.getOptions().rightType;
		}

		return [
			{
				text: Loc.getMessage('INTRANET_INVITATION_WIDGET_SETTING_ALL_INVITE'),
				className: this.#rightType === 'all' ? 'menu-popup-item-accept' : '',
				onclick: () => {
					this.saveInvitationRightSetting('all').then(() => {
						this.emit('right-selected', new BaseEvent({
							data: {
								type: 'all'
							}
						}));
					});
				}
			},
			{
				text: Loc.getMessage('INTRANET_INVITATION_WIDGET_SETTING_ADMIN_INVITE'),
				className: this.#rightType === 'admin' ? 'menu-popup-item-accept' : '',
				onclick: () => {
					this.saveInvitationRightSetting('admin').then(() => {
						this.emit('right-selected', new BaseEvent({
							data: {
								type: 'admin'
							}
						}));
					});
				}
			}
		];
	}

	saveInvitationRightSetting(type): Promise
	{
		return ajax.runAction("intranet.invitationwidget.saveInvitationRight", {
			data: {
				type: type
			}
		});
	}

}
