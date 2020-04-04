import {Controller as FormController} from "../controller";
import {Factory} from "../../field/factory";
import {AgreementBlock} from "./agreement";
import {StateBlock} from "./state";
import {PagerBlock} from "./pager";
import {BasketBlock} from "./basket";

const Form = {
	props: {
		form: {type: FormController},
	},
	components: {
		'field': Factory.getComponent(),
		'agreement-block': AgreementBlock,
		'state-block': StateBlock,
		'pager-block': PagerBlock,
		'basket-block': BasketBlock,
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
					<component v-bind:is="'pager-block'"
						v-bind:key="form.id"
						v-bind:pager="form.pager"
						v-if="form.pager.iterable()"
					></component>
								
					<div>		
						<component v-bind:is="'field'"
							v-for="field in form.pager.current().fields"
							v-bind:key="field.id"
							v-bind:field="field"
						></component>
					</div>	
					
					<component v-bind:is="'agreement-block'"
						v-bind:key="form.id"
						v-bind:fields="form.agreements"
						v-bind:view="form.view"
						v-bind:messages="form.messages"
						v-if="form.pager.ended()"
					></component>
					
					<component v-bind:is="'basket-block'"
						v-bind:key="form.id"
						v-bind:basket="form.basket"
						v-bind:messages="form.messages"
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
								{{ form.buttonCaption }}
							</button>
						</div>
					</div>
					
					<span style="color: red;" v-show="false && hasErrors">
						Debug: fill fields
					</span>
				</form>
			</div>
			
			<state-block v-bind:key="form.id" v-bind:form="form"></state-block>
			<div class="b24-form-sign" v-if="form.useSign">
				<select v-show="false" v-model="form.messages.language">
					<option v-for="language in form.languages" 
						v-bind:value="language"																						
					>
						{{ language }}
					</option>				
				</select>
			
				<span class="b24-form-sign-text">{{ form.messages.get('sign') }}</span>
				<span class="b24-form-sign-bx">{{ getSignBy() }}</span>
				<span class="b24-form-sign-24">24</span>			
			</div>			
		</div>
	`,
	computed: {
		hasErrors()
		{
			return this.form.validated && !this.form.valid();
		},
	},
	methods: {
		prevPage()
		{
			this.form.loading = true;
			setTimeout(() => {
				this.form.loading = false;
				this.form.pager.prev();
			}, 300);
		},
		nextPage()
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
		getSignBy()
		{
			return this.form.messages.get('signBy').replace('24', '');
		},
		submit(e)
		{
			if (!this.form.submit())
			{
				e.preventDefault();
			}
		},
		classes()
		{
			let list = [];
			if (this.form.view.type === 'inline' && this.form.design.shadow)
			{
				list.push('b24-form-shadow');
			}

			let border = this.form.design.border;
			for (let pos in border)
			{
				if (!border.hasOwnProperty(pos) || !border[pos])
				{
					continue;
				}
				list.push('b24-form-border-' + pos);
			}
			if (this.form.loading || this.form.sent || this.form.error || this.form.disabled)
			{
				list.push('b24-from-state-on');
			}

			return list;
		},
	}
};

export {
	Form,
}