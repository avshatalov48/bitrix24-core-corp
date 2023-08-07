<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

foreach($this->getComponent()->getErrors() as $error): ?>
	<div class="ui-alert ui-alert-danger">
		<span class="ui-alert-message"><?=$error->getMessage();?></span>
	</div>
<?php endforeach; ?>

