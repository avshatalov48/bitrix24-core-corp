<?php
/**
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load('crm.filter-dependent-fields');

?>
<script>
	BX.ready(function() {
		(new BX.Crm.FilterDependentFields()).initialize();
	});
</script>
<?php