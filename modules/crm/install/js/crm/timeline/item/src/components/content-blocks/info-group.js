
export const InfoGroup = {
	props: {
		blocks: {
			type: Object,
			required: false,
			default: () => ({}),
		}
	},

	template: `
		<table class="crm-timeline__info-group">
			<tbody>
				<tr
					v-for="({title, block}, id) in blocks"
					:key="id"
					class="crm-timeline__info-group_block"
				>
					<td
						:title="title"
						class="crm-timeline__info-group_block-title"
					>
						{{title}}
					</td>
					<td class="crm-timeline__info-group_block-content">
						<component
							:is="block.rendererName"
							v-bind="block.properties"
						/>
					</td>
				</tr>
			</tbody>
		</table>
	`
}