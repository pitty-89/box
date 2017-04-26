<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Application;

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$arGet = $request->getQueryList()->toArray();
$arPost = $request->getPostList()->toArray();
$arServer = $context->getServer()->toArray();
$arParamsNotEdit = array(
    'ID',
    'IBLOCK_ID',
    'PROPERTY_PROP_WMS_STATUS',
    'PROPERTY_PROP_SSCC'
);
$htmlHideUrl = '
    <a href="#" 
        class="portlet-content-toggle url_close"
        data-confirm-title="' . Loc::getMessage('SETTINGS_FILTER_SAVE_TITLE') . '" 
        data-confirm-question="' . Loc::getMessage('SETTINGS_FILTER_SAVE_QUESTION') . '" 
        data-confirm-answer-y="' . Loc::getMessage('SETTINGS_FILTER_SAVE_Y') . '" 
        data-confirm-answer-n="' . Loc::getMessage('SETTINGS_FILTER_SAVE_N') . '"> 
    </a>';

$arCountItems = array('2', '5', '10', '15', '20', '25', '30');
if(!empty($arParams['COUNT_ITEMS_VARS'])) {
    $arCountItems = $arParams['COUNT_ITEMS_VARS'];
}
$cOrder = new Orders();
$onlyCheck = 'ONLY_CHECK=';
if(!empty($arGet['ONLY_CHECK']) && $arGet['ONLY_CHECK'] == 'Y') {
    $onlyCheck = '';
} else {
    $onlyCheck .= 'Y';
}
$tmpArGet = $arGet;
unset($tmpArGet['ONLY_CHECK']);
if(!empty($tmpArGet)) {
    if($onlyCheck) {
        $onlyCheck = getStringQueryExcludeParam($tmpArGet, 'ONLY_CHECK') . '&' . $onlyCheck;
    } else {
        $onlyCheck = getStringQueryExcludeParam($tmpArGet, 'ONLY_CHECK');
    }
} else {
    if($onlyCheck) {
        $onlyCheck = '?' . $onlyCheck;
    }
}
$onlyCheck = $arResult['FORM_PATH_DEFAULT'] . $onlyCheck;
global $APPLICATION;
$showOptions = array();
foreach($arParams['OPTIONS_TO_UNIT'] as $option => $boolOption) {
    if($boolOption) {
        $showOptions[] = $option;
    }
}?>

<div class="content__item _top">
    <div class="page__title">
        <h1>
            <?if($arParams['TITLE_PAGE']) {?>
                <?= $arParams['TITLE_PAGE'] ?>
            <?} else {?>
                <?= Loc::getMessage('USER_REG_T_TITLE') ?>
            <?}?>
        </h1>
    </div>
    <div class="scrollbar-outer">
        <form action="<?= $arResult['FORM_PATH_DEFAULT'] ?>" class="form form__fl-left" method="get" id="form-filter">
            <input type="hidden" name="F_UNIT_FILTER" value="Y">
            <div class="form__row form__row__sortable" id="sortable">
                <a href="#" class="lfs-delete hidden">
                    <?= Loc::getMessage('SETTINGS_FILTER_DELETE') ?>
                </a>
                <div class="column form__row__col _size4 ui-sortable">
                    <?$countInLine = $arParams['COUNT_IN_ROW_PARAMS'];
                    $countRowSort = count($arResult['ROW_SORT']);
                    $iSort = 0;
                    foreach($arResult['ROW_SORT'] as $cSort => $vSort){
                        $flag = true;
                        if($cSort == 'ID' || $cSort == 'PROPERTY_PROP_FILE' || $cSort == 'IBLOCK_NAME') {
                            $flag = false;
                            $countRowSort = $countRowSort - 1;
                        }
                        if($flag) {
                            if(stristr($cSort, 'PROPERTY_')) {
                                $arField = $arResult['PROPERTY_LIST'][str_replace('PROPERTY_', '', $cSort)];
                                $rowName = $arField['NAME'];
                                $rowPlaceholder = Loc::getMessage('UNIT_F_PLACEHOLDER_' . $arField['PROPERTY_TYPE']);
                            } else {
                                $rowName = Loc::getMessage('UNIT_F_TITLE_' . $vSort['CODE']);
                                $rowPlaceholder = Loc::getMessage('UNIT_F_PLACEHOLDER_' . $vSort['CODE']);
                            }?>
                            <div class="portlet" data-portlet="<?= $iSort ?>">
                                <?if($cSort == 'IBLOCK_ID') {?>
                                    <div class="portlet-header">
                                        <?= Loc::getMessage('UNIT_F_TITLE_IBLOCK_NAME') ?><?= $htmlHideUrl ?>
                                    </div>
                                    <div class="portlet-content">
                                        <select name="F_UNIT_FILTER_IBLOCK_ID[]"
                                                <?= getSizeMultiSelect(count($arResult['LIST_REGISTRY'])) ?>
                                                id="unit-filter-iblock">
                                            <option value="">-</option>
                                            <?foreach($arResult['LIST_REGISTRY'] as $idRegistry => $nRegistry){?>
                                                <?$select = '';
                                                if($arGet['F_UNIT_FILTER_IBLOCK_ID'] && in_array($idRegistry, $arGet['F_UNIT_FILTER_IBLOCK_ID'])) {
                                                    $select = ' selected';
                                                }?>
                                                <option value="<?= $idRegistry ?>"<?= $select ?>>
                                                    <?= $nRegistry ?>
                                                </option>
                                            <?}?>
                                        </select>
                                    </div>
                                <?} elseif($cSort == 'PROPERTY_PROP_WMS_STATUS') {?>
                                    <div class="portlet-header">
                                        <?= $rowName ?><?= $htmlHideUrl ?>
                                    </div>
                                    <div class="portlet-content">
                                        <select name="F_UNIT_FILTER_PROPERTY_PROP_WMS_STATUS[]" id="WMS_STATUS" <?= getSizeMultiSelect(count($arResult['WMS_STATUS_LIST'])) ?>>
                                            <option value="">-</option>
                                            <?foreach($arResult['WMS_STATUS_LIST'] as $xmlID => $arStatus) {?>
                                                <?$select = '';
                                                if($arGet['F_UNIT_FILTER_PROPERTY_PROP_WMS_STATUS'] && in_array($xmlID, $arGet['F_UNIT_FILTER_PROPERTY_PROP_WMS_STATUS'])) {
                                                    $select = ' selected';
                                                }?>
                                                <option value="<?= $xmlID ?>"<?= $select ?>>
                                                    <?= $arStatus['NAME'] ?>
                                                </option>
                                            <?}?>
                                        </select>
                                    </div>
                                <?} else {?>
                                    <div class="portlet-header">
                                        <?= $rowName ?><?= $htmlHideUrl ?>
                                    </div>
                                    <div class="portlet-content">
                                        <input type="text"
                                               value="<?= $arGet['F_UNIT_FILTER_' . $vSort['CODE']] ?>"
                                               class="form__field"
                                               name="F_UNIT_FILTER_<?= $vSort['CODE'] ?>"
                                               placeholder="<?= $rowPlaceholder ?>">
                                        <a href="#" class="js-reset-input"><i class="fa fa-close"></i></a>
                                    </div>
                                <?}?>
                            </div>
                            <?
                            $iSort++;
                        }
                    }?>
                </div>
            </div>
            <div class="form__row">
                <button type="submit" class="btn"><?= Loc::getMessage('USER_REG_T_BUTTON_APPLY') ?></button>
                <a href="<?= $arResult['FORM_PATH_DEFAULT'] ?>" class="btn _white">
                    <?= Loc::getMessage('USER_REG_T_BUTTON_RESET') ?>
                </a>
            </div>
        </form>
    </div>
</div>
<div class="content__item _bottom content__item__table">
    <div class="scrollbar-outer">
        <div class="content__item__frame">
            <?if(count($arResult['LIST_REG_ITEMS']) > 0) {?>
                <form action="<?= $arResult['UNITS_FORM_PATH'] ?>"
                      method="post"
                      id="edit-unit"
                      enctype="multipart/form-data"
                      class="form js-edit-unit">
                    <input type="hidden" name="F_UNIT_EDIT" value="Y">
                    <div class="control control-block" id="js-controls">
                        <div class="control__result">
                            <?= Loc::getMessage('USER_REG_T_FOUND') ?><?= $arResult['COUNT_UNITS'] ?>
                        </div>
                        <?if(count($showOptions) > 0){?>
                            <ul class="control__tools">
                                <?foreach($arParams['OPTIONS_TO_UNIT'] as $cOption => $bOption) {?>
                                    <?if($bOption){?>
                                        <li class="js-option-disabled">
                                            <a href="#"
                                               title="<?= Loc::getMessage('USER_REG_T_OPTION_' . $cOption) ?>"
                                               class="js-option js-o-<?= strtolower($cOption) ?>">
                                                <i class="fa<?= setClassIcon($cOption) ?>"></i>
                                            </a>
                                        </li>
                                    <?}?>
                                <?}?>
                            </ul>
                            <div class="control__actions">
                                <div class="form__row__name"></div>
                                <div class="form__row__col__item">
                                    <button class="btn btn-disabled js-btn-submit" type="submit">
                                        <?= Loc::getMessage('USER_REG_T_BUTTON_SAVE') ?>
                                    </button>
                                </div>
                            </div>
                        <?}?>
                        <div class="control__actions control__actions__container"
                             id="control-action-toggle"
                             data-uri-check="<?= $onlyCheck ?>"
                             data-check="<?= $arGet['ONLY_CHECK'] ?>"></div>
                    </div>
                    <?$arItemActive = array();?>
                    <div class="div-table table js-table"
                         id="registry-table"
                         data-request="<?= $arResult['PROP_REQUEST'] ?>"
                         data-ajax-order="<?= $this->GetFolder() ?>/ajax/order.php">
                        <a href="#" class="lfs-delete hidden">
                            <?= Loc::getMessage('SETTINGS_FILTER_DELETE') ?>
                        </a>
                        <ul class="list-row" id="list-row-sortable">
                            <li class="lr-checkbox ui-state-disabled" data-column="lr-checkbox">
                                <div class="js-checkbox-td js-td">
                                    <label class="checkbox">
                                        <input type="checkbox" name="L_UNITS_CHECK_ALL" class="js-table-checkbox-all">
                                        <span></span>
                                    </label>
                                </div>
                                <?foreach($arResult['LIST_REG_ITEMS'] as $iItem => $arItem) {
                                    $clsActiveRow = '';
                                    $checked = '';
                                    $arItemActive[$iItem] = false;
                                    if($cOrder->checkExistUnit($arItem['CONTRACT']['ID'], $arItem['ID'])) {
                                        $clsActiveRow = ' _active';
                                        $checked = ' checked';
                                        $arItemActive[$iItem] = true;
                                    }?>
                                    <div class="js-td<?= $clsActiveRow ?><?= $arItem['ERROR_CLASS'] ?>" data-item="<?= $iItem ?>" title="<?= $arItem['ERROR'] ?>">
                                        <div class="js-checkbox-td">
                                            <label class="checkbox">
                                                <input type="checkbox"
                                                       name="L_CHECK_UNIT[]"
                                                       value="UNIT_ID-<?= $arItem['ID'] ?>-IBLOCK_ID-<?= $arItem['IBLOCK_ID'] ?>"
                                                       class="js-table-checkbox"
                                                    <?= $checked ?>
                                                       data-contract="<?= $arItem['CONTRACT']['ID'] ?>"
                                                       data-status="<?= $arItem['PROPERTY_PROP_WMS_STATUS'] ?>">
                                                <span></span>
                                            </label>
                                        </div>
                                    </div>
                                <?}?>
                            </li>
                            <?if($arParams['USE_VIEWER'] == 'Y') {?>
                                <li class="lr-icon ui-state-disabled" data-column="lr-icon">
                                    <div class="js-td"></div>
                                    <?foreach($arResult['LIST_REG_ITEMS'] as $iItem => $arItem) {
                                        $clsActiveRow = '';
                                        if($arItemActive[$iItem]) {
                                            $clsActiveRow = ' _active';
                                        }?>
                                        <div class="js-td<?= $clsActiveRow ?><?= $arItem['ERROR_CLASS'] ?>" data-item="<?= $iItem ?>" title="<?= $arItem['ERROR'] ?>">
                                            <?$arFiles = $arItem['DATA_FILES'];
                                            $clsIcon = ' hidden';
                                            if($arFiles['FILE_EXIST']) {
                                                if(count($arFiles['FILE_URL']) == 1) {
                                                    $clsIcon = setClassForIcon($arFiles['FILE_EXT']);
                                                } else {
                                                    $clsIcon = 'fa-files-o';
                                                }?>
                                                <div class="contain-upload" data-ajax-file="<?= $this->GetFolder() ?>/ajax/files.php">
                                                    <a href="#"
                                                       title="<?= count($arItem['DATA_FILES']['FILE_URL']) ?><?= Loc::getMessage('USER_REG_T_TITLE_COUNT_FILES') ?>"
                                                       class="js-file-list"
                                                       data-files="<?= urlencode($arParams['PATH_TO_FILES'] . $arItem['PROPERTY_PROP_SSCC'] . '/') ?>">
                                                        <i class="fa <?= $clsIcon ?>"></i>
                                                    </a>
                                                </div>
                                            <?}?>
                                        </div>
                                    <?}?>
                                </li>
                            <?}?>
                            <?$iCode = 0;
                            foreach($arResult['ROW_SORT'] as $cSort => $vSort) {?>
                                <?if($cSort != 'IBLOCK_ID' && $cSort != 'ID') {?>
                                    <li class="portlet lr-<?= strtolower($cSort) ?>" data-column="lr-<?= strtolower($cSort) ?>">
                                        <?$column++;?>
                                        <div class="js-td<?= setClassFile($cSort) ?>">
                                            <?if($cSort == 'PROPERTY_PROP_FILE') {?><div><?}?>
                                                <a class="filtr" href="<?= $vSort['URL'] ?>">
                                                    <?if(stristr($vSort['CODE'], 'PROPERTY_')) {?>
                                                        <?= $arResult['PROPERTY_LIST'][str_replace('PROPERTY_', '', $vSort['CODE'])]['NAME'] ?>
                                                    <?} else {?>
                                                        <?= Loc::getMessage('USER_REG_T_TABLE_' . $vSort['CODE']) ?>
                                                    <?}?>
                                                </a>
                                                <?if($cSort == 'PROPERTY_PROP_FILE') {?></div><?}?>
                                            <a href="#"
                                               class="portlet-content-toggle url_close"
                                               data-confirm-title="<?= Loc::getMessage('SETTINGS_FILTER_SAVE_TITLE') ?>"
                                               data-confirm-question="<?= Loc::getMessage('SETTINGS_FILTER_SAVE_QUESTION') ?>"
                                               data-confirm-answer-y="<?= Loc::getMessage('SETTINGS_FILTER_SAVE_Y') ?>"
                                               data-confirm-answer-n="<?= Loc::getMessage('SETTINGS_FILTER_SAVE_N') ?>">
                                            </a>
                                        </div>
                                        <?foreach($arResult['LIST_REG_ITEMS'] as $iItem => $arItem) {
                                            $clsActiveRow = '';
                                            if($arItemActive[$iItem]) {
                                                $clsActiveRow = ' _active';
                                            }?>
                                            <div class="js-td<?= setClassFile($cSort) ?><?= $clsActiveRow ?><?= $arItem['ERROR_CLASS'] ?>" data-item="<?= $iItem ?>" title="<?= $arItem['ERROR'] ?>">
                                                <?if($cSort == 'PROPERTY_PROP_FILE') {?>
                                                    <div class="contain-upload disabled-upload"
                                                         data-ajax-file="<?= $this->GetFolder() ?>/ajax.files.php">
                                                        <?if(!empty($arItem['PROPERTY_PROP_SSCC'])) {?>
                                                            <?$APPLICATION->IncludeComponent(
                                                                'box:box.file.upload',
                                                                'row',
                                                                array(
                                                                    'INPUT_ID' => 'UNIT-' . $arItem['PROPERTY_PROP_SSCC'],
                                                                    'PATH_TO_FILES' => $arParams['PATH_TO_FILES'] . $arItem['PROPERTY_PROP_SSCC'] . '/',
                                                                )
                                                            );?>
                                                        <?} else {?>
                                                            <?= Loc::getMessage('USER_REG_T_EMPTY_SSCC') ?>
                                                        <?}?>
                                                    </div>
                                                <?}
                                                elseif(!in_array($cSort, $arParamsNotEdit)) {?>
                                                    <?$flag = true;?>
                                                    <?if(stristr($cSort, 'PROPERTY_')
                                                        && !array_key_exists(str_replace('PROPERTY_', '', $cSort), $arResult['PROPERTY_LIST_IBLOCK'][$arItem['IBLOCK_ID']])) {
                                                        $flag = false;
                                                    }?>
                                                    <?if($flag) {?>
                                                        <span class="js-to-input"
                                                              data-type="text"
                                                              data-class="form__field js-input-disabled"
                                                              data-name="UNIT_<?= $arItem['ID'] ?>_<?= $cSort ?>"
                                                              data-placeholder="<?= Loc::getMessage('PLACEHOLDER_UNIT_' . $cSort) ?>">
                                                    <?= $arItem[$cSort] ?>
                                                </span>
                                                    <?}?>
                                                <?}
                                                elseif($cSort == 'PROPERTY_PROP_WMS_STATUS') {?>
                                                    <?= $arResult['WMS_STATUS_LIST'][$arItem[$cSort]]['NAME'] ?>
                                                <?}
                                                else {?>
                                                    <?= $arItem[$cSort] ?>
                                                <?}?>
                                            </div>
                                        <?}?>
                                    </li>
                                    <?$iCode++;?>
                                <?}?>
                            <?}?>
                        </ul>
                    </div>
                </form>
                <div class="navigation">
                    <?= $arResult['NAV_PRINT'] ?>
                    <div class="pager">
                        <div class="pager__current">
                            <span><?= Loc::getMessage('USER_REG_T_COUNT_ITEMS') ?></span>
                            <form action="<?= $arResult['FORM_PATH_DEFAULT'] ?>" method="GET" class="nav-form">
                                <?= getHTMLInputExcludeParam($arGet, 'COUNT_ON_PAGE') ?>
                                <input type="text" name="COUNT_ON_PAGE" value="<?= $arResult['COUNT_ON_PAGE'] ?>" />
                            </form>
                        </div>
                    </div>
                </div>
            <?} else {?>
                <div class="form">
                    <div class="form__row">
                        <div class="_size3 frn-waiting">
                            <h3><?= Loc::getMessage('USER_REG_T_EMPTY_LIST') ?></h3>
                        </div>
                    </div>
                </div>
            <?}?>
        </div>
    </div>
</div>