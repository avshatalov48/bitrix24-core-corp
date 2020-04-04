const BasketBlock = {
	props: ['basket', 'messages'],
	template: `
		<div v-if="basket.has()" class="b24-form-basket">
			<table>
				<tbody>
					<tr v-if="basket.discount()" class="b24-form-basket-sum">
						<td class="b24-form-basket-label">
							{{ messages.get('basketSum') }}:
						</td>
						<td class="b24-form-basket-value" v-html="basket.printSum()"></td>
					</tr>
					<tr v-if="basket.discount()" class="b24-form-basket-discount">
						<td class="b24-form-basket-label">
							{{ messages.get('basketDiscount') }}:
						</td>
						<td class="b24-form-basket-value" v-html="basket.printDiscount()"></td>
					</tr>
					<tr class="b24-form-basket-pay">
						<td class="b24-form-basket-label">
							{{ messages.get('basketTotal') }}:
						</td>
						<td class="b24-form-basket-value" v-html="basket.printTotal()"></td>
					</tr>
				</tbody>
			</table>
		</div>
	`,
	computed: {

	},
	methods: {

	}
};

export {
	BasketBlock,
}