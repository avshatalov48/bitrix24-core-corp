<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<script id="sharing-item" type="text/html">
	<div class="disk-detail-sidebar-user-access" id="sharing-item-{{id}}" data-entity="sharing-item" data-id="{{id}}">
		<div class="disk-detail-sidebar-user-access-avatar" {{#entity.avatar}} style="background-image: url('{{#encodeURI}}{{entity.avatar}}{{/encodeURI}}')"{{/entity.avatar}}></div>
		<div class="disk-detail-sidebar-user-access-name">
			<div class="disk-detail-sidebar-user-access-name-block">
				<a class="disk-detail-sidebar-user-access-link" href="{{entity.link}}">{{entity.name}}</a>
				{{#sharing.canDelete}}
				<div class="disk-detail-sidebar-user-access-remove" data-entity="sharing-item-delete"></div>
				{{/sharing.canDelete}}
			</div>
			<div class="disk-detail-sidebar-user-access-type" {{#sharing.canChange}} data-entity="sharing-item-change-right" {{/sharing.canChange}} >{{sharing.name}}</div>
		</div>
	</div>
</script>