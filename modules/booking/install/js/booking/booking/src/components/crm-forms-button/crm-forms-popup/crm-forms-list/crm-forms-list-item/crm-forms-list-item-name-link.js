import { h } from 'ui.vue3';

function CrmFormsListItemNameLink(props: CrmFormsListItemNameLinkProps, { slots }): Object
{
	if (props.canEdit && props.editUrl)
	{
		return h(
			'a',
			{
				href: props.editUrl,
				target: '_blank',
			},
			slots.default(),
		);
	}

	return slots.default();
}

type CrmFormsListItemNameLinkProps = {
	canEdit: boolean;
	editUrl: string;
}

const props: Array<$Keys<CrmFormsListItemNameLinkProps>> = ['canEdit', 'editUrl'];

CrmFormsListItemNameLink.props = props;

export { CrmFormsListItemNameLink };
