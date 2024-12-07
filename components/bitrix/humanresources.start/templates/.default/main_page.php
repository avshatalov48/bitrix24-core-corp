<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'humanresources.tree-structure'
]);

?>

<div id="humanresources-main-page-container"></div>
<script>
	BX.HumanResources.TreeStructure.renderTo(BX('humanresources-main-page-container'));
</script>
