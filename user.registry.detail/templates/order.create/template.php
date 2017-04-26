<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Application;

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$arGet = $arParams['GET_PARAMS'];
$arPost = $request->getPostList()->toArray();
$arServer = $context->getServer()->toArray();
$arParamsNotEdit = array(
    'ID',
    'IBLOCK_ID',
    'PROPERTY_PROP_WMS_STATUS',
    'PROPERTY_PROP_SSCC'
);
$arParamsNoShow = array(
    'IBLOCK_ID',
    'PROPERTY_PROP_WMS_STATUS'
);
$formPath = $arResult['FORM_PATH'];
$clsShow = '';
$dShow = '';
$textFilter = Loc::getMessage('USER_ORDER_T_SHOW_FILTER');
$dataTFilter = Loc::getMessage('USER_ORDER_T_HIDE_FILTER');

$arSelectedUnits = array();
if(!empty($arParams['SELECTED_UNITS'])) {
    $arSelectedUnits = $arParams['SELECTED_UNITS'];
}

$prefix = $arParams['PREFIX_FOR_INPUT'];
if($arGet[$prefix . 'FILTER'] == 'Y'
    || !empty($arParams['SELECTED_UNITS'])) {
    $clsShow = ' js-open';
    $dShow = ' style="display: block;"';
    $textFilter = Loc::getMessage('USER_ORDER_T_HIDE_FILTER');
    $dataTFilter = Loc::getMessage('USER_ORDER_T_SHOW_FILTER');
}
$orderClass = new Orders();
?>

<div class="order">
    <div class="order__col">
        <div class="b-container" id="order-form">
            <div class="b-container-block<?= $clsShow ?>">
                <div class="bcb-head bcb-head-no-width">
                    <p></p>
                    <p>
                        <a class="js-toggle-uri" href="#">
                            <?= Loc::getMessage('USER_ORDER_T_SELECT_REGISTRY') ?>
                            <i class="fa fa-arrow-circle-down"></i>
                        </a>
                    </p>
                </div>
                <div class="bcb-collapse"<?= $dShow ?>>
                    <div class="bcb-body">
                        <div class="b-container-block<?= $clsShow ?>">
                            <div class="bcb-head bcb-head-no-width">
                                <p></p>
                                <p>
                                    <a class="js-toggle-uri" href="#" data-text-toggle="<?= $dataTFilter ?>">
                                        <span><?= $textFilter ?></span>
                                        <i class="fa fa-filter"></i>
                                    </a>
                                </p>
                            </div>
                            <div class="bcb-collapse"<?= $dShow ?>>
                                <div class="bcb-body">
                                    <form action="<?= $formPath ?>" class="form" method="get">
                                        <input type="hidden" name="<?= $prefix ?>FILTER" value="Y">
                                        <?if(!empty($arParams['SELECTED_UNITS'])){?>
                                            <?foreach($arGet as $cGet => $vGet){?>
                                                <input type="hidden" name="<?= $cGet ?>" value="<?= $vGet ?>" />
                                            <?}?>
                                        <?} else {?>
                                            <input type="hidden" name="<?= $prefix ?>CONTRACT" value="<?= $arParams['F_UNIT_CONTRACT'] ?>">
                                            <input type="hidden" name="<?= $prefix ?>TYPE_ORDER" value="<?= $arParams['F_UNIT_TYPE_ORDER'] ?>">
                                        <?}?>

                                        <?$countInLine = $arParams['COUNT_IN_ROW_PARAMS'];
                                        $countRowSort = count($arResult['ROW_SORT']);
                                        $iSort = 0;
                                        foreach($arResult['ROW_SORT'] as $cSort => $vSort){
                                            $flag = true;
                                            if(!empty($arParams['VARIABLES']['ID']) || stristr($cSort, 'PROP_FILE')) {
                                                if(in_array($cSort, $arParamsNoShow)
                                                    || $cSort == 'IBLOCK_NAME' && empty($arResult['LIST_REGISTRY'])
                                                    || $cSort == 'IBLOCK_NAME' && count($arResult['LIST_REGISTRY']) == 1
                                                    || $cSort == 'PROPERTY_PROP_FILE') {
                                                    $flag = false;
                                                    $countRowSort = $countRowSort - 1;
                                                }
                                            }
                                            if($flag) {
                                                if($iSort == 0) {?>
                                                    <div class="form__row">
                                                <?}
                                                if(stristr($cSort, 'PROPERTY_')) {
                                                    $arField = $arResult['PROPERTY_LIST'][str_replace('PROPERTY_', '', $cSort)];
                                                    $rowName = $arField['NAME'];
                                                    $rowPlaceholder = Loc::getMessage('UNIT_F_PLACEHOLDER_' . $arField['PROPERTY_TYPE']);
                                                } else {
                                                    $rowName = Loc::getMessage('UNIT_F_TITLE_' . $vSort['CODE']);
                                                    $rowPlaceholder = Loc::getMessage('UNIT_F_PLACEHOLDER_' . $vSort['CODE']);
                                                }?>
                                                <div class="form__row__col _size6">
                                                    <div class="form__row__name"><?= $rowName ?></div>
                                                    <?if($cSort == 'IBLOCK_NAME') {?>
                                                        <select name="<?= $prefix ?>FILTER_IBLOCK_ID[]" multiple size="2" id="unit-filter-iblock">
                                                            <option value="">-</option>
                                                            <?foreach($arResult['LIST_REGISTRY'] as $idRegistry => $nRegistry){
                                                                $selected = '';
                                                                if(in_array($idRegistry, $arGet[$prefix . 'FILTER_IBLOCK_ID'])){
                                                                    $selected = ' selected';
                                                                }?>
                                                                <option value="<?= $idRegistry ?>"<?= $selected ?>><?= $nRegistry ?></option>
                                                            <?}?>
                                                        </select>
                                                    <?}
                                                    else {?>
                                                        <input type="text"
                                                               value="<?= $arGet[$prefix . 'FILTER_' . $vSort['CODE']] ?>"
                                                               class="form__field"
                                                               name="<?= $prefix ?>FILTER_<?= $vSort['CODE'] ?>"
                                                               placeholder="<?= $rowPlaceholder ?>">
                                                    <?}?>
                                                </div>
                                                <?$iSort++;
                                                if(($iSort % $countInLine) == 0 && $iSort != $countRowSort) {?>
                                                    </div>
                                                    <div class="form__row">
                                                <?} elseif($iSort == $countRowSort) {?>
                                                    </div>
                                                <?}
                                            }
                                        }?>
                                        <div class="form__row btn-block">
                                            <button type="submit" class="btn"><?= Loc::getMessage('USER_REG_T_BUTTON_APPLY') ?></button>
                                            <a href="<?= $formPath ?>create/" class="btn _white">
                                                <?= Loc::getMessage('USER_REG_T_BUTTON_RESET') ?>
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="control__result">
                            <?= Loc::getMessage('USER_REG_T_FOUND') ?><?= $arResult['COUNT_UNITS']?>
                            <?if($orderClass->checkExistUnitsByOrder($arPost['CONTRACT'], $arPost['TYPE_ID'])
                                || !empty($arParams['SELECTED_UNITS'])) {?>
                                <?= Loc::getMessage('SPLIT') ?><a href="<?= $arResult['URL_ONLY_CHECK'] ?>"><?= $arResult['MESS_ONLY_CHECK'] ?></a>
                            <?}?>
                        </div>
                        <table class="table js-table" id="order-table">
                            <thead>
                            <tr>
                                <th class="js-checkbox-td">
                                    <label class="checkbox">
                                        <input type="checkbox" name="L_UNITS_CHECK_ALL" class="js-table-checkbox-all">
                                        <span></span>
                                    </label>
                                </th>
                                <th></th>
                                <?foreach($arResult['ROW_SORT'] as $cSort => $vSort) {
                                    if(!in_array($cSort, $arParamsNoShow)) {?>
                                        <th<?= setClassFile($cSort) ?>>
                                            <a class="filtr" href="<?= $vSort['URL'] ?>">
                                                <?if(stristr($vSort['CODE'], 'PROPERTY_')) {
                                                    echo $arResult['PROPERTY_LIST'][str_replace('PROPERTY_', '', $vSort['CODE'])]['NAME'];
                                                } else {
                                                    echo Loc::getMessage('USER_REG_T_TABLE_' . $vSort['CODE']);
                                                }?>
                                            </a>
                                        </th>
                                    <?}
                                }?>
                            </tr>
                            </thead>
                            <?foreach($arResult['LIST_REG_ITEMS'] as $iItem => $arItem){
                                $existOrder = $orderClass->checkUnit($arPost['TYPE_ID'], $arPost['CONTRACT'], $arItem['ID']);
                                $attrCheck = '';
                                $classRow = '';
                                if(!$arResult['WMS_STATUS_LIST'][$arItem['PROPERTY_PROP_WMS_STATUS']]['SHOW']
                                    || $existOrder
                                    || !empty($arSelectedUnits) && in_array($arItem['ID'], $arSelectedUnits)) {
                                    $classRow .= ' class="';
                                    if(!$arResult['WMS_STATUS_LIST'][$arItem['PROPERTY_PROP_WMS_STATUS']]['SHOW']) {
                                        $classRow .= 'no-active-row';
                                    }
                                    if($existOrder || in_array($arItem['ID'], $arSelectedUnits)) {
                                        $classRow .= ' _active';
                                        $attrCheck = ' checked';
                                    }
                                    $classRow .= '"';
                                }?>
                                <tr<?= $classRow ?>>
                                    <td class="js-checkbox-td">
                                        <label class="checkbox">
                                            <input type="checkbox" <?= $attrCheck ?>
                                                   name="<?= $arParams['PREFIX_FOR_INPUT'] ?>UNITS[]"
                                                   value="UNIT_ID-<?= $arItem['ID'] ?>-IBLOCK_ID-<?= $arItem['IBLOCK_ID'] ?>"
                                                   class="js-table-checkbox">
                                           <span></span>
                                        </label>
                                    </td>
                                    <td>
                                        <?$arFiles = $arItem['DATA_FILES'];
                                        $clsIcon = ' hidden';
                                        if($arFiles['FILE_EXIST']) {
                                            if(count($arFiles['FILE_URL']) == 1) {
                                                $clsIcon = setClassForIcon($arFiles['FILE_EXT']);
                                            } else {
                                                $clsIcon = 'fa-files-o';
                                            }?>
                                            <div class="contain-upload" data-ajax-file="<?= $this->GetFolder() ?>/ajax.files.php">
                                                <a href="#"
                                                   title="<?= count($arItem['DATA_FILES']['FILE_URL']) . Loc::getMessage('USER_REG_T_TITLE_COUNT_FILES') ?>"
                                                   class="js-file-list"
                                                   data-files="<?= urlencode($arParams['PATH_TO_FILES'] . $arItem['PROPERTY_PROP_SSCC'] . '/') ?>">
                                                    <i class="fa <?= $clsIcon ?>"></i>
                                                </a>
                                            </div>
                                        <?}?>
                                    </td>
                                    <?foreach($arResult['ROW_SORT'] as $cSort => $vSort) {
                                        if(!in_array($cSort, $arParamsNoShow)){?>
                                            <td<?= setClassFile($cSort) ?>>
                                                <?if(!in_array($vSort['CODE'], $arParamsNotEdit)) {
                                                    $flag = true;
                                                    if(stristr($vSort['CODE'], 'PROPERTY_')
                                                        && !array_key_exists(str_replace('PROPERTY_', '', $vSort['CODE']), $arResult['PROPERTY_LIST_IBLOCK'][$arItem['IBLOCK_ID']])) {
                                                        $flag = false;
                                                    }
                                                    if($flag) {?>
                                                        <span class="js-to-input"
                                                              data-type="text"
                                                              data-class="form__field js-input-disabled"
                                                              data-name="UNIT_<?= $arItem['ID'] ?>_<?= $vSort['CODE'] ?>"
                                                              data-placeholder="<?= Loc::getMessage('PLACEHOLDER_UNIT_' . $vSort['CODE']) ?>">
                                                            <?= $arItem[$vSort['CODE']] ?>
                                                        </span>
                                                    <?}
                                                } else {
                                                    if($vSort['CODE'] == 'PROPERTY_PROP_WMS_STATUS') {
                                                        echo $arResult['WMS_STATUS_LIST'][$arItem[$vSort['CODE']]]['NAME'];
                                                    } else {
                                                        echo $arItem[$vSort['CODE']];
                                                    }
                                                }?>
                                            </td>
                                        <?}
                                    }?>
                                </tr>
                            <?}
                            $countOnPage = $arParams['COUNT_ITEM_ON_PAGE'];
                            if(!empty($arGet['COUNT_ON_PAGE']) && $arGet['COUNT_ON_PAGE'] != $countOnPage) {
                                $countOnPage = $arGet['COUNT_ON_PAGE'];
                            }?>
                        </table>
                        <div class="navigation">
                            <?= $arResult['NAV_PRINT'] ?>
                            <div class="pager">
                                <div class="pager__current">
                                    <span><?= Loc::getMessage('USER_REG_T_COUNT_ITEMS') ?></span>
                                    <form action="<?= $arParams['SEF_FOLDER'] ?>" method="GET" class="nav-form">
                                        <?= getHTMLInputExcludeParam($arGet, 'COUNT_ON_PAGE') ?>
                                        <input type="text" name="COUNT_ON_PAGE" value="<?= $countOnPage ?>">
                                    </form>
                                </div>
                            </div>
                        </div>                  
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    checkSendForm('form');
</script>