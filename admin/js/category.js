/**
* Модуль для  раздела "Категории".
*/
var category = (function() {
    return {
        wysiwyg: null, // HTML редактор для   редактирования страниц
        supportCkeditor: null,//??? не используется
        supportCkeditorSeo: null,//??? не используется
        clickedId: [],//??? не используется
        checkBlockInterval: null,//??? не используется
        
        edata: edate,
        odata: '',
        
        
        
        init: function() {
            // Инициализация обработчиков
            category.initEvents();
            
            // восстанавливаем массив открытых категорий из localStorage
            if(LS = JSON.parse(localStorage.getItem('activeTab'+edate.mod))){
                $.each(LS,function(index,value){
                    category.hideShowRows($(value), true);
                });
            }
            //localStorage.removeItem('activeTab'+edate.mod);
            category.sortableInit();
        },
        
        /**
         * Инициализирует обработчики для кнопок и элементов раздела.
        */
        initEvents: function() {
            // функции нужные в модуле категории
            
            // Показать/скрыть категории по клику
            $('#main').on("click",".ds_catTL", function () {
                // Удалить существующие специальные строки
                $('tr.row-drop').remove();
                category.hideShowRows($(this));
            });
            
            // Показать trRow подстроку редактирование категории при клике на строку tr
            $('body').on('click', 'tbody td', function(event){
                // Не обрабатывайте, если был нажат какой-либо другой щелчок
                if (event.target !== this){return;}
                category.rowDrop(this);
            });
            
            // Показать trRow подстроку редактирование категории при клике на кнопку редактирования
            /*$('body').on('click', '.ds_editSt', function(event){
                // Не обрабатывайте, если был нажат какой-либо другой щелчок
                if (event.target !== this){return;}
                category.rowDrop($(this).closest('td'));
            });*/
            
            // Удалить подстроку и модальное окно edit, add категории
            $(document).on('click', '.ds_closeSt', function(){
                $(this).closest('tr.row-drop').remove();
                $(this).closeModal();
            });
            
            // Показать/скрыть все вложенные категории
            $('#ds_catAllsh').on("click", function () {
                let tr = $('#dsList>tr'),
                    tri = tr.find('td.name a.icon-size2');
                localStorage.removeItem('activeTab'+edate.mod);// Очищаем localStorage
                if ($(this).hasClass('icon-squared-plus')) {// развернуть все
                    // Разворачиваем все
                    tr.show();
                    tri.removeClass('icon-squared-plus').addClass('icon-squared-minus');
                    // Записываем в localStorage все записи
                    let ids = [];
                    $(tri).each(function(i,e){
                        ids.push('#ds_catTL'+ $(e).data('id'));
                    });
                    localStorage.setItem('activeTab'+edate.mod, JSON.stringify(ids));
                }else{// свернуть все
                    tri.removeClass('icon-squared-minus').addClass('icon-squared-plus');
                    tr.not('[data-parent="0"]').hide();
                }
                $(this).toggleClass('icon-squared-plus icon-squared-minus');
            });
            
            
            
            
            
            // Добавить или изменить категорию
            $(document).on("submit","#ds_eaddSt", function (e) {
                e.preventDefault();
                let f = $(this).closest('form'),
                    nowdata = f.serialize(),
                    ti = f.find('#title');
                if(!ti.val()) {
                    ti.focus();// Если нет title ставим focus
                }else{
                    $('.modal').closeModal();
                    $('tr.row-drop').remove();
                    if(category.odata != nowdata) {
                        category.saveCat($(this).serialize());
                    }
                }
            });
            
            
            
            
            
            
            
            
            /*
            
            // Удалить категорию
            $('#dsList .ds_delSt').data('confirm-options', {
                onConfirm: function() {
                    // Удалить существующие специальные строки
                    $('tr.row-drop').remove();
                    
                    //$('#dsList input[type="checkbox"][name*="check"]').attr('checked', false).change();
                    
                    e = $(this).closest('tr');
                    
                    //e.find('input[type="checkbox"][name*="check"]').attr('checked', true);
                    
                    
                    //check[]
                    
                    category.saveCat({'check[]': e.data('id'), 'a':'delete'});
                    
				
				// Remove element
				//$(this).closest('li').fadeAndRemove();

				// Prevent default link behavior
				return false;
			}
		    });
		    */
		    
		    
		    
		    /* Действия */
		    $('#main').on("click","#dsRun", function (event) {
                if ($('#dsList tr input[type="checkbox"][name*="check"]:checked').length>0) {
                        let v = '',
                            o = $('#dsRunSel').val();
                    if ($.inArray(o, ['move', 'delete']) != -1) {
                        event.preventDefault();
                        $(this).confirm({
                            onConfirm:	function(){
                                if (o == 'move') {
                                    v = $('#dsMove').val();
                                }
                                category.runOperation(o,v);
                                return false;
                            }
                        });
                    }else{
                        category.runOperation(o);
                    }
                }
            });
            
            
            // Показать пункты для перемещения
            $('#dsRunSel').change(function(e){ 
                if($(this).val() == 'move') {
                    $('#dsRun').before('<select id="dsMove" class="select expandable-list white-gradient glossy small-margin-right">'+category.edata.select+'</select>');
                }else{
                    $('#dsMove').remove();
                }
            }); 
            
            
            // Вызов модального окна при нажатии на пункт добавления подкатегории.
           /* $('#main').on("click",".ds_addSub", function () {
                category.catForm();
                $('select[name=id_sec] option[value="' + $(this).closest('tr').data('id') + '"]').prop('selected', true).change();
            });*/
            
            
            
            
            // Обработка нажатий на кнопки Действий
            $('#main').on('click', '.ds_action a', function() {
                let a = $(this).data('a'),
                    v = $(this).hasClass('green') ? 1 : 0;
                if (a) {category.actionCat($(this),a,v);}
            });
            
            
            
            
            
            
            
		    
		    
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
            
            
        },// END initEvents функции нужные в модуле категории
        
        // ФУНКЦИИ НИЖЕ
        
        
        
        
        
        
        /**
        * Выполняет выбранную операцию со всеми отмеченными категориями
        * o - тип операции.
        */
        runOperation: function(o,v=false) {
            let c_id = [];
            $('#dsList input[name=check').each(function() {
                if($(this).prop('checked')) {
                    c_id.push($(this).parents('tr').data('id'));
                }
            });
            category.saveCat({'check': c_id, 'operation': o, 'option':v});
        },
        
        
        
        
        
        
        
        
        
        
        
        
        
        /**
         * появление подстроки trRow для редактирования категории
         * t - идентификатор
        */
        rowDrop: function(t) {
            var tr = $(t).parent(),
                row = tr.next('.row-drop'),
                rows;
            // Если нажать на специальную строку
            if (tr.hasClass('row-drop')){return;}
            // Если уже существует специальная строка
            if (row.length > 0){
                // Remove row
                row.remove();
                return;
            }
            // Удалить существующие специальные строки
            rows = tr.siblings('.row-drop');
            if (rows.length > 0){
                // Remove rows
                rows.remove();
            }
            // Add row
            category.catForm(tr);
        },
        
        
        /**
         * Делает категорию  видимой/невидимой в меню
         * oneId - идентификатор первой категории
         * 
         * twoId - идентификатор второй категории
        */
        invisibleCat: function(id, invisible) {
            
        },
        
        /**
         * Обработка кнопок Действия
         * option - текущее действие
        */
        actionCat: function(e, option, v=false) {
            switch (option) {
                case 'edit': // Показать trRow подстроку редактирование категории при клике на кнопку редактирования
                    category.rowDrop(e.closest('td'));
                break;
                case 'addSub': // Вызов модального окна при нажатии на пункт добавления подкатегории.
                    category.catForm();
                    $('select[name=id_sec] option[value="' + e.closest('tr').data('id') + '"]').prop('selected', true).change();
                break;
                case 'visible': // 
                    var o = v ? 'disable' : 'enable',
                        id = e.closest('tr').data('id');
                break;
                case 'view': // 
                    var o = v ? 'view_off' : 'view_on',
                        id = e.closest('tr').data('id');
                break;
                case 'del': // Удалить категорию
                    e.data('confirm-options', {onConfirm: function() {
                        $('tr.row-drop').remove();// Удалить существующие специальные строки
                        let id = e.closest('tr').data('id');
                        if (id) {category.saveCat({'check[]': id, 'operation': 'delete'});}
                        return false;
                    }});
                break;
                default: return false;
            }
            if (o && id) {
                category.saveCat({'check[]': id, 'operation': o});
                return false;
            }
        },
        
        
        
        
        
        

        
        
        
        
        
        
        
        
        
        
        
        
        /**
         *  Форма add/edit
        */
        catForm: function(tr=false) {
            var col = 1,
                //ac = 'add',
                id = 0,
                //pos = 0,
                name = '',
                eadd = edate.add,
                cl = '',
                cl2 = '';
            if (tr) {
                var parent = tr.data('parent'),
                    access =  tr.data('access').toString().split(',');
                col = tr.children().length,
                //ac = 'edit',
                id = tr.data('id'),
                //pos = tr.data('pos'),
                name = tr.find('td.name span').text(),
                eadd = edate.editing,
                cl = 'row-drop',
                cl2 = ' white';
            }
            
            
            
            
            
            
            
            /*var content = '<tr class="'+cl+'"><td colspan="'+col+'">'+
                                '<form action="category.php?mod='+edate.mod+'&ac='+ac+'" method="POST">'+
                                    '<fieldset class="fieldset margin-top'+cl2+'"><legend class="legend">'+eadd+'</legend>'+
                                        '<p class="button-height block-label"><label for="input" class="label"><small>Additional information</small>'+edate.title+'</label><input type="text" name="title" id="input" class="input full-width" value="'+name+'"></p>'+
                                        '<p class="button-height block-label"><label for="input" class="label"><small>Additional information</small>'+edate.parent+'</label><select name="id_sec" id="ecatSel" class="select">'+category.edata.select+'</select></p>'+
                                        '<p>'+edate.access+'</p>'+
                                        '<table class=""><tr>'+edate.groups+'</tr></table>'+
                                    '</fieldset>'+
                                    '<div class="float-right margin-bottom">'+
                                        '<button type="submit" name="send" class="button glossy mid-margin-right">'+edate.save+
                                            '<span class="button-icon green-gradient right-side"><span class="icon-save"></span></span>'+
                                        '</button>'+
                                        '<a href="javascript:void(0)" class="ds_closeSt button glossy margin-right">'+edate.close+'<span class="button-icon red-gradient right-side"><span class="icon-cross"></span></span></a>'+
                                    '</div>'+
                                '</form></td>'+
                            '</tr>';*/
                            
                            
                            
                            
            var content = '<tr class="'+cl+'"><td colspan="'+col+'">'+
                                '<form id="ds_eaddSt">'+
                                    '<input name="id" type="hidden" value="'+id+'">'+
                                    '<fieldset class="fieldset margin-top'+cl2+'"><legend class="legend">'+eadd+'</legend>'+
                                        '<p class="button-height block-label required"><label for="title" class="label"><small>Additional information</small>'+edate.title+'</label><input type="text" name="title" id="title" class="input full-width" value="'+name+'"></p>'+
                                        '<p class="button-height block-label"><label for="ecatSel" class="label"><small>Additional information</small>'+edate.parent+'</label><select name="id_sec" id="ecatSel" class="select">'+category.edata.select+'</select></p>'+
                                        '<p>'+edate.access+'</p>'+
                                        '<table class=""><tr>'+edate.groups+'</tr></table>'+
                                    '</fieldset>'+
                                    '<div class="float-right margin-bottom">'+
                                        
                                        
                                        
                                        '<button type="submit" class="button glossy mid-margin-right">'+edate.save+
                                            '<span class="button-icon green-gradient right-side"><span class="icon-save"></span></span>'+
                                        '</button>'+
                                        
                                        
                                        
                                        '<a href="javascript:void(0)" class="ds_closeSt button glossy margin-right">'+edate.close+'<span class="button-icon red-gradient right-side"><span class="icon-cross"></span></span></a>'+
                                    '</div>'+
                                '</form></td>'+
                            '</tr>';
            if (tr) {
                $(content).insertAfter(tr);
                $('#ecatSel option[value='+id+']').remove();
                $('#ecatSel option[value='+parent+']').prop('selected', true).change();
                $.each(access, function(x, v) {
                    tr.next('tr').find('#ac_'+v+'').prop('checked', false).change();
                });
            }else{
                $.modal({
                    title: eadd,
                    content: content,
                    beforeContent: '<table>',
                    afterContent: '</table>',
                    draggable: false,
                    resizable: false,
                    closeOnBlur: true,
                    buttons:  false
                })
                $('#ds_eaddSt').find('#title').focus();
            }
            category.odata = $('form').serialize();
        },
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        /**
         * Сохранение изменений в модальном окне категории.
         * Используется и для сохранения редактированных данных и для сохранения нового продукта.
         * id - идентификатор продукта, может отсутствовать если производится добавление нового товара.
        */
        saveCategory: function(id) {
            //alert('saveCategory');
            //e.preventDefault();
        },
        
        /**
         * Получает данные о категории с сервера и заполняет ими поля в окне.
        */
        editCategory: function(id) {
            
        },
        
        /**
         * Удаляет категорию из БД сайта
        */
        deleteCategory: function(id) {
            
        },
        
        sortableInit: function() { 
            $('.list').has('.list-sort').sortable({
                handle: '.list-sort',
                placeholder: 'ui-state-highlight',
                
                start: function(event, ui) {
                    $('tr.row-drop').remove();
                    // определяем позицию чтобы вдальнейшем узнать - изменил ли позицию
                    ui.item.startIndex = ui.item.index();
                    ui.item.startPos = $('table #dsList tr:visible').index($('#ds_catTr'+ui.item.prevAll(":visible:first").data('id')))+1;
                },
                
                sort: function(e,u) {
                    $('#dsList tr td.name>span').removeClass('icon-right icon-green');
                    idh = $('#dsList tr td.name>span:hover').closest('tr').data("id");
                    $('#ds_catTr'+idh+' td.name>span').addClass('icon-right icon-green');
                    u.item.data('trhover', idh); // id над какой завис перетаскиваемый tr
                    u.item.data('trnext', $('#dsList tr:hover').next().data("id")); // id сдудующего tr после перемещения
                    
                    //var Y = e.pageY; // положения по оси Y
                    //$('.ui-sortable-helper').offset({ top: (Y-10)});
                },
                
                //items: 'tr:not(.ui-state-disabled)',
                items: 'tr',
                helper: fixHelperCategory,
                
                stop: function(event, ui) {
                    let sarr = {},
                        id = ui.item.data('id'), // id сортируемого элемента
                        trhover = ui.item.data('trhover'), // если не равно null , значит переносит во внутрь категории idh
                        trnext = ui.item.data('trnext');
                        sarr['id'] = id;
                    if (trhover) {// переместили в новую группу
                        sarr['parent'] = $('#ds_catTr'+trhover).data('id');
                    }else{// определяем под какую категорию положили
                        // Проверка было ли перемещение
                        if (ui.item.startIndex != ui.item.index()) {
                            if (ui.item.startPos != $('table #dsList tr:visible').index($('#ds_catTr'+id))) {
                                //положили на первую позицию всего списка
                                let prevcat = $(ui.item).prevAll(":visible:first"),
                                    parId = prevcat.data('parent') ? prevcat.data('parent') : 0;
                                // изменяем атрибут data-parent у перетаскиваемого элемента
                                ui.item.attr('data-parent', parId);
                                sarr['parent'] = parId;
                                sarr['sort'] = $('table #dsList tr[data-parent="'+parId+'"]').map(function(){ // вызываем функцию на каждом элементе
                                    return $(this).data('id'); // возвращаем значение текущего элемента
                                })
                                .get() // преобразуем объект jQuery в обычный массив
                                .join(','); // преобразуем и объединяем все элементы массива в одно строковое значение
                            }
                        }
                    }
                    if (Object.keys(sarr).length > 1) {
                        category.saveCat({'sort': sarr});
                    }
                    $("tbody.list tr").css('visibility','visible');
                }
                
            }).disableSelection();
        },
        
        // сохранение
        saveCat: function(data) {
            $.ajax({
                method: 'post',
                dataType: 'json',
                data: data,
                success: function(data){/* функция которая будет выполнена после успешного запроса.  */
                    //console.log(data);
                    if(data.e) {category.edata = data.e;}
                    if(data.tr) {
                        $('tbody.list').html(data.tr);
                        LS = JSON.parse(localStorage.getItem('activeTab'+edate.mod));
                        if(LS){
                            $.each(LS,function(index,value){
                                category.hideShowRows($(value), true);
                            });
                        }
                    }
                    if(data.msg) {
                        notify(data.msg,'', {showCloseOnHover: false});
                    }
                    if(data.err) {
                        notify('Error',data.err, {showCloseOnHover: false});
                    }
                }
            });
            return false;
        },
        
        
        
        
        
        
        
        
        
        
        
        
        
        // скрывает спрятанные пункты
        hideShowRows: function(ts, aj=false) {
            let LS = JSON.parse(localStorage.getItem('activeTab'+edate.mod)) || [],
                i = ts.data('id'), // show id нажатой кнопки
                t = ts.closest('tr'),
                p = t.data('level'), // от какого
                n = t.next(),
                l = n.data('level'); // до какого
            if (ts.hasClass('icon-squared-minus')){
                ts.removeClass('icon-squared-minus').addClass('icon-squared-plus');
                // Удаление из localStorage
                let m = LS.indexOf('#ds_catTL'+ $(ts).data('id'));
                if (m !== -1) {LS.splice(m, 1);}
                localStorage.setItem('activeTab'+edate.mod, JSON.stringify(LS));
                while (l > p) {
                    n.hide().find('td.name>div>a.icon-squared-minus').toggleClass('icon-squared-plus icon-squared-minus');
                    // Удаление из localStorage
                    m = LS.indexOf('#ds_catTL'+ n.data('id'));
                    if (m !== -1) {LS.splice(m, 1);}
                    localStorage.setItem('activeTab'+edate.mod, JSON.stringify(LS));
                    n = n.next();
                    l = n.data('level');
                }
            } else {
                ts.removeClass('icon-squared-plus').addClass('icon-squared-minus');
                $('.ds_catTr'+i).show();// show
                if(!aj){
                    // Сохранение в localStorage
                    LS.push('#ds_catTL'+ $(ts).data('id'));
                    localStorage.setItem('activeTab'+edate.mod, JSON.stringify(LS));
                }
            }
            let iconSquared = $('#dsList>tr>td.name a');
            if (!iconSquared.filter('.icon-squared-minus').length) {
                // Если нет icon-squared-minus стераем все из localStorage, т.к. там может быть лишнее
                localStorage.removeItem('activeTab'+edate.mod);
            }else{
                // Если нет ни одного .icon-squared-plus
                if(!iconSquared.filter('.icon-squared-plus').length) {
                    $('#ds_catAllsh').removeClass('icon-squared-plus').addClass('icon-squared-minus');
                }else{
                    $('#ds_catAllsh').removeClass('icon-squared-minus').addClass('icon-squared-plus');
                }
            }
        },
        
        
        
        
        
	};
})();

var fixHelperCategory = function(e, ui) {
    trStyle = "color:#1585cf!important;background-color:#fff!important;";
    // берем id текущей строки
    let id = $(ui).data('id');
    // достаем уровень вложенности данной строки
    let level = $(ui).data('level');
        level++;
    
    // берем порядковый номер текущей строки
    // thisSortNumber = $(ui).data('sort');
    $('tbody.list tr').each(function(index) {
        if($(this).data('id') == id) {
            thisSortNumber = index;
            return false;
        }
    }); 
    // фикс скрола
    //$('.section-category .table-wrapper').css('overflow', 'visible');
    // поиск ширины для жесткой записи, чтобы не разебывалось
    width = $('.simple-table').width();
    width *= 0.9;
    uiq = '<div style="width:'+width+'px;position:fixed;margin:20px 0 0 65px;"><table style="width:100%;"><tr style="'+trStyle+'">'+$(ui).html()+'</tr>';
    group = 'ds_catTr'+$(ui).data('parent');
    let trCount = $('tbody.list tr').length;
    for(i = thisSortNumber+1; i < trCount; i++) {
        if(($('tbody.list tr:eq('+i+')').hasClass(group)) || (($('tbody.list tr:eq('+i+')').data('level') < level))) {
            break;
            }else{
            if(($('tbody.list tr:eq('+i+')').data('level') >= level)) {
                uiq += '<tr style="'+trStyle+'display:'+$('tbody.list tr:eq('+i+')').css('display')+'">'+$('tbody.list tr:eq('+i+')').html()+'</tr>';
                $('tbody.list tr:eq('+i+')').css('visibility','hidden');
            }
        }
    }
    uiq += '</table></div>';
    return uiq;
};