<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

use Bitrix\Main\Application;

$request = Application::getInstance()->getContext()->getRequest();
$arGet = $request->getQueryList()->toArray();
$arPost = $request->getPostList()->toArray();

//region Функция установки класса у иконки обозначающей формат файла
/** Функция установки класса у иконки обозначающей формат файла
 * @param $extention - расширение хранимого файла
 * @return int|string
 */
function setClassForIcon($extention) {
    $arExtentionIcons = array(
        'fa-file-excel-o' => array('xlsx', 'xlsm', 'xlsb', 'xltx', 'xltm', 'xls', 'xlt', 'xml', 'xlam', 'xla', 'xlw'),
        'fa-file-pdf-o' => array('pdf'),
        'fa-file-word-o' => array('docx', 'docm', 'odt', 'doc', 'docm', 'dotx', 'dot'),
        'fa-file-archive-o' => array('gz', 'gzip', 'jar', '7z', 'rar', 'tar'),
        'fa-file-image-o' => array('png', 'jpg', 'jpeg', 'JPG', 'PNG'),
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
//endregion
//region Функция для установки класса для ячейки содержащей файл
/** Функция для установки класса для ячейки содержащей файл (если у ячейки код равен PROPERTY_PROP_FILE то в ячейке содержится блок для работы с файлом)
 * @param $code - проверяемый код
 * @return string
 */
function setClassFile($code) {
    $resClass = '';
    if($code == 'PROPERTY_PROP_FILE') {
        $resClass = ' js-file';
    }
    return $resClass;
}
//endregion
//region Функция установки класса у панели с опциями обработки ЕУ
/** Функция установки класса у панели с опциями обработки ЕУ
 * @param $class - разрешенная в параметрах компонента опция для работы с файлом
 * @return mixed
 */
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
//endregion
//region Проверяет возможно ли создать заказ для ЕУ с WMS статусом имеющим UF_XML_ID == $ufXmlId
/** Проверяет возможно ли создать заказ для ЕУ с WMS статусом имеющим UF_XML_ID == $ufXmlId
 * @param $ufXmlId - UF_XML_ID статуса ЕУ
 * @return array
 */
function checkPermitOrder($ufXmlId) {
    $arReturn = array(
        'PERMIT' => 'N',
        'ORDERS' => array()
    );
    $arPermitOrder = array(
        'placed_storage',                               // Размещено на хранение (Складирование)
        'delivered' => array('reception_of_units'),     // Доставлено клиенту (Заказ операций над ЕУ или ее содержимым)
        'placed_operation'                              // Размещено на хранение (Заказ операций над ЕУ или ее содержимым)
    );
    if(!empty($ufXmlId)) {
        if(isset($arPermitOrder[$ufXmlId]) || in_array($ufXmlId, $arPermitOrder)) {
            $arReturn['PERMIT'] = 'Y';
            if(is_array($arPermitOrder[$ufXmlId])) {
                $arReturn['ORDERS'] = $arPermitOrder[$ufXmlId];
            }
        }
    } else {
        $arReturn['PERMIT'] = 'Y';
    }

    return $arReturn;
}
//endregion

$formPath = $arServer['REQUEST_URI'];
if($arServer['REDIRECT_URL']) {
    $formPath = $arServer['REDIRECT_URL'];
}
if($arParams['CURRENT_PATH']) {
    $formPath = $arParams['CURRENT_PATH'];
}
$arResult['FORM_PATH_DEFAULT'] = $formPath;
if(!empty($arGet)) {
    $iGet = 0;
    foreach($arGet as $cGet => $vGet) {
        if($iGet == 0) {
            $formPath .= '?';
        } else {
            $formPath .= '&';
        }
        $formPath .= $cGet . '=' . $vGet;
    }
}
$arResult['UNITS_FORM_PATH'] = $formPath;

$arResult['COUNT_ON_PAGE'] = $arParams['COUNT_ITEM_ON_PAGE'];
if(!empty($arGet['COUNT_ON_PAGE']) && is_numeric($arGet['COUNT_ON_PAGE'])) {
    $arResult['COUNT_ON_PAGE'] = $arGet['COUNT_ON_PAGE'];
}