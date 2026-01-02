import { Event, Runtime, Type, ajax, Dom } from 'main.core';
import { BaseCache, MemoryCache } from 'main.core.cache';
import { EventEmitter } from 'main.core.events';
import { Counter, CounterStyle } from 'ui.cnt';
import { WidgetLoader } from 'intranet.widget-loader';
import { AvatarWidget } from 'intranet.avatar-widget';
import { PULL } from 'pull.client';
import { WorkTimeStateIcon } from 'timeman.work-time-state-icon';

type AvatarButtonOptions = {
	userId: number,
	skeleton: Object,
	signDocumentsCounter: number,
	signDocumentsPullEventName: string,
	workTimeAvailable: boolean,
	workTimeState: string,
	workTimeAction: string,
}

export class AvatarButton
{
	static #avatarWrapper: HTMLElement;
	static #cache: BaseCache<any> = new MemoryCache();
	static #options: AvatarButtonOptions;

	static init(options: AvatarButtonOptions): void
	{
		this.#options = options;
		this.#avatarWrapper = document.querySelector('[data-id="bx-avatar-widget"]');
		this.#setEventHandlerForChangeAvatar();

		if (this.#options.signDocumentsCounter > 0)
		{
			this.#showCounter();
			this.#setEventHandlerForUpdateCounter();
		}

		if (this.#options.workTimeAvailable)
		{
			this.#showWorkTimeState();
		}

		Event.bind(this.#avatarWrapper, 'click', () => {
			Event.unbindAll(this.#avatarWrapper);
			this.#getWidgetLoader().getPopup().setFixed(true);
			this.#getWidgetLoader()
				.createSkeletonFromConfig(options.skeleton)
				.show();
			this.#setHiddenAvatar();
			this.#getWidgetLoader().getPopup().subscribe('onClose', () => {
				this.#setVisibleAvatar();
			});
			this.#getWidgetLoader().getPopup().subscribe('onShow', () => {
				this.#setHiddenAvatar();
			});
			Runtime.loadExtension(['intranet.avatar-widget']).then(() => {
				this.#showWidget();
			}).catch(() => {});
		});
	}

	static #setHiddenAvatar(): void
	{
		Dom.style(this.#avatarWrapper, 'opacity', '0');
	}

	static #setVisibleAvatar(): void
	{
		Dom.style(this.#avatarWrapper, 'opacity', '1');
	}

	static #showWidget(): void
	{
		this.#getContent().then((response) => {
			this.#getWidgetLoader().clearBeforeInsertContent();
			AvatarWidget.getInstance().setOptions({
				buttonWrapper: this.#avatarWrapper,
				loader: this.#getWidgetLoader().getPopup(),
				data: response.data,
			}).show();
			Event.bind(this.#avatarWrapper, 'click', () => {
				AvatarWidget.getInstance().show();
			});
		}).catch(() => {});
	}

	static #getWidgetLoader(): WidgetLoader
	{
		return this.#cache.remember('widgetLoader', () => {
			return new WidgetLoader({
				id: 'bx-avatar-header-popup',
				bindElement: this.#avatarWrapper,
				className: 'intranet-avatar-widget-base-popup',
				width: 450,
				useAngle: false,
				fixed: true,
				offsetTop: -50,
				offsetLeft: -392,
			});
		});
	}

	static #getContent(): Promise
	{
		return this.#cache.remember('content', () => {
			return new Promise((resolve, reject) => {
				ajax.runAction('intranet.user.widget.getContent')
					.then((response) => resolve(response))
					.catch((response) => reject(response))
				;
			});
		});
	}

	static #showCounter(): void
	{
		this.#getCounter().renderTo(this.#getCounterWrapper());
	}

	static #showWorkTimeState(): void
	{
		this.#getWorkTimeState().renderTo(this.#getWorkTimeStateWrapper());
	}

	static #getCounterWrapper(): HTMLElement
	{
		return this.#cache.remember('counterWrapper', () => {
			return this.#avatarWrapper.querySelector('.air-user-profile-avatar__counter');
		});
	}

	static #getWorkTimeStateWrapper(): HTMLElement
	{
		return this.#cache.remember('workTimeStateWrapper', () => {
			return this.#avatarWrapper.querySelector('.air-user-profile-avatar__work-time-state');
		});
	}

	static #getCounter(): Counter
	{
		return this.#cache.remember('counter', () => {
			return new Counter({
				color: Counter.Color.DANGER,
				size: Counter.Size.MEDIUM,
				value: this.#options.signDocumentsCounter,
				useAirDesign: true,
				style: CounterStyle.FILLED_ALERT,
			});
		});
	}

	static #getWorkTimeState(): WorkTimeStateIcon
	{
		return this.#cache.remember('workTimeState', () => {
			return new WorkTimeStateIcon({
				state: this.#options.workTimeState,
				action: this.#options.workTimeAction,
			});
		});
	}

	static #setEventHandlerForUpdateCounter(): void
	{
		PULL.subscribe({
			moduleId: 'sign',
			command: this.#options.signDocumentsPullEventName,
			callback: (params) => {
				if (!Type.isNumber(params?.needActionCount))
				{
					return;
				}

				if (params?.needActionCount > 0)
				{
					this.#getCounter().update(params.needActionCount);
				}
				else
				{
					this.#getCounter().destroy();
				}
			},
		});
	}

	static #setEventHandlerForChangeAvatar(): void
	{
		const avatar = this.#avatarWrapper.querySelector('i');

		EventEmitter.subscribe(
			'BX.Intranet.UserProfile:Avatar:changed',
			(event) => {
				const data = event.getData()[0];
				const url = data && data.url ? data.url : '';
				const eventUserId = data && data.userId ? data.userId : 0;

				if (this.#options.userId === eventUserId && avatar)
				{
					avatar.style = Type.isStringFilled(url)
						? `background-size: cover; background-image: url('${encodeURI(url)}')`
						: ''
					;
				}
			},
		);
	}
}
