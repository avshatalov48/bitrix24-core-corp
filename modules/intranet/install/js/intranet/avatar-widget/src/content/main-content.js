import { Tag, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { AvatarRound, AvatarRoundExtranet, AvatarRoundGuest, AvatarBase } from 'ui.avatar';
import { WorkStatusControlPanel } from 'timeman.work-status-control-panel';
import { Content } from './content';
import { Analytics } from '../analytics';
import type { MainContentOptions } from '../types';
import { ExtensionTool } from '../tools/extension-tool';
import { SecurityTool } from '../tools/security-tool';
import type { BaseMainTool } from '../tools/base-main-tool';
import { MyDocumentsTool } from '../tools/my-documents-tool';
import { SalaryVacationTool } from '../tools/salary-vacation-tool';

export class MainContent extends Content
{
	#activeOnclick: boolean = true;

	getConfig(): Object
	{
		return {
			html: this.getLayout(),
		};
	}

	getOptions(): MainContentOptions
	{
		return super.getOptions();
	}

	#handleClickTaskStatus(event): void
	{
		event.stopPropagation();
		event.preventDefault();
	}

	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			this.#setEventHandlers();
			const onclick = () => {
				if (this.#activeOnclick)
				{
					BX.SidePanel.Instance.open(this.getOptions().url);
					Analytics.sendOpenProfile();
				}
			};

			return Tag.render`
				<div class="intranet-avatar-widget-item__wrapper" data-testid="bx-avatar-widget-content-main">
					<div onclick="${onclick}" class="intranet-avatar-widget-item-main__wrapper-head">
						<div class="intranet-avatar-widget-item__avatar">
							${this.#getAvatar().getContainer()}
						</div>
						<div class="intranet-avatar-widget-item__info-wrapper">
							${this.#getFullName()}
							${this.#getWorkPosition()}
						</div>
					</div>
					${this.#getStatus()}
					${this.#getWorkStatusBlock()}
					${this.#getToolsContainer()}
				</div>
			`;
		});
	}

	#getFullName(): HTMLElement
	{
		return this.cache.remember('title', () => {
			return Tag.render`
				<span class="intranet-avatar-widget-item__title">
					<span>${this.getOptions().fullName}</span>
					<i class="ui-icon-set --chevron-right-s intranet-avatar-widget-item__chevron"/>
				</span>
			`;
		});
	}

	#getWorkPosition(): ?HTMLElement
	{
		return this.cache.remember('workPosition', () => {
			if (!this.getOptions().workPosition)
			{
				return null;
			}

			return Tag.render`
				<span class="intranet-avatar-widget-item__description">${this.getOptions().workPosition}</span>
			`;
		});
	}

	#getAvatar(): AvatarBase
	{
		return this.cache.remember('avatar', () => {
			const options = {
				size: 48,
				userpicPath: encodeURI(this.getOptions().userPhotoSrc),
			};
			let avatar = null;

			if (this.getOptions().role === 'extranet')
			{
				avatar = new AvatarRoundExtranet(options);
			}
			else if (this.getOptions().role === 'collaber')
			{
				avatar = new AvatarRoundGuest(options);
			}
			else
			{
				avatar = new AvatarRound(options);
			}

			return avatar;
		});
	}

	#getStatus(): ?HTMLElement
	{
		return this.cache.remember('status', () => {
			if (!this.getOptions().status && !this.getOptions().vacation)
			{
				return null;
			}

			const wrapper = Tag.render`
				<div class="intranet-avatar-widget-main__status-wrapper"></div>
			`;

			if (this.getOptions().vacation)
			{
				Dom.append(Tag.render`
					<span class="intranet-avatar-widget-main__status --vacation">
						${this.getOptions().vacation}
					</span>
				`, wrapper);
			}

			if (this.getOptions().status)
			{
				const status = Tag.render`
					<span class="intranet-avatar-widget-main__status">
						${this.getOptions().status}
					</span>
				`;

				if (this.getOptions().role === 'collaber')
				{
					Dom.addClass(status, '--collaber');
				}
				else if (this.getOptions().role === 'extranet')
				{
					Dom.addClass(status, '--extranet');
				}

				Dom.append(status, wrapper);
			}

			return wrapper;
		});
	}

	#getWorkStatusBlock(): ?HTMLElement
	{
		return this.cache.remember('worktime', () => {
			if (!this.getOptions().isTimemanAvailable)
			{
				return null;
			}

			if (!this.#getWorkStatusControlPanel())
			{
				return null;
			}

			return Tag.render`
				<div
					class="intranet-avatar-widget-item__task-status task-status"
					onclick="${this.#handleClickTaskStatus}"
				>
					${this.#getWorkStatusControlPanel()}
				</div>
			`;
		});
	}

	#getWorkStatusControlPanel(): ?HTMLElement
	{
		return this.cache.remember('taskStatusActions', () => {
			try
			{
				return (new WorkStatusControlPanel()).renderWorkStatusControlPanel();
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		});
	}

	#getToolsContainer(): ?HTMLElement
	{
		return this.cache.remember('tools-container', () => {
			if (!this.getOptions().tools || Object.keys(this.getOptions().tools).length === 0)
			{
				return null;
			}

			const container = Tag.render`
				<div class="intranet-avatar-widget-main-tools__wrapper"></div>
			`;
			const tools = this.#getTools();

			tools.forEach((tool) => {
				Dom.append(tool.getLayout(), container);
			});

			return container;
		});
	}

	#getTools(): Array<BaseMainTool>
	{
		return this.cache.remember('tools', () => {
			const tools = this.getOptions().tools;

			return [
				tools.myDocuments ? new MyDocumentsTool(tools.myDocuments) : null,
				tools.salaryVacation ? new SalaryVacationTool(tools.salaryVacation) : null,
				tools.security ? new SecurityTool(tools.security) : null,
				tools.extension ? new ExtensionTool(tools.extension) : null,
			].filter(Boolean);
		});
	}

	#setEventHandlers(): void
	{
		EventEmitter.subscribe(
			'BX.Intranet.UserProfile:Avatar:changed',
			({ data: [{ url, userId }] }) => {
				if (this.getOptions().id > 0 && userId && this.getOptions().id.toString() === userId.toString())
				{
					const preparedUrl = encodeURI(url);
					this.getOptions().userPhotoSrc = preparedUrl;
					this.#getAvatar().setUserPic(preparedUrl);
				}
			},
		);
		EventEmitter.subscribe(
			'BX.Intranet.UserProfile:Name:changed',
			({ data: [{ fullName }] }) => {
				this.getOptions().fullName = fullName;
				this.#getFullName().querySelector('span').innerHTML = fullName;
			},
		);
		EventEmitter.subscribe('BX.Intranet.AvatarWidget.Popup:enabledAutoHide', () => {
			this.#activeOnclick = true;
		});
		EventEmitter.subscribe('BX.Intranet.AvatarWidget.Popup:disabledAutoHide', () => {
			this.#activeOnclick = false;
		});
	}
}
