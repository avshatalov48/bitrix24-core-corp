import { Controller as FormController } from '../controller';
import { Factory } from '../../field/factory';
import { AbuseBlock } from './abuse';
import { AgreementBlock } from './agreement';
import { StateBlock } from './state';
import { PagerBlock } from './pager';
import { BasketBlock } from './basket';
import ReCaptcha from './recaptcha';
import YandexCaptcha from './yandexcaptcha';

const Form = {
	props: {
		form: { type: FormController },
	},

	components:
	{
		field: Factory.getComponent(),
		'abuse-block': AbuseBlock,
		'agreement-block': AgreementBlock,
		'state-block': StateBlock,
		'pager-block': PagerBlock,
		'basket-block': BasketBlock,
		'recaptcha-block': ReCaptcha,
		'yandex-captcha-block': YandexCaptcha,
	},

	computed:
	{
		hasErrors(): boolean
		{
			return this.form.validated && !this.form.valid();
		},
		abuseEnabled(): boolean
		{
			return Boolean(this.form?.abuse?.link);
		},
		abuseLink(): Object | string
		{
			return this.abuseEnabled ? this.getQueryParametersForAbuseLink() : '';
		},
		isDark(): boolean
		{
			return Boolean(this.form?.design?.isDark());
		},
	},

	methods:
	{
		getQueryParametersForAbuseLink()
		{
			const url = new URL(this.form.abuse.link);

			url.searchParams.set('b24_form_id', this.form.identification.id);
			url.searchParams.set('b24_address', this.form.identification.address);
			url.searchParams.set('b24_form_address', window.location.href);

			return url;
		},
		prevPage(): void
		{
			this.form.loading = true;
			setTimeout(() => {
				this.form.loading = false;
				this.form.pager.prev();
			}, 300);
		},
		nextPage(): void
		{
			if (this.form.pager.current().validate())
			{
				this.form.loading = true;
			}
			setTimeout(() => {
				this.form.loading = false;
				this.form.pager.next();
			}, 300);
		},
		getSignBy(): string
		{
			return this.form.messages.get('signBy').replace('24', '');
		},
		submit(e): void
		{
			if (!this.form.submit())
			{
				e.preventDefault();
			}
		},
		classes(): []
		{
			const list = [];
			if (this.form.view.type === 'inline' && this.form.design.shadow)
			{
				list.push('b24-form-shadow');
			}

			if (this.form.design.compact)
			{
				list.push('b24-form-compact');
			}

			const border = this.form.design.border;
			for (const pos in border)
			{
				if (!border.hasOwnProperty(pos) || !border[pos])
				{
					continue;
				}
				list.push(`b24-form-border-${pos}`);
			}

			if (this.form.loading || this.form.sent || this.form.error || this.form.disabled)
			{
				list.push('b24-from-state-on');
			}

			return list;
		},
	},

	template: `
		<div class="b24-form-wrapper"
			:class="classes()"
		>
			<div v-if="form.title || form.desc" class="b24-form-header b24-form-padding-side">
				<div v-if="form.title" class="b24-form-header-title">{{ form.title }}</div>
				<div class="b24-form-header-description"
					v-if="form.desc"
					v-html="form.desc"
				></div>
			</div>
			<div v-else class="b24-form-header-padding"></div>

			<div class="b24-form-content b24-form-padding-side">
				<form
					method="post"
					novalidate
					@submit="submit"
					v-if="form.pager"
				>
					<component
						:is="'pager-block'"
						:pager="form.pager"
						v-if="form.pager.iterable()"
					></component>

					<div v-if="!form.disabled">
						<component
							:is="'field'"
							v-for="field in form.pager.current().fields"
							:key="field.id"
							:field="field"
						></component>
					</div>

					<component
						:is="'agreement-block'"
						:formId="form.getId()"
						:fields="form.agreements"
						:view="form.view"
						:messages="form.messages"
						v-if="form.pager.ended()"
					></component>

					<component
						:is="'basket-block'"
						:basket="form.basket"
						:messages="form.messages"
					></component>

					<div class="b24-form-btn-container">
						<div class="b24-form-btn-block"
							v-if="!form.pager.beginning()"
							@click.prevent="prevPage()"
						>
							<button type="button" class="b24-form-btn b24-form-btn-white b24-form-btn-border">
								{{ form.messages.get('navBack') }}
							</button>
						</div>

						<div class="b24-form-btn-block"
							v-if="!form.pager.ended()"
							@click.prevent="nextPage()"
						>
							<button type="button" class="b24-form-btn">
								{{ form.messages.get('navNext') }}
							</button>
						</div>
						<div class="b24-form-btn-block"
							v-if="form.pager.ended()"
						>
							<button type="submit" class="b24-form-btn">
								{{ form.buttonCaption || form.messages.get('defButton') }}
							</button>
						</div>
					</div>

					<span style="color: red;" v-show="false && hasErrors">
						Debug: fill fields
					</span>
				</form>
			</div>

			<state-block :form="form" />

			<recaptcha-block :form="form" />

			<yandex-captcha-block :form="form" />

			<div class="b24-form-sign">
				<select v-show="false" v-model="form.messages.language">
					<option
						v-for="language in form.languages"
						:value="language"
					>
						{{ language }}
					</option>
				</select>

				<component
					v-if="abuseEnabled"
					:is="'abuse-block'"
					:messages="form.messages"
					:abuseLink="abuseLink"
				></component>

				<div class="b24-form-sign-info" v-if="form.useSign">
					<span class="b24-form-sign-text">{{ form.messages.get('sign') }}</span>
					<span class="b24-form-sign-bx">{{ getSignBy() }}</span>
					<span class="b24-form-sign-24">24</span>
				</div>
			</div>
		</div>
	`,
};

export {
	Form,
};
