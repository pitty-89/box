<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Loader,
    \Bitrix\Iblock\IblockTable,
    \Bitrix\Main\Application,
    \Bitrix\Highloadblock as HL;

class UserRegistryDetailComponent extends CBitrixComponent {
    //region Задание свойств класса
    private $page = 'template';
    private $arGet = array();
    private $arPost = array();
    private $arUserGroups = array();
    private $arRegistryID = array();
    //endregion
    //region Подготовка входных параметров компонента
    /** Подготовка входных параметров компонента
     * @param $arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams) {
        return parent::onPrepareComponentParams($arParams);
    }
    //endregion
    //region Подключение файлов перевода
    /**
     * Подключение файлов перевода
     */
    public function onIncludeComponentLang() {
        Loc::loadMessages(__FILE__);
    }
    //endregion
    //region Выполнение компонента
    /**
     * Выполнение компонента
     */
    public function executeComponent() {
        $this->getRequest();                                    // Получение данных request
        $this->includeModule();                                 // Подключение модулей
        $this->getUserGroup();                                  // Возвращает список групп которым принадлежит пользователь
        if($this->arPost['F_UNIT_EDIT'] == 'Y') {
            $this->updateUnit();
        }
        $this->getListIDRegistry();                             // Возвращаем список ID инфоблоков реестров
        $this->getListContracts();                              // Возвращает список договоров пользователя
        $this->getListUnits();                                  // Возвращает список элементов реестра
        $this->getStatusWms();                                  // Возвращает массив статусов WMS
        //$this->checkSoapData();                                 // Инициализирует класс для синхронизации статусов ЕУ с базой WMS
        $this->getListOrderType();                              // Возвращает список типов заказа
        $this->includeComponentTemplate($this->page);           // Подключение шаблона
    }
    //endregion
    //region Получение данных request
    /**
     * Получение данных request
     */
    private function getRequest() {
        $request = Application::getInstance()->getContext()->getRequest();
        if(!empty($this->arParams['GET_PARAMS'])) {
            $this->arGet = $this->arParams['GET_PARAMS'];
        } else {
            $this->arGet = $request->getQueryList()->toArray();
        }
        $this->arPost = $request->getPostList()->toArray();
    }
    //endregion
    //region Подключаем список модулей
    /**
     * Подключаем список модулей
     */
    private function includeModule() {
        Loader::includeModule('iblock');
        //region Если присутствует фильтрация по статусу WMS
        if(!empty($this->arParams['OPTION_FOR_FILTER'])) {
            Loader::includeModule("highloadblock");
        }
        //endregion
    }
    //endregion
    //region Возвращает список групп пользователей
    /**
     * Возвращает список групп пользователей
     */
    private function getUserGroup() {
        global $USER;
        $userID = $this->arParams['USER_ID'];
        if(empty($userID)) {
            $userID = $USER->GetID();
            $this->arParams['USER_ID'] = $userID;
        }
        $arGroupID = array();
        $rsGroups = $USER->GetUserGroupList($userID);
        while($obUG = $rsGroups->Fetch()) {
            $arGroupID[] = $obUG['GROUP_ID'];
        }
        $arFilterGroup = array('ID' => implode('|', $arGroupID), 'ACTIVE' => 'Y');
        $rsGroup = CGroup::GetList($by = 'ID', $order = 'ASC', $arFilterGroup);
        while($obGroup = $rsGroup->Fetch()) {
            $this->arUserGroups[] = $obGroup['STRING_ID'];
        }
    }
    //endregion
    //region Возвращаем список ID инфоблоков реестров
    /**
     * Возвращаем список ID инфоблоков реестров
     */
    private function getListIDRegistry() {
        if(!empty($this->arParams['LIST_REGISTRY'])) {
            $this->arRegistryID = $this->arParams['LIST_REGISTRY'];
        }
        elseif($this->arParams['SHOW_ALL'] == 'Y') {
            if(!empty($this->arParams['USER_ID'])){
                $userID = $this->arParams['USER_ID'];
            } else {
                global $USER;
                $userID = $USER->GetID();
            }
            // Возвращаем список реестров из инфоблока "Клиенты" (даже если пользователь == суперпользователь,
            // то если у него указаны id инфоблоков реестров то список ему доступных им и ограничивается)
            $arFilterClients = array(
                'IBLOCK_CODE' => $this->arParams['IBLOCK_CODE_CLIENTS'],
                'PROPERTY_PROP_CLIENT' => $userID);
            $arSelectClients = array('ID', 'IBLOCK_ID', 'PROPERTY_PROP_REGISTRY');
            $rsClients = CIBlockElement::GetList(array(), $arFilterClients, false, false, $arSelectClients);
            $arRegistryID = array();    // массив с ID реестров указанных у пользотелей
            $arUserRegistry = array();  // массив с ID пользователей $arUserRegistry[#id пользователя#] = #массив с id реестров#
            $idIBlockClients = 0;
            while ($obClients = $rsClients->Fetch()) {
                $idIBlockClients = $obClients['IBLOCK_ID'];
                foreach($obClients['PROPERTY_PROP_REGISTRY_VALUE'] as $idRegistry) {
                    if(!in_array($idRegistry, $arRegistryID)) {
                        $arRegistryID[] = $idRegistry;
                    }
                }
                $arUserRegistry[$obClients['ID']] = $obClients['PROPERTY_PROP_REGISTRY_VALUE'];
            }
            // Если список реестров в инфоблоке клиентов пуст, проверяем, является ли пользователь суперпользователем
            $this->arResult['PROP_REQUEST'] = 'Y';
            if(empty($arRegistryList) && in_array(U_GROUP_CODE_CLIENT_S_USER, $this->arUserGroups)) {
                $this->arResult['PROP_REQUEST'] = 'N';
                $arOrderContracts = array('ID' => 'ASC');
                $arFilterContracts = array(
                    'IBLOCK_CODE' => $this->arParams['IBLOCK_CODE_CONTRACTS'],
                    'PROPERTY_PROP_SUPER_USER' => $userID);
                $arSelectContracts = array('ID', 'NAME', 'IBLOCK_ID', 'PROPERTY_PROP_ID_REGISTRY');
                $rsContracts = CIBlockElement::GetList(
                    $arOrderContracts,
                    $arFilterContracts,
                    false,
                    false,
                    $arSelectContracts);
                while($obContracts = $rsContracts->Fetch()) {
                    foreach($obContracts['PROPERTY_PROP_ID_REGISTRY_VALUE'] as $idRegistry) {
                        if(!in_array($idRegistry, $arRegistryID)) {
                            $arRegistryID[] = $idRegistry;
                        }
                    }
                }
                $arUserRegistry[$obClients['ID']] = $arRegistryID;
            }

            $rsRegistry = IblockTable::getList(array(
                'filter' => array('ID' => $arRegistryID),
                'select' => array('ID', 'NAME'),
                'order' => array('NAME' => 'ASC')
            ));
            //$rsRegistry = CIBlock::GetList(array('NAME' => 'ASC'), array('ID' => $arRegistryID));
            $arRegistryExist = array();
            while($obRegistry = $rsRegistry->Fetch()) {
                $this->arResult['LIST_REGISTRY'][$obRegistry['ID']] = $obRegistry['NAME'];
                $arRegistryExist[] = $obRegistry['ID'];
            }
            //region Вернем ID реестров которые были удалены
            $arRegistryDelete = array();
            foreach($arRegistryID as $iBlockID) {
                if(!in_array($iBlockID, $arRegistryExist)) {
                    $arRegistryDelete[] = $iBlockID;
                }
            }
            //endregion
            foreach($arUserRegistry as $userId => $arRegistryId) {
                $flag = false;
                $arRegistryIdUpdate = array();
                foreach($arRegistryId as $registryId) {
                    if(in_array($registryId, $arRegistryDelete)) {
                        $flag = true;
                    } else {
                        $arRegistryIdUpdate[] = $registryId;
                    }
                }
                //$flag = false;
                if($flag) {
                    CIBlockElement::SetPropertyValuesEx($userId, $idIBlockClients, array('PROP_REGISTRY' => $arRegistryIdUpdate));
                }
            }
            $this->arRegistryID = $arRegistryExist;
        }
    }
    //endregion
    //region Возвращает список элементов реестра
    /**
     * Возвращает список элементов реестра
     */
    private function getListUnits() {
        $this->arResult['SECTION_CODE'] = $this->arParams['VARIABLES']['CODE'];
        $arFilterRegItem = array();
        if(!empty($this->arParams['LIST_REGISTRY']) || $this->arParams['SHOW_ALL'] == 'Y') {
            $arFilterRegItem = array('IBLOCK_ID' => $this->arRegistryID);
        } elseif(!empty($this->arParams['VARIABLES']['ID']) && !is_null($this->arParams['VARIABLES']['ID'])) {
            // проверяем, доступен ли реестр пользователю (во избежание ошибки при вводе id реестра в адресной строке)
            $arServer = Application::getInstance()->getContext()->getServer()->toArray();
            // параметр определяющий будет ли редирект на страницу списка реестров
            $redirectToSef = false;
            // если пользователь принадлежит группе суперпользователей
            if(in_array(U_GROUP_CODE_CLIENT_S_USER, $this->arUserGroups)) {
                // если в списке с договорами не присутствует id реестра
                if(empty($this->arResult['LIST_CONTRACT_UNIT'][$this->arParams['VARIABLES']['ID']])) {
                    $redirectToSef = true;
                }
            }
            // если пользователь не является суперпользователем
            else {
                $arRegistry = array();
                $arOrderClient = array('ID' => 'ASC');
                $arFilterClient = array(
                    'IBLOCK_CODE' => $this->arParams['IBLOCK_CODE_CLIENTS'],
                    'PROPERTY_PROP_CLIENT' => $this->arParams['USER_ID']);
                $arSelectClient = array('ID', 'NAME', 'IBLOCK_ID', 'PROPERTY_PROP_REGISTRY');
                $rsClient = CIBlockElement::GetList($arOrderClient, $arFilterClient, false, false, $arSelectClient);
                if($obClient = $rsClient->Fetch()) {
                    $arRegistry = $obClient['PROPERTY_PROP_REGISTRY_VALUE'];
                }
                if(!in_array($this->arParams['VARIABLES']['ID'], $arRegistry)) {
                    $redirectToSef = true;
                }
            }
            if($redirectToSef) {
                header('Location: ' . $arServer['HTTP_ORIGIN'] . $this->arParams['SEF_FOLDER']);
            } else {
                $arFilterRegItem = array('IBLOCK_ID' => $this->arParams['VARIABLES']['ID']);
            }
        }
        //region Если присутствует фильтрация по статусу WMS
        if(!empty($this->arParams['OPTION_FOR_FILTER'])) {
            $arFilterRegItem['PROPERTY_PROP_WMS_STATUS'] = array_keys($this->arParams['OPTION_FOR_FILTER']);
        }
        //endregion

        if($this->arGet['F_UNIT_FILTER'] == 'Y') {
            foreach($this->arGet as $cGet => $vGet) {
                if(stristr($cGet, 'F_UNIT_FILTER_') && !empty($vGet)) {
                    $keyFilter = str_replace('F_UNIT_FILTER_', '', $cGet);
                    if(is_string($vGet)) {
                        $keyFilter = '%' . $keyFilter;
                    }
                    $arFilterRegItem[$keyFilter] = $vGet;
                }
            }
        }
        if($this->arGet['ONLY_CHECK'] == 'Y') {
            $cOrder = new Orders();
            $arFilterRegItem['ID'] = $cOrder->getListCheckUnitID();
        }
        if(!empty($this->arParams['SELECTED_UNITS']) && $this->arGet['SHOW_ALL'] != 'Y') {
            $arFilterRegItem['ID'] = $this->arParams['SELECTED_UNITS'];
        }
        if($this->arGet['ONLY_CHECK_SAVE'] == 'Y') {
            $cOrder = new Orders();
            $arFilterRegItem['ID'] = $cOrder->getListCheckUnitIDByOrders($this->arGet['F_UNIT_CONTRACT'], $this->arGet['F_UNIT_TYPE_ORDER']);
        }
        if(!empty($this->arParams['LIST_UNITS'])) {
            $arFilterRegItem['ID'] = $this->arParams['LIST_UNITS'];
        }

        if($arFilterRegItem['%IBLOCK_NAME']) {
            $rsIblock = IblockTable::getList(array(
                'filter' => array('%NAME' => $arFilterRegItem['%IBLOCK_NAME']),
                'select' => array('ID')
            ));
            $arFilterRegItem['IBLOCK_ID'] = array();
            while($obIblock = $rsIblock->fetch()) {
                $arFilterRegItem['IBLOCK_ID'][] = $obIblock['ID'];
            }
        }

        $prefNav = '';
        // данный параметр необходим для навигации по нескольким таблицам на странице
        if(!empty($this->arParams['PREFIX_NAVIGATION'])) {
            $prefNav = $this->arParams['PREFIX_NAVIGATION'];
        }
        $this->arResult[$prefNav . 'COUNT_ON_PAGE'] = $this->arParams['COUNT_ITEM_ON_PAGE'];
        $arNavParamsRegItem = array('nPageSize' => $this->arResult[$prefNav . 'COUNT_ON_PAGE'], 'iNumPage' => 1);
        if(!empty($this->arGet[$prefNav . 'COUNT_ON_PAGE']) && is_numeric($this->arGet[$prefNav . 'COUNT_ON_PAGE'])) {
            $this->arResult[$prefNav . 'COUNT_ON_PAGE'] = $this->arGet[$prefNav . 'COUNT_ON_PAGE'];
            if(!empty($this->arParams['MAX_COUNT_ITEM_ON_PAGE']) && $this->arParams['MAX_COUNT_ITEM_ON_PAGE'] < $this->arGet[$prefNav . 'COUNT_ON_PAGE']) {
                $this->arResult[$prefNav . 'COUNT_ON_PAGE'] = $this->arParams['MAX_COUNT_ITEM_ON_PAGE'];
            }
            $arNavParamsRegItem['nPageSize'] = $this->arResult[$prefNav . 'COUNT_ON_PAGE'];
        }

        if(!empty($this->arParams['GET_PARAMS']['PAGEN_1'])) {
            $arNavParamsRegItem['iNumPage'] = $this->arParams['GET_PARAMS']['PAGEN_1'] * 1;
        } elseif(!empty($this->arGet['PAGEN_1'])) {
            $arNavParamsRegItem['iNumPage'] = $this->arGet['PAGEN_1'] * 1;
        }
        $arSelectRegItem = array('ID', 'IBLOCK_NAME', 'IBLOCK_ID', 'NAME');

        $arProperties = array();
        if(!empty($this->arParams['VARIABLES']['ID'])) {
            if(is_array($this->arParams['VARIABLES']['ID'])) {
                foreach($this->arParams['VARIABLES']['ID'] as $idRegistry) {
                    $arPropertyRegistry = $this->getProperty($idRegistry);
                    $arProperties = array_merge($arProperties, $arPropertyRegistry);
                    $this->arResult['PROPERTY_LIST_IBLOCK'][$idRegistry] = $arProperties;
                }
            } else {
                $arProperties = $this->getProperty($this->arParams['VARIABLES']['ID']);
                $this->arResult['PROPERTY_LIST_IBLOCK'][$this->arParams['VARIABLES']['ID']] = $arProperties;
            }
        } else {
            foreach($this->arRegistryID as $idRegistry) {
                $arPropertyRegistry = $this->getProperty($idRegistry);
                $arProperties = array_merge($arProperties, $arPropertyRegistry);
                $this->arResult['PROPERTY_LIST_IBLOCK'][$idRegistry] = $arProperties;
            }
        }

        $this->arResult['PROPERTY_LIST'] = $arProperties;
        foreach($arProperties as $arProperty) {
            if($arProperty['ACTIVE'] == 'Y') {
                $arSelectRegItem[] = 'PROPERTY_' . $arProperty['CODE'];
            }
        }
        if(empty($this->arGet['SORT'])) {
            $arOrderRegItem = array('NAME' => 'ASC');
        } else {
            $arOrderRegItem = array($this->arGet['SORT'] => $this->arGet['ORDER']);
        }

        if(!empty($arFilterRegItem['IBLOCK_ID']) || !empty($arFilterRegItem['ID'])) {
            $rsRegItem = CIBlockElement::GetList($arOrderRegItem, $arFilterRegItem, false, $arNavParamsRegItem, $arSelectRegItem);
            $this->arResult['COUNT_UNITS'] = $rsRegItem->SelectedRowsCount();
            $this->arResult['NAV_PRINT'] = $rsRegItem->GetPageNavStringEx($navComponentObject, Loc::getMessage('USER_REG_C_PAGES'), $this->arParams['NAV_TEMPLATE']);
            $iRegItem = 0;
            // параметр отвечающий за добавление данных реестра в массив со списком реестров
            $addToListRegistry = false;
            if(empty($this->arResult['LIST_REGISTRY']) && empty($this->arParams['LIST_REGISTRY'])) {
                $addToListRegistry = true;
            } elseif(!empty($this->arParams['LIST_REGISTRY'])) {
                $rsRegistry = IblockTable::getList(array(
                    'filter' => array('ID' => $this->arParams['LIST_REGISTRY']),
                    'select' => array('ID', 'NAME')
                ));
                while($obRegistry = $rsRegistry->fetch()) {
                    $this->arResult['LIST_REGISTRY'][$obRegistry['ID']] = $obRegistry['NAME'];
                }
            }
            while($obRegItem = $rsRegItem->Fetch()) {
                if($addToListRegistry) {
                    $this->arResult['LIST_REGISTRY'][$obRegItem['IBLOCK_ID']] = $obRegItem['IBLOCK_NAME'];
                }
                if(empty($obRegItem['PROPERTY_PROP_SSCC_VALUE']) || empty($this->arParams['PATH_TO_FILES'])) {
                    $this->arResult['LIST_REG_ITEMS'][$iRegItem]['DATA_FILES']['FILE_EXIST'] = false;
                } else {
                    $this->arResult['LIST_REG_ITEMS'][$iRegItem]['DATA_FILES'] = $this->getCountFiles($this->arParams['PATH_TO_FILES'] . $obRegItem['PROPERTY_PROP_SSCC_VALUE']);
                    $countFiles = count($this->arResult['LIST_REG_ITEMS'][$iRegItem]['DATA_FILES']['FILE_URL']);
                    if($countFiles != $obRegItem['PROPERTY_PROP_FILE_VALUE']) {
                        $obRegItem['PROPERTY_PROP_FILE_VALUE'] = $countFiles;
                        CIBlockElement::SetPropertyValuesEx($obRegItem['ID'], $obRegItem['IBLOCK_ID'], array('PROP_FILE' => $countFiles));
                    }
                }
                $this->arResult['LIST_REG_ITEMS'][$iRegItem]['CONTRACT'] = $this->arResult['LIST_CONTRACT_UNIT'][$obRegItem['IBLOCK_ID']];

                foreach($obRegItem as $cItem => $arItem) {
                    if(!stristr($cItem, 'VALUE_ID')) {
                        //$cItem = str_replace('PROPERTY_', '', $cItem);
                        $cItem = str_replace('_VALUE', '', $cItem);
                        $this->arResult['LIST_REG_ITEMS'][$iRegItem][$cItem] = $arItem;
                    }
                }
                $iRegItem++;
            }
        }
        $this->setSort($arSelectRegItem);
    }
    //endregion
    //region Сверка статусов ЕУ в списке реестра со статусами на WMS сервере
    /**
     * Сверка статусов ЕУ в списке реестра со статусами на WMS сервере
     */
    private function checkSoapData() {
        if(!empty($this->arResult['LIST_REG_ITEMS'])) {
            // массив с кодами статусов WMS
            $arRelationStatus = array_keys($this->arResult['WMS_STATUS_LIST']);

            $wmsClass = new \EME\WMS(true);
            foreach($this->arResult['LIST_REG_ITEMS'] as $iUnit => $arUnit) {
                $dateTime = $wmsClass->getXMLDateTime();
                $responseStatusMu = $wmsClass->GetStatusMeasUnit($dateTime, $arUnit['PROPERTY_PROP_SSCC']);
                if($responseStatusMu->state == 1) {
                    $this->arResult['LIST_REG_ITEMS'][$iUnit]['ERROR'] = Loc::getMessage('ERROR_WMS_CONNECT_' . $responseStatusMu->descriptionError);
                    $this->arResult['LIST_REG_ITEMS'][$iUnit]['ERROR_CLASS'] = ' tooltip-option bgd-warning';
                } else {
                    // Возвращаемый статус rand(0, 14)
                    $statusMu = $responseStatusMu->listStatusMu[0]->currentStatus;
                    if($statusMu != array_search($arUnit['PROPERTY_PROP_WMS_STATUS'], $arRelationStatus)) {
                        $newStatus = $arRelationStatus[$statusMu];
                        $this->arResult['LIST_REG_ITEMS'][$iUnit]['PROPERTY_PROP_WMS_STATUS'] = $newStatus;
                        CIBlockElement::SetPropertyValuesEx($arUnit['ID'], $arUnit['IBLOCK_ID'], array('PROP_WMS_STATUS' => $newStatus));
                    }
                }
            }
        }
    }
    //endregion
    //region Возвращает массив свойств инфоблока
    /** Возвращает массив свойств инфоблока
     * @param $iblockID - ID инфоблока с реестром
     * @return array
     */
    private function getProperty($iblockID) {
        $rsProperty = CIBlock::GetProperties($iblockID);
        $arReturn = array();
        while($obProperty = $rsProperty->Fetch()) {
            $arReturn[$obProperty['CODE']] = array(
                'ID' => $obProperty['ID'],
                'NAME' => $obProperty['NAME'],
                'PROPERTY_TYPE' => $obProperty['PROPERTY_TYPE'],
                'ACTIVE' => $obProperty['ACTIVE'],
                'SORT' => $obProperty['SORT'],
                'CODE' => $obProperty['CODE'],
            );
        }
        // Если у реестра задано свойство со списком запрещенных к выводу свойств
        if(!empty($arReturn[PROPERTY_CODE_LIST_OF_PERMISSION])) {
            // Если пользователь не входит в группу суперпользователей вернем список кодов свойств ограниченных к показу для пользователя
            if(!in_array(U_GROUP_CODE_CLIENT_S_USER, $this->arUserGroups)) {
                $arListProperty = $this->getListValues($iblockID, PROPERTY_CODE_LIST_OF_PERMISSION);
                foreach($arReturn as $cProperty => $arProperty) {
                    if(in_array($cProperty, $arListProperty)) {
                        unset($arReturn[$cProperty]);
                    }
                }
            }
        }
        return $arReturn;
    }
    //endregion
    //region Метод возвращает список значений свойства
    /** Метод возвращает список значений свойства
     * @param $iblockId - id инфоблока
     * @param $cProperty - код свойства для которого необходимо вернуть список значений
     * @return array
     */
    private function getListValues($iblockId, $cProperty) {
        $arReturn = array();
        $arOrderProperty = array('SORT' => 'ASC');
        $arFilterProperty = array('IBLOCK_ID' => $iblockId);
        $rsProperty = CIBlockProperty::GetPropertyEnum($cProperty, $arOrderProperty, $arFilterProperty);
        while ($obProperty = $rsProperty->Fetch()) {
            $arReturn[] = $obProperty['XML_ID'];
        }
        return $arReturn;
    }
    //endregion
    //region Просмотр файлов
    /** Просмотр файлов
     * @param $sectionFiles
     * @return array
     */
    private function getCountFiles($sectionFiles) {
        $documentRoot = Application::getDocumentRoot();
        $firstSymbol = substr($sectionFiles, 0, 1);
        $lastSymbol = substr($sectionFiles, strlen($sectionFiles) - 1, strlen($sectionFiles) - 1);
        if($firstSymbol != '/') {
            $sectionFiles = '/' . $sectionFiles;
        }
        if($lastSymbol != '/') {
            $sectionFiles = $sectionFiles . '/';
        }
        $sectionFiles = $documentRoot . $sectionFiles;
        $countFiles = 0;
        $arTmpFiles = scandir($sectionFiles);
        $arUnitFiles = array();
        $arUnitFiles['FILE_EXIST'] = false;  // Файлы в директории отсутствуют
        if($arTmpFiles) {
            foreach($arTmpFiles as $nFile) {
                if($nFile != '.' && $nFile != '..' && !stristr($nFile, 'thumbnail')) {
                    if(!$arUnitFiles['FILE_EXIST']) {
                        $arUnitFiles['FILE_EXIST'] = true;
                    }
                    $arPathInfo = pathinfo($nFile);
                    if($countFiles == 0) {
                        $arUnitFiles['FILE_EXT'] = $arPathInfo['extension'];
                    } else {
                        $arUnitFiles['FILE_EXT'] = '';
                    }
                    $arUnitFiles['FILE_URL'][$arPathInfo['filename']] = $sectionFiles . $arPathInfo['basename'];
                    $countFiles++;
                }
            }
        }
        return $arUnitFiles;
    }
    //endregion
    //region Возвращает массив с полями для сортировки
    /** Возвращает массив с полями для сортировки
     * @param $arSort
     */
    private function setSort($arSort) {
        $arSetSort = $arSort;
        $arRowSort = array();
        $preffixGet = '';
        if(!empty($this->arGet)) {
            $iGet = 0;
            foreach($this->arGet as $cGet => $vGet) {
                if(!empty($this->arParams['SET_SORT_FILTER'])) {
                    if(!empty($this->arParams['SET_SORT_FILTER'][$cGet])) {
                        continue;
                    }
                }
                if($cGet != 'SORT' && $cGet != 'ORDER') {
                    if($iGet == 0) {
                        $preffixGet .= '?';
                    } else {
                        $preffixGet .= '&';
                    }
                    if(is_array($vGet)) {
                        foreach($vGet as $iVal => $val) {
                            $preffixGet .= $cGet . urlencode('[]') . '=' . urlencode($val);
                            if($iVal + 1 != count($vGet)) {
                                $preffixGet .= '&';
                            }
                        }
                    } else {
                        $preffixGet .= $cGet . '=' . $vGet;
                    }

                    $iGet++;
                }
            }
        }
        if(!empty($this->arParams['SET_SORT_FILTER'])) {
            $iFilter = 0;
            foreach($this->arParams['SET_SORT_FILTER'] as $cCode => $vCode) {
                if($iFilter == 0 && $preffixGet == '') {
                    $preffixGet .= '?';
                } else {
                    $preffixGet .= '&';
                }
                $preffixGet .= $cCode . '=' . $vCode;
                $iFilter++;
            }
        }
        if(!empty($this->arParams['CODE_OPTIONS_TO_SHOW'])) {
            $arSetSort = $this->arParams['CODE_OPTIONS_TO_SHOW'];
        }
        foreach($arSetSort as $cSort) {
            if($cSort != 'PROPERTY_' . PROPERTY_CODE_LIST_OF_PERMISSION) {
                $order = 'ASC';

                if(!empty($this->arGet['SORT']) && $cSort == $this->arGet['SORT'] && $this->arGet['ORDER'] == $order) {
                    $order = 'DESC';
                }
                $arRowSort[$cSort]['CODE'] = $cSort;
                if($preffixGet == '') {
                    $arRowSort[$cSort]['URL'] = '?';
                } else {
                    $arRowSort[$cSort]['URL'] = $preffixGet . '&';
                }
                $arRowSort[$cSort]['URL'] .= 'SORT=' . $cSort . '&ORDER=' . $order;
            }
        }
        $this->arResult['ROW_SORT'] = $arRowSort;
    }
    //endregion
    //region Обновляет значения элемента
    /**
     * Обновляет значения элемента
     */
    private function updateUnit() {
        $arPost = $this->arPost;
        if(!empty($arPost['L_CHECK_UNIT'])) {
            foreach($arPost['L_CHECK_UNIT'] as $unitData) {
                $preffixUnitID = 'UNIT_ID-';
                $preffixIBlockID = '-IBLOCK_ID-';
                $nUnitData = str_replace($preffixUnitID, '', $unitData);

                $unitID = substr($nUnitData, 0, strpos($nUnitData, $preffixIBlockID));
                $iblockID = substr($unitData, strpos($unitData, $preffixIBlockID) + strlen($preffixIBlockID));
                $arLoadUnit = array();
                $arLoadUnitProperty = array();
                foreach($arPost as $cPost => $vPost) {
                    if(stristr($cPost, 'UNIT_' . $unitID . '_')) {
                        $keyLoad = str_replace('UNIT_' . $unitID . '_', '', $cPost);
                        if($keyLoad == 'IBLOCK_NAME') {
                            $iBlock = new CIBlock;
                            $arFieldsIBlock = array('NAME' => $vPost);
                            $iBlock->Update($iblockID, $arFieldsIBlock);
                        } elseif(!stristr($keyLoad, 'PROPERTY_')) {
                            $arLoadUnit[str_replace('UNIT_' . $unitID . '_', '', $cPost)] = $vPost;
                        } else {
                            $keyLoad = str_replace('PROPERTY_', '', $keyLoad);
                            $arLoadUnitProperty[$keyLoad] = $vPost;
                        }
                    }
                }
                $el = new CIBlockElement;
                $el->Update($unitID, $arLoadUnit);
                if(!empty($arLoadUnitProperty)) {
                    CIBlockElement::SetPropertyValuesEx($unitID, $iblockID, $arLoadUnitProperty);
                }
            }
        }
    }
    //endregion
    //region Возвращает массив статусов WMS
    /**
     * Возвращает массив статусов WMS
     */
    private function getStatusWms() {
        if($this->arParams['OPTION_FOR_FILTER']) {
            $filter = array('UF_XML_ID' => array_keys($this->arParams['OPTION_FOR_FILTER']));
        } else {
            $filter = array();
        }
        $idHLBlock = $this->getIdHLBlock(HL_BLOCK_CODE_STATUS_WMS);
        $dataClass = $this->getDataClass($idHLBlock);
        $rsStatus = $dataClass::getList(array(
            'filter' => $filter,
            'select' => array('*'),
            'limit' => '',
            'order' => array('UF_SORT' => 'ASC')
        ));
        while($obStatus = $rsStatus->Fetch()) {
            $this->arResult['WMS_STATUS_LIST'][$obStatus['UF_XML_ID']]['NAME'] = $obStatus['UF_NAME'];
            if($this->arParams['OPTION_FOR_FILTER']) {
                $this->arResult['WMS_STATUS_LIST'][$obStatus['UF_XML_ID']]['SHOW'] = $this->arParams['OPTION_FOR_FILTER'][$obStatus['UF_XML_ID']];
            }
        }
    }
    //endregion
    //region Возвращаем id HL блока по его коду
    /**
     * Возвращаем id HL блока по его коду
     */
    private function getIdHLBlock($codeHL) {
        $connection = Application::getConnection();
        $sql = "SELECT ID,NAME FROM b_hlblock_entity WHERE NAME='" . $codeHL . "';";
        $recordset = $connection->query($sql)->fetch();
        return $recordset['ID'];
    }
    //endregion
    //region Возвращаем класс для работы с HL блоком
    /** Возвращаем класс для работы с HL блоком
     * @throws \Bitrix\Main\SystemException
     */
    private function getDataClass($idHL) {
        $hlblock = HL\HighloadBlockTable::getById($idHL)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        return $entity->getDataClass();
    }
    //endregion
    //region Возвращает список типов заказа
    /**
     * Возвращает список типов заказа
     */
    private function getListOrderType() {
        if($this->arParams['USE_ORDER_TYPE'] == 'Y') {
            $rsOrderType = CIBlockProperty::GetPropertyEnum(
                                    'TYPE_ORDER',
                                    array('sort' => 'ASC'),
                                    array('IBLOCK_ID' => $this->getIDIBlock($this->arParams['IBLOCK_CODE_ORDERS']))
                                );
            $arCTypeOrderClosed = array(
                'first_placement',      // Первичное размещение
                'checkout_out_expert',  // Выезд эксперта
                'supplies',             // Доставка расходных материалов
            );
            while($obType = $rsOrderType->Fetch()) {
                if(!in_array($obType['XML_ID'], $arCTypeOrderClosed)) {
                    $this->arResult['TYPE_ORDERS_LIST'][$obType['XML_ID']] = array(
                        'ID'        => $obType['ID'],
                        'VALUE'     => $obType['VALUE'],
                    );
                }
            }
        }
    }
    //endregion
    //region Возвращает ID инфоблока по его коду
    /** Возвращает ID инфоблока по его коду
     * @param $code - код инфоблока
     * @return mixed
     */
    private function getIDIBlock($code) {
        $arIBlock = IblockTable::getList(array(
            'filter' => array('CODE' => $code),
            'select' => array('ID'),
            'order' => array('NAME' => 'ASC')
        ))->fetch();
        return $arIBlock['ID'];
    }
    //endregion
    //region Возвращает список договоров суперпользователя
    /**
     * Возвращает список договоров суперпользователя
     */
    private function getListContracts() {
        if(!empty($this->arParams['USER_ID'])){
            $userID = $this->arParams['USER_ID'];
        } else {
            global $USER;
            $userID = $USER->GetID();
        }
        $arSortContracts = array('ID' => 'ASC');
        $arFilterContracts = array(
                'IBLOCK_CODE' => $this->arParams['IBLOCK_CODE_CONTRACTS'],
                'PROPERTY_PROP_SUPER_USER' => $userID
            );
        $arSelectContracts = array('ID', 'NAME', 'IBLOCK_ID', 'PROPERTY_PROP_ID_REGISTRY');
        $rsContracts = CIBlockElement::GetList($arSortContracts, $arFilterContracts, false, false, $arSelectContracts);
        while($obContracts = $rsContracts->Fetch()) {
            foreach($obContracts['PROPERTY_PROP_ID_REGISTRY_VALUE'] as $idRegistry) {
                $this->arResult['LIST_CONTRACT_UNIT'][$idRegistry] = array(
                    'ID' => $obContracts['ID'],
                    'NAME' => $obContracts['NAME'],
                );
            }
        }
    }
    //endregion
}