import { Tag, Loc, Type, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';
import DepartmentControl from 'intranet.department-control';
import { SelfRegister } from '../self-register';
import { Transport } from '../transport';
import { Page } from './page';
import { SubmitButton } from '../submit-button';

export class LinkPage extends Page
{
	#container: HTMLElement;
	#isAdmin: boolean;
	#isCloud: boolean;
	#fastRegistrationAvailable: boolean;
	#needConfirmRegistration: boolean;
	#wishlist: string;
	#departmentControl: DepartmentControl;

	#linkRegisterEnabled: boolean;
	#analytics: Analytics;
	#transport: Transport;
	#btnState: string;

	constructor(options)
	{
		super();
		this.#isAdmin = options.isAdmin === true;
		this.#isCloud = options.isCloud === true;
		this.#fastRegistrationAvailable = options.fastRegistrationAvailable === true;
		this.#needConfirmRegistration = options.needConfirmRegistration === true;
		this.#wishlist = Type.isStringFilled(options.wishlist) ? options.wishlist : '';
		this.#departmentControl = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
		this.#linkRegisterEnabled = options.linkRegisterEnabled;
		this.#analytics = options.analytics;
		this.#transport = options.transport;
		this.#btnState = SubmitButton.DISABLED_STATE;
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="self-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-link">
						<div class="ui-icon-set --link-3"></div>
					</div>
					<div class="invite-title-text">${Loc.getMessage('INTRANET_INVITE_DIALOG_FAST_REG_TITLE')}</div>
					<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=6546149');"></div>
				</div>
				<form method="POST" name="SELF_DIALOG_FORM">
					${this.#isAdmin ? this.#renderFastRegistrationSwitcher() : ''}
					<div class="invite-content-container --department-bg">
						<div class="invite-form-container">
							<div 
								style="border-top: none; ${this.#fastRegistrationAvailable ? '' : 'display: none;'}" 
								data-role="selfSettingsBlock" 
								id="intranet-dialog-tab-content-self-block" 
								class="invite-dialog-inv-link-block"
							>
								<div id="invitation-department-status" class="invitation-department__status"></div>
								<div>
									${this.#isAdmin ? '' : this.#renderCopyBtnDescription()}
									<div class="intranet-invitation__link-department-control"></div>
									<div class="invite-form-container-reg-row">
										<span class="ui-btn ui-btn-primary ui-btn-themes ui-btn-icon-copy invite-form-btn-copy" data-role="copyRegisterUrlButton">
											${Loc.getMessage('BX24_INVITE_DIALOG_COPY_LINK')}
										</span>
										${this.#isAdmin ? this.#renderUpdateLinkBtn() : ''}
									</div>
									${this.#isAdmin && this.#isCloud ? this.#renderConfirmRegistrationCheckbox() : ''}
									${this.#isCloud ? this.#renderWhiteListField() : ''}
								</div>
							</div>
						</div>
					</div>
				</form>
				${!this.#isAdmin && !this.#fastRegistrationAvailable ? this.#renderWarningContainer() : ''}
			</div>
		`;

		Dom.prepend(
			this.#departmentControl.render(),
			this.#container.querySelector('.intranet-invitation__link-department-control'),
		);

		new SelfRegister({
			container: this.#container,
			isSelfRegisterEnabled: this.#linkRegisterEnabled,
			analytics: this.#analytics,
			transport: this.#transport,
			departmentControl: this.#departmentControl,
			page: this,
		});

		return this.#container;
	}

	#renderCopyBtnDescription(): HTMLElement
	{
		return Tag.render`
			<div class="invite-form__copy-button-description">
				${Loc.getMessage('INTRANET_INVITE_COPY_BUTTON_DESCRIPTION')}
			</div>
		`;
	}

	#renderUpdateLinkBtn(): HTMLElement
	{
		return Tag.render`
			<span data-role="selfRegenerateSecretButton">
				<a
				href="javascript:void(0)"
				class="invite-dialog-update-link"
				>
					${Loc.getMessage('BX24_INVITE_DIALOG_REGISTER_NEW_LINK')}
				</a>
			</span>
		`;
	}

	#renderFastRegistrationSwitcher(): HTMLElement
	{
		return Tag.render`
			<label class="invite-dialog-fast-reg-control-container js-invite-dialog-fast-reg-control-container ${this.#fastRegistrationAvailable ? '' : 'disallow-registration'}" for="allow_register">
				<div class="invite-dialog-fast-reg-control-switcher" data-role="self-switcher"></div>
				<span class="invite-dialog-fast-reg-control-label">
					<div class="invite-dialog-fast-reg-control-label-title">${Loc.getMessage('INTRANET_INVITE_ALLOW_INVITATION_LINK')}</div>
				</span>
			</label>
		`;
	}

	#renderConfirmRegistrationCheckbox(): HTMLElement
	{
		return Tag.render`
			<div style="padding-top: 12px;">
				<label class="ui-ctl ui-ctl-w100 ui-ctl-checkbox invite-form-check-box">
					<input
					type="checkbox"
					class="ui-ctl-element"
					name="allow_register_confirm"
					id="allow_register_confirm"
					data-role="allowRegisterConfirm"
					${this.#needConfirmRegistration ? 'checked' : ''}
					${this.#isAdmin ? '' : 'disabled'}
					/>
					<div class="ui-ctl-label-text">${Loc.getMessage('INTRANET_INVITE_DIALOG_FAST_REG_TYPE')}</div>
				</label>
			</div>
		`;
	}

	#renderWhiteListField(): HTMLElement
	{
		return Tag.render`
		<div id="intranet-dialog-tab-content-self-whitelist" data-role="selfWhiteList" ${this.#needConfirmRegistration ? '' : 'style="display: none"'}>
			<span class="invite-form-ctl-title">
				${Loc.getMessage('INTRANET_INVITE_DIALOG_FAST_REG_DOMAINS')}
			</span>
			<label class="ui-ctl ui-ctl-w75 ui-ctl-textbox">
				<input
				type="text"
				${this.#isAdmin ? '' : 'disabled'}
				class="ui-ctl-element"
				name="allow_register_whitelist"
				value="${this.#wishlist}"
				placeholder="${Loc.getMessage('BX24_INVITE_DIALOG_REGISTER_TYPE_DOMAINS_PLACEHOLDER')}"
				/>
			</label>
		</div>
		`;
	}

	#renderWarningContainer(): HTMLElement
	{
		return Tag.render`
			<div class="intranet-invitation__error-box">
				<div class="intranet-invitation__error-icon"></div>
				<div class="intranet-invitation__error-title">${Loc.getMessage('INTRANET_INVITE_ALERT_INVITATION_LINK_DISABLED')}</div>
				<div class="intranet-invitation__error-desc">${Loc.getMessage('INTRANET_INVITE_ALERT_INVITATION_LINK_DISABLED_DESCRIPTION')}</div>
			</div>
		`;
	}

	getAnalyticTab(): string
	{
		return Analytics.TAB_LINK;
	}

	onSubmit(event: BaseEvent)
	{
		const formNode = this.render().querySelector('form');
		const data = {
			allow_register: formNode.allow_register.value,
			allow_register_confirm: formNode?.allow_register_confirm?.checked ? 'Y' : 'N',
			allow_register_whitelist: formNode?.allow_register_whitelist?.value ?? '',
		};

		const context = event.getData()?.context;
		const isConfirm = event.getData()?.isConfirm;

		if (formNode.allow_register.value === 'N' && !isConfirm)
		{
			EventEmitter.emit('BX.Intranet.Invitation:confirmShutdown', {
				page: this,
				context,
			});

			return;
		}

		EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
			action: 'self',
			data,
		});
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_SAVE');
	}

	getButtonState(): ?string
	{
		return this.#btnState;
	}

	setButtonState(state: string): ?string
	{
		this.#btnState = state;
	}
}
