<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>

<script>
	(function(){
		var link = BX('<?=$field['ID']?>-link');
		var input = BX('<?=$field['ID']?>-input');

		var menuItems = [

			<?php foreach($field['ITEMS'] as $item):?>
			{
				id : '<?=$item['ID']?htmlspecialcharsbx($item['ID']):randString(5)?>',
				text : '<?=htmlspecialcharsbx($item['TITLE'])?>',
				onclick: function(){

					input.value = '<?=htmlspecialcharsbx($item['VALUE'])?>';
					link.innerHTML = '<?=htmlspecialcharsbx($item['TITLE'])?>';
				}
			},
            <?php endforeach;?>
		];

		BX.bind(link, 'click', function(e){
			e.preventDefault();

            BX.PopupMenu.show("menu-<?=$field['ID']?>", link, menuItems, {
                autoHide : true,
                closeByEsc : true
            });
        });
	})();
</script>

<?php
    $value = $field['VALUE'];

    $usedItem = array_filter($field['ITEMS'], function($item) use ($value){
        return ($value == $item['VALUE']);
    });

    if(!$usedItem)
    {
        $usedItem = array_shift($field['ITEMS']);
    }
    else
    {
        $usedItem = array_shift($usedItem);
    }
?>

<a href="javascript:;" id="<?=$field['ID']?>-link"><?=$usedItem['TITLE']?></a>
<input type="text" value="<?=$usedItem['VALUE']?>" name="<?=htmlspecialcharsbx($arParams['INPUT_PREFIX'])?>[<?=$field['CODE']?>]" id="<?=$field['ID']?>-input">