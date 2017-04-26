<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Application,
    \Bitrix\Main\Localization\Loc;
$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$arServer = $context->getServer()->toArray();
$arGet = $arParams['GET_PARAMS'];
$arPost = $request->getPostList()->toArray();

function setClassForIcon($extention) {
    $arExtentionIcons = array(
        'fa-file-excel-o' => array('xlsx', 'xlsm', 'xlsb', 'xltx', 'xltm', 'xls', 'xlt', 'xml', 'xlam', 'xla', 'xlw'),
        'fa-file-pdf-o' => array('pdf'),
        'fa-file-word-o' => array('docx', 'docm', 'odt', 'doc', 'docm', 'dotx', 'dot'),
        'fa-file-archive-o' => array('gz', 'gzip', 'jar', '7z', 'rar', 'tar'),
        'fa-file-image-o' => array('png', 'jpg', 'jpeg'),
        'fa-file-text-o' => array('txt'),
    );
    $resultIcon = 'fa-file-o';
    foreach ($arExtentionIcons as $clsIcon => $arExtention) {
        if(in_array($extention, $arExtention)) {
            $resultIcon = $clsIcon;
            break;
        }
    }
    return $resultIcon;
}
function setClassFile($code) {
    $resClass = '';
    if($code == 'PROPERTY_PROP_FILE') {
        $resClass = ' class="js-file hidden"';
    }
    return $resClass;
}
function setClassIcon($class) {
    $arListClass = array(
        'DOWNLOAD' => ' fa-download',   // Скачать
        'VIEW' => ' fa-eye',            // Просмотреть
        'EDIT' => ' fa-pencil',         // Редактировать
        'PRINT' => ' fa-print',         // Распечатать
        'DELETE' => ' fa-trash-o'       // Удалить
    );
    return $arListClass[$class];
}
$formPath = $arServer['REQUEST_URI'];
if($arServer['REDIRECT_URL']) {
    $formPath = $arServer['REDIRECT_URL'];
}
if($arParams['CURRENT_PATH']) {
    $formPath = $arParams['CURRENT_PATH'];
}
$arResult['FORM_PATH'] = $formPath;
$cOrder = new Orders();
$prf = $arParams['PREFIX_FOR_INPUT'];
$onlyCheck = '?' . $prf . 'FILTER=Y&' . $prf . 'CONTRACT=' . $arParams[$prf . 'CONTRACT'] . '&' . $prf . 'TYPE_ORDER=' . $arParams[$prf . 'TYPE_ORDER'] . '&ONLY_CHECK_SAVE=';
if(!empty($arGet['ONLY_CHECK_SAVE']) && $arGet['ONLY_CHECK_SAVE'] == 'Y') {
    $arResult['MESS_ONLY_CHECK'] = Loc::getMessage('SHOW_ALL');
    $onlyCheck .= 'N';
} else {
    $arResult['MESS_ONLY_CHECK'] = Loc::getMessage('SHOW_ONLY_CHECK');
    $onlyCheck .= 'Y';
}

$arResult['URL_ONLY_CHECK'] = $arParams['CURRENT_PATH'] . $onlyCheck;
if(!empty($arParams['SELECTED_UNITS'])) {
    $postfix = '?';
    $iGet = 0;
    foreach($arGet as $cGet => $vGet) {
        if($cGet != 'SHOW_ALL') {
            if($iGet != 0) {
                $postfix .= '&';
            }
            $postfix .= $cGet . '=' . $vGet;
            $iGet++;
        }
    }

    if($arGet['SHOW_ALL'] == 'Y') {
        $arResult['MESS_ONLY_CHECK'] = Loc::getMessage('SHOW_ONLY_SELECTED');
    } else {
        $arResult['MESS_ONLY_CHECK'] = Loc::getMessage('SHOW_ALL_UNITS');
        $postfix .= '&SHOW_ALL=Y';
    }
    $arResult['URL_ONLY_CHECK'] = $arParams['CURRENT_PATH'] . $postfix;
}