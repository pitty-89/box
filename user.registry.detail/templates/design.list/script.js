$(document).ready(function () {
    //region Задание переменных
    var ids = {
            v   : '#vertical',
            hl  : '#horizontal-left',
            hr  : '#horizontal-right',
            tb  : '#registry-table',
            cn  : '#js-controls',
            ac  : '#control-action-toggle',
            lr  : '#list-row-sortable'
        },
        cls = {
            form        : {
                form       : '.js-edit-unit',
                table       : '.table',
                input       : 'form__field',
                span        : '.js-to-input',
                tdinput     : 'min-padding',
                checkbox    : {
                    fix         : 'table-fix-checkbox',
                    unit        : '.js-table-checkbox',
                    all         : '.js-table-checkbox-all'
                }
            },
            scroll      : {
                outer       : '.scrollbar-outer',
                content     : '.scroll-content'
            },
            split       : {
                top         : '.top_panel',
                bottom      : '.bottom_panel',
                left        : '.left_panel',
                right       : '.right_panel',
                cleft       : '_left',
                cright      : '_right'
            },
            btn         : {
                disabled    : 'btn-disabled',
                submit      : '.js-btn-submit'
            },
            action      : {
                active      : '_active',
                hidden      : 'hidden',
                noactive    : 'no-active-row'
            },
            file        : {
                td          : '.js-file',
                blk         : '.contain-upload',
                list        : '.js-file-list',
                iframe      : '.js-file-iframe',
                active      : 'js-file-active',
                disabled    : 'disabled-upload',
                delete      : '.js-file-delete'
            },
            option      : {
                controls    : '.control__tools',
                button      : '.js-option',
                disabled    : 'js-option-disabled',
                download    : 'js-o-download',
                view        : 'js-o-view',
                edit        : 'js-o-edit',
                print       : 'js-o-print',
                delete      : 'js-o-delete'
            },
            viewer      : {
                manager     : '.fl-manager',
                container   : '.fl-container',
                blimage     : '.fl-c-image',
                pfile       : '.fl-m-numfile',
                ppage       : '.fl-m-numpage',
                arrow       : '.pager__arrow',
                bnext       : '_next',
                bprev       : '_prev',
                bdisable    : 'js-nav-disabled',
                managehide  : 'pager__hidden',
                cactive     : 'fl-c-active',
                pref        : '.file-',
                bdownload   : '.fl-m-download',
                hcdownload  : '.fl-md-current',
                htmlpages   : '.js-pages'
            },
            order       : {
                new     : '.js-new-order',
                add     : 'js-add-to-order',
                reset   : '.js-btn-reset',
                contain : '.control__actions',
                btn     : '.js-btn-order'
            },
            list        : {
                l       : '.list-row',
                td      : '.js-td',
                wto     : 'slow-width-to',
                wout    : 'slow-width-out',
                licheck : '.lr-checkbox'
            },
            tooltip     : '.tooltip-option'
        },
        loadAddScrypts = 1,
        useVerticalSplit = 0;
    //endregion
    //region Установка для фильтра функции перетаскивания
    var sortable = {
            form    : '#sortable',
            column  : '.column',
            columns : 'column',
            item    : '.portlet',
            iheader : '.portlet-header',
            icancel : '.portlet-toggle',
            icontent: '.portlet-content',
            iplaceh : 'portlet-placeholder ui-corner-all',
            iadd    : 'ui-widget ui-widget-content ui-helper-clearfix ui-corner-all',
            ihadd   : 'ui-widget-header ui-corner-all',
            span    : 'ui-icon ui-icon-minusthick portlet-toggle',
            toggle  : '.portlet-content-toggle'
        },
        filter = {
            list        : '.list-filter-settings',
            listshow    : 'lfs-show',
            toggle      : '.btn-settings',
            fa          : '.fa',
            spin        : 'fa-spin',
            link        : {
                save    : '.lfs-save',
                delete  : '.lfs-delete',
                revert  : '.lfs-revert'
            }
        },
        listData = {
            default         : 'Y',
            columnObj       : {},
            hiddenObj       : {}
        },
        stData = {
            default         : 'Y',
            columnObj       : {},
            hiddenObj       : {},
            posSplitVert    : 95,
            posSplitHor     : 30
        },
        objLS = {
            default : 'Y'
        },
        objTab = {
            default : 'Y'
        },
        togglespeed = 400;
    //endregion
    //region Разбивка рабочей области на область просмотра документа (5%) и область со списком ЕУ
    if($(ids.v).children('.' + cls.split.cright).data('right') == 'Y') {
        useVerticalSplit = 1;
    }
    if(useVerticalSplit == 1) {
        $(ids.v).split({
            orientation : 'vertical',
            limit       : 100,
            position    : stData.posSplitVert + '%',
            onDragEnd   : function() {
                setColumn(true);
            }
        });
    }
    setColumn(false);
    //endregion
    //region Сброс всех выделенных ЕУ
    $(document).on('click', cls.order.reset, function (e) {
        e.preventDefault();
        var dataSend = { ACTION      : 'RESET' };
        startLoader($(ids.tb).closest(cls.split.bottom));
        resetCheckedUnit();
        //region Отправка ajax запроса
        sendDataOrder(dataSend);
        //endregion
    });
    //endregion
    //region Обновление блока управления заказами
    var dataSendOrder = { ACTION : 'REFRESH' };
    startLoader($(ids.ac));
    sendDataOrder(dataSendOrder);
    //endregion
    //region Проверяем наличие выделенных строк в таблице
    checkRowChecked();
    //endregion
    //region Клик по чекбоксу отдельной ЕУ
    $(document).on('click', cls.form.checkbox.unit, function(e){
        var t = $(this),
            list = t.closest(cls.list.l),
            rowTd = t.closest(cls.list.td),
            iItem = rowTd.data('item'),
            row = t.closest('tr'),
            dataSend = {
                ACTION      : '',
                ID          : getObjUnit(t.val()).unit,
                STATUS      : t.data('status'),
                CONTRACT_ID : t.data('contract')
            };
        //region Проверяем наличие выделенных строк в таблице
        checkRowChecked();
        //endregion
        //region Запуск лоадера
        startLoader($(ids.ac));
        //endregion
        list.find(cls.list.td + '[data-item="' + iItem + '"]').toggleClass(cls.action.active);
        //region Выборка действия
        // Выделили checkbox
        if(rowTd.hasClass(cls.action.active)) {
            dataSend.ACTION = 'ADD';
        }
        // Сняли выделенный checkbox
        else {
            dataSend.ACTION = 'REMOVE';
        }
        //endregion
        //region Отправка ajax запроса
        sendDataOrder(dataSend);
        //endregion
    });
    //endregion
    //region Клик по чекбоксу "выделить все"
    $(document).on('click', cls.form.checkbox.all, function(e){
        var t = $(this),
            controls = $(ids.cn).find(cls.option.controls),
            dataSend = {
                MULTISELECT : 'Y',
                ACTION      : '',
                ID          : {},
                CONTRACT_ID    : {}
            };
        startLoader($(ids.ac));
        if(t.prop('checked')) {
            $(ids.tb).find(cls.list.td).not('.' + cls.action.active).addClass(cls.action.active);
        } else {
            $(ids.tb).find(cls.list.td + '.' + cls.action.active).removeClass(cls.action.active);
        }

        $(ids.tb).find(cls.form.checkbox.unit).each(function(iUnit, yUnit) {
            var contractID = $(yUnit).data('contract'),
                unitID = getObjUnit($(yUnit).val()).unit,
                iContract = 0;
            if(!dataSend.CONTRACT_ID[contractID]) {
                dataSend.CONTRACT_ID[contractID] = {};
            }
            iContract = countOfOject(dataSend.CONTRACT_ID[contractID]);
            dataSend.CONTRACT_ID[contractID][iContract] = unitID;

            dataSend.ID[unitID] = $(yUnit).data('status');
        });
        if(t.prop('checked')) {
            controls.find('li').removeClass(cls.option.disabled);
            dataSend.ACTION = 'ADD';
        } else {
            controls.find('li').addClass(cls.option.disabled);
            dataSend.ACTION = 'REMOVE';
        }
        sendDataOrder(dataSend);
    });
    //endregion
    //region Функция проверки выделенных элементов в таблице
    function checkRowChecked() {
        var table = $(ids.tb),
            controls = $(ids.cn).find(cls.option.controls),
            countCheck = 0;
        //region Проверка количества включенных чекбоксов
        table.find(cls.form.checkbox.unit).each(function(i,y){
            if($(y).prop('checked')) {
                countCheck = countCheck + 1;
            }
        });
        //endregion
        //region Блокировка и разблокировка панели редактирования ЕУ
        if(countCheck == 0) {
            controls.find('li').addClass(cls.option.disabled);
        } else {
            controls.find('li').removeClass(cls.option.disabled);
        }
        //endregion
    }
    //endregion
    //region Добавление ЕУ в создающийся заказ
    $(document).on('click', cls.order.btn, function(e){
        e.preventDefault();
        var t = $(this),
            curSelect = t.closest(cls.order.contain).find('select'),
            dataSend = {
                ACTION  : 'CREATE',
                TYPE    : curSelect.val(),
                REQUEST : $(ids.tb).data('request'),
                CONTRACT: $(ids.tb).find(cls.list.licheck)
                    .find('.' + cls.action.active).first()
                    .find(cls.form.checkbox.unit).data('contract')
            };
        if(curSelect.hasClass(cls.order.add)) {
            dataSend.ACTION = 'ADD_TO';
        }
        //region Запуск лоадера
        startLoader($(ids.ac));
        //endregion
        $.ajax({
            url     : $(ids.tb).data('ajax-order'),
            data    : dataSend,
            type    : 'post',
            success : function(data) {
                stopLoader();
                resetCheckedUnit();
                $(ids.ac).html(data);
            }
        });
    });
    //endregion
    //region Отправка ajax запроса на получение формы для добавления ЕУ в создающийся заказ
    /**
     * Отправка ajax запроса на получение формы для добавления ЕУ в создающийся заказ
     * @param dataToSend - данные для отправки
     */
    function sendDataOrder(dataToSend) {
        dataToSend.CHECK_URI = $(ids.ac).data('uri-check');
        dataToSend.CHECK_CUR = $(ids.ac).data('check');
        $.ajax({
            url     : $(ids.tb).data('ajax-order'),
            data    : dataToSend,
            type    : 'post',
            success : function(data) {
                stopLoader();
                $(ids.ac).html(data);
                $(ids.ac).find('select').styler({
                    onSelectClosed : function() {
                        var select = $(this).closest('div').find('select'),
                            btn = $(this).closest(cls.order.contain).find('button');
                        if(select.val() != '' && btn.hasClass(cls.btn.disabled)) {
                            btn.removeClass(cls.btn.disabled);
                        } else if(select.val() == '' && !btn.hasClass(cls.btn.disabled)) {
                            btn.addClass(cls.btn.disabled);
                        }
                    }
                });
                $(cls.tooltip).tooltip({
                    position    : { my  : 'left-100% bottom' },
                    tooltipClass: 'row__tooltip'
                });
            }
        });
    }
    //endregion
    //region Клик по одной из кнопок управления ЕУ
    $(cls.option.button).on('click', function(e){
        e.preventDefault();
        var t = $(this),
            form = t.closest(cls.form.form),
            btnsubmit = form.find(cls.btn.submit),
            tItem = t.closest('li'),
            controls = tItem.closest(cls.option.controls),
            c = {
                temp    : 'temp-class',
                list    : 'temp-edit',
                input   : 'temp-input'
            };
        //region Если кнопка не заблокирована, т.е. чекбоксы были выделены
        if(!tItem.hasClass(cls.option.disabled)) {
            var table = $(ids.tb);
            //region Если действия уже производятся (т.е. клик по иконке действия был произведен), активируем все остальные иконки
            if(controls.hasClass(c.list)) {
                controls.removeClass(c.list).find('li').removeClass(cls.option.disabled);
                //region Активируем неактивные строки таблицы
                table.find('.' + cls.action.noactive).removeClass(cls.action.noactive);
                //endregion
                //region Если выбрана опция редактирования то скрываем кнопку "Изменить" и блок с возможностью загрузки файлов
                if(t.hasClass(cls.option.edit)) {
                    table.find(cls.list.td + cls.file.td).each(function(ifile, yfile){
                        var iitem = $(yfile).data('item');
                        $(yfile).removeClass(cls.list.wto).addClass(cls.list.wout);
                        setTimeout(function(){
                            $(yfile).removeClass(cls.list.wout);
                        }, 300);
                        if($(yfile).hasClass(cls.action.active)) {
                            $(yfile).find(cls.file.blk).addClass(cls.file.disabled);
                            table.find(cls.list.td + '[data-item="' + iitem + '"]').removeClass(cls.form.checkbox.fix);
                        }
                    });
                    btnsubmit.addClass(cls.btn.disabled);
                }
                //endregion
                //region У всех активных строк таблицы удаляем инпуты и показываем только подписи значений свойств
                table.find(cls.list.td + '.' + cls.action.active).each(function(i, y) {
                    var cell = $(y);
                    cell.find('.' + c.input).remove();
                    cell.find(cls.form.span).removeClass(cls.action.hidden).closest('td');
                    cell.removeClass(cls.form.tdinput);
                });
                //endregion
            }
            //endregion
            //region Клик по иконке действия произведен впервые (выделяем лишь ту по которой кликнули, остальные деактивируем)
            else {
                //region Активному элементу списка добавляем временный класс, деактивируем остальные элементы управления и удаляем временный класс у активного
                tItem.addClass(c.temp);
                controls.addClass(c.list).find('li').not('.' + c.temp).addClass(cls.option.disabled);
                tItem.removeClass(c.temp);
                //endregion
                //region Деактивируем неактивные строки таблицы
                table.find(cls.list.td).not('.' + cls.action.active).addClass(cls.action.noactive);
                //endregion
                //region Если выбрана опция редактирования то добавляем ячейку с возможностью загрузки файлов и активируем кнопку "Изменить"
                if(t.hasClass(cls.option.edit)) {
                    table.find(cls.list.td + cls.file.td).each(function(ifile, yfile){
                        var iitem = $(yfile).data('item');
                        //$(y).removeClass(cls.action.hidden);
                        $(yfile).addClass(cls.list.wto);
                        if($(yfile).hasClass(cls.action.active)) {
                            $(yfile).find(cls.file.blk).removeClass(cls.file.disabled);
                            table.find(cls.list.td + '[data-item="' + iitem + '"]').addClass(cls.form.checkbox.fix);
                        }
                    });
                    btnsubmit.removeClass(cls.btn.disabled);
                }
                //endregion
                //region Перебираем все выделенные чекбоксы в таблице
                table.find(cls.list.licheck).find(cls.list.td + '.' + cls.action.active).each(function(ic, yc) {
                    var iitem = $(yc).data('item');
                        //row = $(y);             // активная строка в таблице
                    table.find(cls.list.td + '.' + cls.action.active + '[data-item="' + iitem + '"]').each(function(is, ys) {
                        if($(ys).find(cls.form.span).length > 0) {
                            //region Выбрано редактирование
                            if(t.hasClass(cls.option.edit)) {
                                var span = $(ys).find(cls.form.span),
                                    s = {
                                        value       : $.trim(span.html()),
                                        name        : span.data('name'),
                                        placeholder : span.data('placeholder'),
                                        id          : span.data('id')
                                    },
                                    wColumn = span.closest(cls.list.td).width(),
                                    tmpHtml = '';
                                //span.closest(cls.list.td).width(wColumn);
                                //region Вставка инпутов
                                if(span.data('type') == 'text') {
                                    tmpHtml = '<input class="' + c.input + ' ' + cls.form.input + '"' +
                                        ' name="' + s.name + '"' +
                                        ' value="' + s.value + '"' +
                                        ' style="width: ' + wColumn + 'px;"' +
                                        ' placeholder="' + s.placeholder + '" />';
                                    span.after(tmpHtml);
                                } else if(span.data('type') == 'select') {
                                    tmpHtml = '<select class="' + c.dtemp + '"' +
                                        ' name="' + s.name + '"' +
                                        ' id="' + s.id + '">';
                                    tmpHtml += '<option value=""';
                                    if(s.value == '') {
                                        tmpHtml += ' selected';
                                    }
                                    tmpHtml += '>-</option>';
                                    span.find('small').each(function(ism, ysm) {
                                        var o = {
                                            value   : $(ysm).data('value'),
                                            text    : $.trim($(ysm).html)
                                        };
                                        tmpHtml += '<option value="' + o.value + '"';
                                        if(s.value == o.value) {
                                            tmpHtml += ' selected';
                                        }
                                        tmpHtml += '>' + o.text + '</option>';
                                    });
                                    tmpHtml += '</select>';
                                }
                                span.addClass(cls.action.hidden).closest(cls.list.td).addClass(cls.form.tdinput);
                                //endregion
                                //region Запуск плагина formstyler для вставленных инпутов
                                table.find('.' + cls.form.input).trigger('refresh');
                                //endregion
                            }
                            //endregion
                        }
                    });
                });
                //endregion
            }
            //endregion
        }
        //endregion
    });
    //endregion
    //region Отправка формы при ключеннной кнопкуе изменить
    $(cls.btn.submit).on('click', function(e){
        e.preventDefault();
        var $this = $(this),
            form = $this.closest(cls.form.form);
        if(!$this.hasClass(cls.btn.disabled)) {
            form.submit();
        }
    });
    //endregion
    //region Возвращает список документов ЕУ
    $(document).on('click', cls.file.list, function(e){
        e.preventDefault();
        var $this = $(this),
            url = $this.closest(cls.file.blk).data('ajax-file'),
            dataSend = {
                SFILES : 'Y',
                FOLDER : $this.data('files'),
                LOAD_SCRYPTS : loadAddScrypts
            },
            bFiles = $this.closest(cls.file.blk);
        if(useVerticalSplit == 1) {
            startLoader($(ids.v).find(cls.split.right));
            refreshSplit(ids.v, 'vertical', 60);
            setSplit(ids.hr, 'horizontal', 100, '80%');
            loadAddScrypts = 0;

            getRightHTML($this.closest(cls.file.blk).data('ajax-file'), dataSend);
            stopLoader();
        }
    });
    //endregion
    //region Функция обновления рабочей области справа страницы со списком файлов ЕУ и просмотровщиком
    function getRightHTML(url, data) {
        $.ajax({
            url : url,
            data : data,
            type : 'post',
            success : function(data) {
                var rdata = $.parseJSON(data);
                if($(ids.hr).find(cls.split.bottom).find(cls.scroll.outer).find(cls.scroll.content).length > 0) {
                    $(ids.hr).find(cls.split.bottom).find(cls.scroll.outer).find(cls.scroll.content).empty();
                    $(ids.hr).find(cls.split.bottom).find(cls.scroll.outer).find(cls.scroll.content).append(rdata.HTML_LIST);
                } else {
                    $(ids.hr).find(cls.split.bottom).find(cls.scroll.outer).append(rdata.HTML_LIST);
                }
                if($(ids.hr).find(cls.split.top).find(cls.scroll.outer).find(cls.scroll.content).length > 0) {
                    $(ids.hr).find(cls.split.top).find(cls.scroll.outer).find(cls.scroll.content).empty();
                    $(ids.hr).find(cls.split.top).find(cls.scroll.outer).find(cls.scroll.content).append(rdata.HTML_VIEWER);
                } else {
                    $(ids.hr).find(cls.split.top).find(cls.scroll.outer).append(rdata.HTML_VIEWER);
                }
                stopLoader();
            }
        });
    }
    //endregion
    //region Показывает отдельный документ для просмотра
    $(document).on('click', cls.file.iframe, function (e){
        e.preventDefault();
        var t = $(this),
            numbFile = t.data('f-number'),                      // номер файла для показа
            blkmanage = $(ids.hr).find(cls.viewer.manager),     // блок управления просмотровщика
            container = $(ids.hr).find(cls.viewer.container),   // блок с содержимым изображений просмотровщика
            bHide = container.find('.' + cls.viewer.cactive),   // блок с изображением для скрытия
            bShow = container.find(cls.viewer.pref + numbFile), // блок с изображением для показа
            imgShow = bShow.find('img'),                        // изображение предназначенное для показа
            bFiles = blkmanage.find(cls.viewer.pfile),          // блок управления файлами
            bPager = blkmanage.find(cls.viewer.ppage),          // блок управления страницами
            langFrom = bPager.data('langPref'),                 // языковое значение для указания номера страницы
            uriDownload = '',                                   // ссылка на скачивание файла
            inputFiles = bFiles.find('input'),
            inputPager = bPager.find('input');

        if(!t.hasClass(cls.file.active)) {
            t.closest('ol').find('.' + cls.file.active).removeClass(cls.file.active);
            t.addClass(cls.file.active);
            //region Если блок с указанным номером скрыт
            if((inputFiles.val() * 1) != numbFile) {
                startLoader(container);
                //region Изменяем активность у блоков с изображениями
                bHide.removeClass(cls.viewer.cactive);
                bShow.addClass(cls.viewer.cactive);
                //endregion
                //region Изменяем значение номера файла
                inputFiles.val(numbFile);
                //endregion
                //region Проверка активности кнопок переключения файлов
                checkArrowActive(bFiles, numbFile, container.find(cls.viewer.blimage).size());
                //endregion
                if(imgShow.data('pdf') == 'Y') {
                    //region Определение переменных
                    var nPage = imgShow.data('current'),                // активная страница у данного pdf файла
                        cPage = inputPager.val() * 1,                   // номер страницы указанный в блоке управления страницами
                        srcPdf = imgShow.data('src'),                   // путь к файлу
                        countPages = imgShow.data('pages') * 1,         // количество страниц в файле pdf
                        stringCount = langFrom + '' + countPages;       // текст для вставки в блок описывающий количество страниц

                    //endregion
                    bPager.attr('data-pages', countPages).find(cls.viewer.htmlpages).html(stringCount);
                    //region Если номер активной страницы указанный у активного изображения не равен текущему значению указанному в блоке управления страницами
                    if(nPage != cPage) {
                        inputPager.val(nPage);
                    }
                    //endregion
                    //region Обновляем классы у кнопок переключения страниц (активируем / деактивируем)
                    checkArrowActive(bPager, nPage, countPages);
                    //endregion
                    //region Обновление значений параметров в блоке управления страницами
                    bPager.data('src', srcPdf);
                    bPager.data('pages', countPages);
                    //endregion
                    //region Если количество страниц больше 1 и блок управления был скрыт то показываем его
                    if(countPages > 1 && bPager.hasClass(cls.viewer.managehide)) {
                        bPager.removeClass(cls.viewer.managehide);
                    }
                    //endregion
                    //region Формируем ссылку на скачивание файла
                    uriDownload = imgShow.data('src');
                    //endregion
                }
                else {
                    //region Если блок с навигацией по страницам показан то скрыть
                    if(!bPager.hasClass(cls.viewer.managehide)) {
                        bPager.addClass(cls.viewer.managehide);
                    }
                    //endregion
                    //region Формируем ссылку на скачивание файла
                    uriDownload = imgShow.attr('src');
                    //endregion
                }
                //region Обновляем значение на скачивание файла
                blkmanage.find(cls.viewer.bdownload).find(cls.viewer.hcdownload).attr('href', uriDownload);
                //endregion
                stopLoader();
            }
            //endregion
        }

    });
    //endregion
    //region Удаление файлов ЕУ
    $(document).on('click', cls.file.delete, function(e) {
        e.preventDefault();
        var t = $(this),
            li = t.closest('li'),
            list = li.closest('ol'),
            urlcontent = li.find(cls.file.iframe);
        $.confirm({
            theme               : 'light',
            backgroundDismiss   : true,
            animation           : 'top',
            closeAnimation      : 'bottom',
            title               : t.data('lang-t'),
            content             : t.data('lang-q'),
            buttons             : {
                confirm: {
                    keys : ['enter'],
                    text : t.data('lang-y'),
                    action : function () {
                        startLoader(ids.hr);
                        var dataSend = {
                                F_DELETE: 'Y',
                                F_PATH  : urlcontent.data('files'),
                                F_NAME  : urlcontent.attr('href'),
                                F_FILES : t.data('ajax-files')
                            };
                        $.ajax({
                            type    : 'post',
                            url     : t.data('ajax-delete'),
                            data    : dataSend,
                            success : function(data) {
                                var countFiles = data * 1;
                                if(countFiles == 0) {
                                    location.reload();
                                } else {
                                    var ds = {
                                            SFILES : 'Y',
                                            FOLDER : dataSend.F_PATH,
                                            LOAD_SCRYPTS : loadAddScrypts
                                        };
                                    getRightHTML(dataSend.F_FILES, ds);
                                }
                            }
                        });
                    }
                },
                cancel: {
                    keys : ['esc'],
                    text : t.data('lang-n'),
                    action : function () {}
                }
            }
        });
    });
    //endregion
    //region Функция проверки активности кнопок переключения (страниц / файлов)
    /**
     * Функция проверки активности кнопок переключения (страниц / файлов)
     * @param bmanage - блок управления кнопками переключения
     * @param npage - номер страницы / файла на которую необходимо переключаться
     * @param cpages - максимальное значение страниц / файлов
     */
    function checkArrowActive(bmanage, npage, cpages) {
        bmanage.find(cls.viewer.arrow).removeClass(cls.viewer.bdisable);
        if(npage == 1) {
            if(!bmanage.find('.' + cls.viewer.bprev).hasClass(cls.viewer.bdisable)) {
                bmanage.find('.' + cls.viewer.bprev).addClass(cls.viewer.bdisable);
            }
        }
        else if (npage == cpages) {
            if(!bmanage.find('.' + cls.viewer.bnext).hasClass(cls.viewer.bdisable)) {
                bmanage.find('.' + cls.viewer.bnext).addClass(cls.viewer.bdisable);
            }
        }
    }
    //endregion
    //region Скрытие блока с input
    $(document).on('click', sortable.toggle, function(e) {
        e.preventDefault();
        var t = $(this),
            blkToHide = t.closest(sortable.item),
            blockContain = $(sortable.form);
        if(t.closest(ids.tb).length > 0) {
            blockContain = $(ids.tb);
        }
        $.confirm({
            theme               : 'light',
            backgroundDismiss   : true,
            animation           : 'top',
            closeAnimation      : 'bottom',
            title               : t.data('confirm-title'),
            content             : t.data('confirm-question'),
            buttons             : {
                confirm: {
                    keys : ['enter'],
                    text : t.data('confirm-answer-y'),
                    action : function () {
                        blkToHide.addClass(cls.action.hidden);
                        //region Показ кнопки настроек фильтра
                        blockContain.find(filter.link.delete).removeClass(cls.action.hidden);
                        //endregion
                        if(t.closest(sortable.form).length > 0) {
                            saveFilterToLocalStorage();
                        } else if(t.closest(ids.tb).length > 0) {
                            saveListToLocalStorage();
                        }
                    }
                },
                cancel: {
                    keys : ['esc'],
                    text : t.data('confirm-answer-n'),
                    action : function () {}
                }
            }
        });
    });
    //endregion
    //region Если имеются настройки в локальном хранилище
    //region Настройки фильтра в локальном хранилище
    if(localStorage.stdata) {
        objLS = $.parseJSON(localStorage.stdata);
    }
    if(objLS.default == 'N') {
        stData = {
            default         : objLS.default,
            columnObj       : objLS.columnObj,
            hiddenObj       : objLS.hiddenObj,
            posSplitVert    : objLS.posSplitVert,
            posSplitHor     : objLS.posSplitHor
        };
        //region Показ настроек фильтра
        $(sortable.form).find(filter.link.delete).removeClass(cls.action.hidden);
        //endregion
        if(countOfOject(stData.hiddenObj) > 0) {
            $.each(stData.hiddenObj, function(ihide, yhide){
                $(sortable.form).find(sortable.item + '[data-portlet="' + yhide + '"]').addClass(cls.action.hidden);
            });
        }
    }
    //endregion
    //region Настройки списка ЕУ в таблице из локального хранилища
    if(localStorage.listdata) {
        objTab = $.parseJSON(localStorage.listdata);
    }
    if(objTab.default == 'N') {
        //region Показ настроек фильтра
        $(ids.tb).find(filter.link.delete).removeClass(cls.action.hidden);
        //endregion
        if(countOfOject(objTab.hiddenObj) > 0) {
            $.each(objTab.hiddenObj, function(ihide, yhide){
                $(ids.tb).find('.' + yhide).addClass(cls.action.hidden);
            });
        }
        setTimeout(setSortColumns, 300);
    }
    //endregion
    //endregion
    //region Функция сортировки столбцов таблицы по данным из локадбного хранилища
    /**
     * Функция сортировки столбцов таблицы по данным из локадбного хранилища
     */
    function setSortColumns() {
        var prevColumn = objTab.columnObj[0],
            listColumns = $(ids.tb).find(cls.list.l);
        $.each(objTab.columnObj, function(icol, ycol) {
            if(icol != 0) {
                listColumns.find('.' + ycol).appendTo(listColumns);

            }
            prevColumn = ycol;
        });
    }
    //endregion
    //region Разбивка рабочей области со списком ЕУ реестра и фильтра
    $(ids.hl).split({
        orientation : 'horizontal',
        limit       : 100,
        position    : stData.posSplitHor + '%',
        onDragEnd   : function() {
            if($(sortable.form).find(filter.link.delete).hasClass(cls.action.hidden)) {
                $(sortable.form).find(filter.link.delete).removeClass(cls.action.hidden);
            }
            saveFilterToLocalStorage();
        }
    });
    //endregion
    //region Сохранение настроек фильтра
    $(document).on('click', filter.link.save, function(e){
        e.preventDefault();
        var t = $(this);
        if(t.closest(sortable.form).length > 0) {
            saveFilterToLocalStorage();
        } else if(t.closest(ids.tb).length > 0) {
            saveListToLocalStorage();
        }
    });
    //endregion
    //region Сброс настроект фильтра / таблицы с ЕУ
    $(document).on('click', filter.link.delete, function(e){
        e.preventDefault();
        var t = $(this);
        if(t.closest(ids.tb).length > 0) {
            localStorage.removeItem('listdata');
        } else if(t.closest(sortable.form).length > 0) {
            localStorage.removeItem('stdata');
        }
        location.reload();
    });
    //endregion
    //region Функция разбивки контейнера по столбцам с учетом количества внутренних блоков
    /**
     * Функция разбивки контейнера по столбцам с учетом количества блоков внутренних
     * @param changeWidth - boolean, если true то функция запущена при изменении размера экрана, если false то при загрузке страницы
     */
    function setColumn(changeWidth) {
        var form = $(sortable.form),
            wForm = form.width(),
            wColumn = form.find(sortable.column).outerWidth(true),
            cColumn = Math.floor(wForm / wColumn),
            tempCls = 'temp',
            nColumn = 1;

        if(stData.default == 'N' && !changeWidth) {
            cColumn = countOfOject(stData.columnObj);
        }

        if(form.find(sortable.column).length != cColumn) {
            form.append('<div class="' + tempCls + '"></div>');
            var tempBlk = form.find('.' + tempCls),
                iColumn = 1;
            tempBlk.append(form.find(sortable.item));
            form.find(sortable.column).remove();

            while(iColumn <= cColumn) {
                form.append('<div class="' + sortable.columns + ' form__row__col _size4 column-' + iColumn + '"></div>');
                if(stData.default == 'N' && !changeWidth) {
                    $.each(stData.columnObj['column-' + iColumn], function(i, y) {
                        form.find('.column-' + iColumn).append(form.find('[data-portlet="' + y + '"]'));
                    });
                }
                iColumn = iColumn + 1;
            }
            if(stData.default != 'N' || changeWidth) {
                form.find(sortable.item).each(function(i, y){
                    var $this = $(y),
                        blkTo = form.find('.column-' + nColumn);
                    if(nColumn < cColumn) {
                        nColumn = nColumn + 1;
                    } else {
                        nColumn = 1;
                    }
                    blkTo.append($this);
                });
            }
            form.find('.' + tempCls).remove();
        } else if(stData.default == 'N' && !changeWidth) {
            form.find(sortable.column).each(function(icol, ycol) {
                iColumn = icol + 1;
                $.each(stData.columnObj['column-' + iColumn], function(i, y) {
                    form.find('.column-' + iColumn).append(form.find('[data-portlet="' + y + '"]'));
                });
            });
        }
        startSortable();
    }
    //endregion
    //region Функция запуска sortable для блоков в колонках для фильтра
    function startSortable() {
        $(sortable.column).sortable({
            connectWith : sortable.column,
            handle      : sortable.iheader,
            cancel      : sortable.icancel,
            placeholder : sortable.iplaceh,
            stop        : function() {
                if($(sortable.form).find(filter.link.delete).hasClass(cls.action.hidden)) {
                    $(sortable.form).find(filter.link.delete).removeClass(cls.action.hidden);
                }
                saveFilterToLocalStorage();
            }
        });
        $(sortable.item).addClass(sortable.iadd)
            .find(sortable.iheader).addClass(sortable.ihadd);
    }
    //endregion
    //region Функция сохранения данных фильтра в локальное хранилище
    /**
     * Функция сохранения данных фильтра в локальное хранилище
     */
    function saveFilterToLocalStorage() {
        stData.default = 'N';
        stData.posSplitVert = Math.floor($(ids.v).children(cls.split.left).width() / $(ids.v).width() * 100);
        stData.posSplitHor = Math.floor($(ids.hl).children(cls.split.top).height() / $(ids.hl).height() * 100);

        $(sortable.form).find(sortable.column).each(function(icol, ycol) {
            var iColumn = icol + 1,
                clsColumn = 'column-' + iColumn;
            stData.columnObj[clsColumn] = {};
            $(ycol).find(sortable.item).each(function(iport, yport) {
                stData.columnObj[clsColumn][iport] = $(yport).data('portlet');
            });
        });
        $(sortable.form).find(sortable.item + '.' + cls.action.hidden).each(function(ihide, yhide) {
            stData.hiddenObj[ihide] = $(yhide).data('portlet');
        });
        localStorage.setItem("stdata", JSON.stringify(stData));
    }
    //endregion
    //region Функция сохранения порядка показа столбцов таблицы со списком ЕУ в локальное хранилище
    /**
     * Функция сохранения порядка показа столбцов таблицы со списком ЕУ в локальное хранилище
     */
    function saveListToLocalStorage() {
        listData.default = 'N';
        var ihidden = 0;
        $(ids.lr).children('li').each(function(icol, ycol){
            var icolumn = $(ycol).data('column');
            listData.columnObj[icol] = icolumn;
            if($(ycol).hasClass(cls.action.hidden)) {
                listData.hiddenObj[ihidden] = icolumn;
                ihidden = ihidden + 1;
            }
        });
        localStorage.setItem("listdata", JSON.stringify(listData));
    }
    //endregion
    //region Отправка формы без пустых параметров
    checkSendForm('#form-filter');
    //endregion
    //region Показ кнопки для очистки содержимого в input
    hoverInput('.portlet-content');
    //endregion
    //region Функция сброса всех выделенных элементов в таблице
    /**
     * Функция сброса всех выделенных элементов в таблице
     */
    function resetCheckedUnit() {
        var listCheck = $(ids.tb).find(cls.list.licheck);
        listCheck.find('.' + cls.action.active).each(function(itr, ytr){
            $(ytr).removeClass(cls.action.active);
            $(ytr).find(cls.form.checkbox.unit).attr('checked', false);
        });
        $(ids.tb).find('.' + cls.action.active).removeClass(cls.action.active);
    }
    //endregion
    //region Подключение функции переноса столбцов в таблице
    $(ids.lr).sortable({
        placeholder : 'portlet-placeholder',
        items       : 'li:not(.ui-state-disabled)',
        stop        : function() {
            if($(ids.tb).find(filter.link.delete).hasClass(cls.action.hidden)) {
                $(ids.tb).find(filter.link.delete).removeClass(cls.action.hidden);
            }
            saveListToLocalStorage();
        }
    });
    //endregion
});
//region Функция возвращает объект содержащий ID ЕУ и ID инфоблока из строки "UNIT_ID-#ID#-IBLOCK_ID-#IBLOCK_ID#"
/**
 * Функция возвращает объект содержащий ID ЕУ и ID инфоблока из строки "UNIT_ID-#ID#-IBLOCK_ID-#IBLOCK_ID#"
 * @param value - строка вида "UNIT_ID-#ID#-IBLOCK_ID-#IBLOCK_ID#"
 * @returns {{unit: string, iblock: number}}
 */
function getObjUnit(value) {
    var prefixUnitID = 'UNIT_ID-',
        prefixIblockID = '-IBLOCK_ID-',
        nUnitData = value.replace(prefixUnitID, ''),
        indexIblock = nUnitData.indexOf(prefixIblockID),
        objResult = {
            unit    : nUnitData.substring(0, indexIblock),
            iblock  : 0
        };
    objResult.iblock = nUnitData.replace(objResult.unit, '');
    objResult.iblock = objResult.iblock.replace(prefixIblockID, '');
    return objResult;
}
//endregion