/**
* @project    DarsiPro CMS
* @package    Darsi front-end library
* @url        https://darsi.pro
*/

if (typeof WWW_ROOT === "undefined") WWW_ROOT = '';
if (typeof LANG === "undefined") LANG = 'eng';

// DINAMIC TIME

UsAgentLang = (navigator.language || navigator.systemLanguage || navigator.userLanguage).substr(0, 2).toLowerCase();

Lang = {}
// Выбираем нужную локализацию.
switch (UsAgentLang) {
    case 'ru' :
        Lang.Now = 'только что';
        Lang.Ago = 'назад';
        Lang.After = 'через';
        Lang.NameMonths = ['Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августa', 'Сентября', 'Октября', 'Ноября', 'Декабря'];
        Lang.NameMonthsMin = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
        Lang.NameWeekdays = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
        Lang.NameWeekdaysMin = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        Lang.DimensionTime = {
                'n' : ['месяцев', 'месяц', 'месяца', 'месяц'],
                'j' : ['дней', 'день', 'дня'],
                'G' : ['часов', 'час', 'часа'],
                'i' : ['минут', 'минуту', 'минуты'],
                's' : ['секунд', 'секунду', 'секунды']
        }
        break;
    default:
        Lang.Now = 'now';
        Lang.Ago = 'ago';
        Lang.After = 'after';
        Lang.NameMonths = ['January', 'February', 'Marth', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        Lang.NameMonthsMin = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        Lang.NameWeekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        Lang.NameWeekdaysMin = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        Lang.DimensionTime = {
                'n' : ['months', 'month', 'months'],
                'j' : ['days', 'day', 'days'],
                'G' : ['hours', 'h', 'hours'],
                'i' : ['minutes', 'minute', 'minutes'],
                's' : ['seconds', 'second', 'seconds']
        }
        break;
}

// Выводит элемент даты с нужной размерностью, и в нужном склонении
function NiceDate(chislo, type) {
    var n;
    // Узнаем нужное склонение для временной единицы
    if (chislo >= 5 && chislo <= 20)
        n = 0;
    else if (chislo == 1 || chislo % 10 == 1)
        n = 1;
    else if ((chislo <= 4 && chislo >= 1) || (chislo % 10 <= 4 && chislo % 10 >= 1))
        n = 2;
    else
        n = 0;
        

    return chislo + ' ' + Lang.DimensionTime[type][n];

}

// Выводит двузначное число с ведущим нулем
function ZeroPlus(x) {
    if (x < 10)
        x = '0' + x;
    return x;
}
// Переводит в 12 часовой формат
function ToAM(x) {
    if (x > 12) 
        x -= 12;
    return x;
}

// Аналог функции date() из PHP
function ParseDateFormat(format, Time) {
    var DateInFormat = '';
    if ((format === undefined)||(format.length === 0))
        return;
    for (var i = 0; i < format.length; i++) {
        switch (format[i]) {
            // Часы
            // 12 часовой
            case 'g' : DateInFormat += ToAM(Time.getUTCHours()); break; // без ведущего нуля
            case 'h' : DateInFormat += ZeroPlus(ToAM(Time.getUTCHours())); break; // C ведущим нулем
            // 24 часовой
            case 'G' : DateInFormat += Time.getUTCHours(); break; // без ведущего нуля
            case 'H' : DateInFormat += ZeroPlus(Time.getUTCHours()); break; // с ведущим нулём
            // Годы
            case 'Y' : DateInFormat += Time.getUTCFullYear(); break; // Четыре цифры
            case 'y' : DateInFormat += String(Time.getUTCFullYear()).substr(2); break; // Две цифры
            // Месяцы
            case 'm' : DateInFormat += ZeroPlus(Time.getUTCMonth() + 1); break; //Порядковый номер месяца с ведущим нулём
            case 'n' : DateInFormat += Time.getUTCMonth() + 1; break; // Порядковый номер месяца без ведущего нуля
            case 'F' : DateInFormat += Lang.NameMonths[Time.getUTCMonth()]; break; // Полное наименование месяца
            case 'M' : DateInFormat += Lang.NameMonthsMin[Time.getUTCMonth()]; break; // Сокращенное наименование месяца
            // Дни
            case 'd' : DateInFormat += ZeroPlus(Time.getUTCDate()); break;// День месяца
            case 'j' : DateInFormat += Time.getUTCDate(); break; // День месяца без в.н.
            // Дни недели
            case 'N' : DateInFormat += Time.getUTCDay() + 1; break; // Порядковый номер дня недели
            case 'D' : DateInFormat += Lang.NameWeekdaysMin[Time.getUTCDay()]; break; // Текстовое, сокращенное, представление дня недели
            case 'L' : DateInFormat += Lang.NameWeekdays[Time.getUTCDay()]; break; // Полное наименование дня недели
            // Минуты
            case 'i' : DateInFormat += ZeroPlus(Time.getUTCMinutes()); break; // с ведущим нулём
            // Секунды
            case 's' : DateInFormat += ZeroPlus(Time.getUTCSeconds()); break; // с ведущим нулём
            
            default : DateInFormat += format[i]; break;
        }
    }
    
    return DateInFormat;
}

// Выводит относительное время. А так же если check = true то просто делает проверку, относительную ли дату выводить
function OffsetDate(Time, Now, check) {
    
    if (check) {
        if (((new Date(Now - Time)) < (new Date(1970, 1))) || Time > Now)
            return true;
        else
            return false;
    }

    if (Time > Now)
        var OffsetTime = new Date(Time - Now);
    else
        var OffsetTime = new Date(Now - Time);
    
    var s = OffsetTime.getUTCSeconds(), // Секунды
         i = OffsetTime.getUTCMinutes(), // Минуты
         G = OffsetTime.getUTCHours(), // Часы
         j = OffsetTime.getUTCDate()-1, // Дни
         n = OffsetTime.getUTCMonth(), // Месяц
         output = '';
    
    // Если время пошло на месяцы то выводим только месяцы и дни(если не ноль)
    if (n) {
        output += NiceDate(n, 'n') + ' ';
        if (j) output += NiceDate(j, 'j') + ' ';
    // Если время пошло на дни то выводим только дни
    } else if (j) {
        output += NiceDate(j, 'j') + ' ';
    // Если время пошло на часы то выводим только часы и минуты(если не ноль)
    } else if (G) {
        output += NiceDate(G, 'G') + ' ';
    // Если время пошло на минуты то выводим только минуты и секунды(если не ноль)
    } else if (i) {
        output += NiceDate(i, 'i') + ' ';
    // Если времени прошло менее минуты то выводим секунды
    } else {
        output += Lang.Now;
        return output;
    }

    if (Time > Now)
        return Lang.After + '  ' + output;
    else
        return output + '  ' + Lang.Ago;

}

// Выводит дату в нужном формате
function FormatTime(el) {
    
    var format = el.data('type'),
        stime = Date.parse(el.attr('datetime')),
        Now = new Date(), // Объект текущей даты
        Time = new Date(stime), // Обьект указанного времени
        f = OffsetDate(Time, Now, true); // Проверка на тип выводимого времени(относительный или дата)
        
    // Выводим относительное время
    if (f)
        el.html(OffsetDate(Time, Now, false));
    else {
        // Здесь просто выводим в нужном формате...
        // Если эту дату(не относительную) мы уже обработали, то не трогаем её.
        if (!el.data('compiled')) {
            el.html(ParseDateFormat(format, Time));
            el.attr('data-compiled', 'true');
        }
    }
}

// Ищем даты на странице и изменяем их под клиента
function UpdateTime() {
    var BlockTime = $('time');
    $.each(BlockTime, function () {
        FormatTime($(this));
    });
}
// Первоначальная обработка времени.
$(document).ready(function(){UpdateTime();});
// Динамическое обновление дат.
setInterval(UpdateTime, 10000);




// Открывает или закрывает блок, находящийся за кнопкой для его открытия.
// Принимает стандартный js обьект элемента кнопки.
function NextToggle(thus) {
    var button = $(thus),
        block = button.next();
    
    if (block.css('display') == 'none') {
        block.slideDown();
        button.addClass('open');
    } else {
        block.slideUp();
        button.removeClass('open');
    }
}



function checkForm() {

    var formErrors = false;

    if (document.getElementById("sendForm").mainText.value.length < 2) {
        formErrors = "Вы должны ввести текст сообщения";
    }

    if (formErrors) {
        alert(formErrors);
        return false;
    }

    return true;
}

function emoticon_wospaces(text) {
    var txtarea = document.getElementById("sendForm").mainText;
    if (txtarea.createTextRange && txtarea.caretPos) {
        var caretPos = txtarea.caretPos;
        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? caretPos.text + text + ' ' : caretPos.text + text;
        txtarea.focus();
    } else {
        txtarea.value  += text;
        txtarea.focus();
    }
}


// Catching selection
var selection = false; // Selection data
function catchSelection() {
    if (window.getSelection)
        selection = window.getSelection().toString();
    else if (document.getSelection)
        selection = document.getSelection();
    else if (document.selection)
        selection = document.selection.createRange().text;
}

// Putting selection to the post box
function quoteSelection(name) {
    if (selection) { 
        emoticon_wospaces('[quote="'+name+'"]' + selection + '[/quote]\n'); 
        selection = '';
        document.getElementById("sendForm").mainText.focus(); 
        return; 
    } else { 
        alert(l_no_text_selected);
        return; 
    } 
}
function in_array(value, array) {
    for(var i = 0; i < array.length; i++) {
        if(array[i] == value) return true;
    }
    return false;
}


/** Adding file field */
function addFileField(elementId) {
    var container = document.getElementById(elementId),
        fields = [],
        numbers = [],
        myclass = new RegExp('\\b'+'attachField'+'\\b'),
        elem = container.getElementsByTagName('*');
    
    for (var i = 0; i < elem.length; i++) {
        var classes = elem[i].className;
        if (myclass.test(classes)) {
            id = parseInt(elem[i].id.substring(6));
            numbers.push(id);
            fields.push(elem[i]);
        }
    }
    
    if (maxAttachedFiles == undefined) maxAttachedFiles = 5;
    
    var cntFields = fields.length + 1;
    if (cntFields <= maxAttachedFiles) {
        if (cntFields < 1) {
            cntFields = 1;
        }
        //Проверка, чтобы не загружать файл в одну и ту же форму и не забыть заполнить все формы.
        i = 1;
        while (in_array(i, numbers)) {
            i++;
        }

        var new_div = document.createElement('div');
        
        new_div.innerHTML = addFileField.html(i);
        
        container.appendChild(new_div);
    }
}
// HTML формы
addFileField.html = function(i) {
    return ' [' + i + '] <input type="file" id="attach' + i + '" name="attach' + i + '" class="attachField" onChange="getFile(' + i + ')" /><span id="attachMeta' + i + '"></span>';
}

/** Get and identific file */
// Main function
function getFile(n){
    var field_id = getFile.field(n),
        t = document.getElementById(field_id);
    if (t.value){
        ext = new Array('png','jpg','gif','jpeg','ico','bmp');
        var img = t.value.replace(/\\/g,'/');
        var pic = img.toLowerCase();
        var ok=0;
        for (i=0;i<ext.length;i++){
            m = pic.indexOf('.' + ext[i]);
            if (m != -1){
                ok=1;
                break;
            }
        }
        var meta_id = getFile.meta(n),
            d = document.getElementById(meta_id);
        if (d) {
            if (ok==1){
                var code='{IMAGE'+n+'}';
                document.getElementById(meta_id).innerHTML=getFile.html(code);
            } else {
                document.getElementById(meta_id).innerHTML='';
            }
        }
    } else {
        document.getElementById(field_id).innerHTML='';
    }
}
// HTML которое добавляется рядом с полем выбора картинки для вывода кода вставки в текст материала
// Принимает код для вставки, возвращает html.
getFile.html = function(code) {
    return ' <input type="text" onmouseover="this.select();" onmouseout="this.value +=\' \'; this.value = this.value.slice(0, -1);" readonly value="'+code+'" title="Вставьте этот код в любое место сообщения" size="'+(code.length)+'" style="font-family:monospace;color:#FF8E00;cursor:move;" />';
}
// Id поля для прикрепления файла.
// принимает номер формы, возвращает id формы
getFile.field = function(n) {
    return 'attach'+n;
}
// Id блока с кодом для вставки картинки в текст материала
// принимает номер формы, возвращает id блока
getFile.meta = function(n) {
    return 'attachMeta'+n;
}

/**
 *
 * aJax окошки. Wnd 3.0
 *
 *    close - показывать (true) или скрывать (false) кнопку закрытия окна. По-умолчанию true
 *    time - время в секундах, после которого окно закроется. По-умолчанию 0 (не закрывать)
 *    align - задает выравнивание контента. По-умолчанию left
 *    css - массив со стилями окна
 *
 *
 */
// Создаёт окно
function Wnd(name, title, content, params) {
    if (name && name.length > 0) {
        var i = $("#"+name).length
        if (i>0) {
            Wnd.content(name, content)
            Wnd.show(name)
            return false
        }
    }

    var props = $.extend({
        close: true,
        overlay: false,
        time: 0,
        align: 'left',
        css: {}
    }, params || {});

    $('body').append(Wnd.html(name,title,content))

    Wnd.param(name, props)
    Wnd.show(name)

}
// Template Window
Wnd.html = function (id,title,content) {
 return '<div id="'+id+'" class="drs-fwin" style="display: none"> \
            <div class="drag_window"> \
              <div class="drs-title" onmousedown="drag_object(event, this.parentNode)">'+title+'</div> \
              <div onClick="Wnd.hide(\''+id+'\')" class="drs-close"></div> \
              <div class="drs-cont">'+content+'</div> \
            </div> \
        </div>';
}

// Создание окна с необычным содержимым
// {type: "dom", source: ".class"} - содержимое со страницы из элемента с классом class
// {type: "url", source: "/path/to/file.htm"} - выводит содержимое любой страницы сайта
function WndData(name, title, data, params) {
    var content;
    Wnd(name, title, Wnd.loader_tpl, params)
    if (data['type'] == 'dom') {
        content = $(data['source']).html()
        Wnd.content(name, content)
        return
    } else if (data['type'] == 'url') {
        jQuery.ajax({
            url: data['source'],
            type: "GET",
            data: '', 
            success: function(response) {
                content = response
                Wnd.content(name, content)
                return
            },
            error: function(response) {
                content = response
                Wnd.content(name, content)
                return
            }
        })
    } else {
        content = 'error'
        Wnd.content(name, content)
        return
    }
}
// HTML for loading
Wnd.loader_tpl = '<span id="loader"><img src="'+WWW_ROOT+'/sys/img/ajaxload.gif" alt="loading"></span>';
// HTML for progress
Wnd.progress_tpl = '<progress min="0" max="100" value="0">0% complete</progress>';
// Function for progress
sendu.progress = function (e) {
    var progressBar = document.querySelector('progress')
    if (e.lengthComputable) {
        progressBar.value = (e.loaded / e.total) * 100
        progressBar.textContent = progressBar.value // Если браузер не поддерживает элемент progress
    }
}
// Function for full update page
sendu.update_page = function (obj,url,is_new_load) {
    if (is_new_load === true) {
        nhash = url.indexOf('#')
        if (nhash === -1) nhash = undefined;
        ohash = document.location.href.indexOf('#')
        if (ohash === -1) ohash = undefined;
        if (document.location.href.substring(0,ohash) == url.substring(0,nhash)) {
            if (typeof nhash != 'undefined')
            document.location.href = url;
            document.location.reload()
        } else
            document.location.href = url;
            
    } else {
        var content = $('body').html();
        history.pushState({}, '', url);
        $('body').html(obj.response);
        $(window).bind('popstate', function() {
            $('body').html(content);
        });
    }
}
// Дополнительная обработка URL
sendu.parseurl = function (url) {
    if (url.indexOf("?") > -1)
        url += '&ajax=true';
    else
        url += '?ajax=true';
    return url;
};

// Отправляет форму на сервер и открывает окно со статусом выполненного действия
function sendu(e, ret, params) {
    if (e instanceof Object != true) {
        e = $('#'+e)
    }
    var name = 'WinSendu',
        title = 'Информация',
        url,formname,formData;
    
    setTimeout(function(){
        if ($(e).attr("action")) {
            Wnd(name, title, Wnd.progress_tpl, params)
            formname = $(e).attr("name")
            formData = new FormData(document.forms[formname])
            url = $(e).attr("action")
        } else {
            Wnd(name, title, Wnd.loader_tpl, params)
            url = $(e).attr("href")
        }
        
        Wnd.type(name, 'alert')
        xhr = new XMLHttpRequest()
        xhr.open('POST', sendu.parseurl(url), true)
        xhr.onload = function(e) {

            if (this.status == 200) {
                // Если ошибка, или нужно что то почитать в окошке
                if (this.getResponseHeader('ResponseAjax') == 'error' || this.getResponseHeader('ResponseAjax') == 'grand') {
                    Wnd.content(name, this.response)
                    Wnd.type(name, 'grand')
                // Если пришло сообщение об успешном выполнении операции
                } else if (this.getResponseHeader('ResponseAjax') == 'ok') {
                    Wnd.content(name, this.response)
                    if (this.getResponseHeader('Refresh')) {
                        // если ответ OK и содержит редирект то загружаем страницу по адресу в редиректе
                        s = this.getResponseHeader('Refresh').split('url=');
                        // Дополнительные действия, если нужно
                        var out;
                        if (ret != undefined)
                            out = ret(this,s[1]);
                        if (out != false)
                            sendu.update_page(this,s[1],true);
                    } else {
                        // если ответ OK, но нет редиректа, то выполняем callback функцию(предполагается, что отобразить изменения на странице можно незначительными преобразованиями)
                        if (ret != undefined)
                            ret(this,url);
                        // Если ответ OK но нет ни редиректа ни callback функции, то просто обновляем текущую страницу
                        else
                            sendu.update_page(this,window.location.pathname,true);
                    }
                    // При ответе ok не может быть сообщений сильно нагруженных информацией, через 5 сек скрываем любое такое сообщение.
                    Wnd.param(name, {time: 5000})
                
                // Если статус неизвестен значит вернулась полноценная страница, а не сообщение
                } else {
                    Wnd.hide(name)
                    sendu.update_page(this,url,false)
                }
            } else {
                Wnd.content(name, 'Произошла неизвестная ошибка')
            }

        }

        if ($(e).attr("action")) {
            // Слушаем процесс загрузки файла
            xhr.upload.onprogress = sendu.progress(e)
            xhr.send(formData)
        } else {
            xhr.send()
        }
    }, 1)
    // костыль, чтобы визуальный редактор успел отправить сформированное сообщение в textarea
}

// Скрывает окно
Wnd.hide = function (name) {
    $("#"+name).fadeOut()
    $('.overlay_'+name).fadeOut()
}
// Показывает окно
Wnd.show = function (name) {
    $("#"+name).fadeIn()
    $('.overlay_'+name).fadeIn()
}
// Меняет заголовок окна
Wnd.title = function (name, content) {
    $('#'+name+' .drs-title').html(content)
}
// Меняет содержимое окна
Wnd.content = function (name, content) {
    $('#'+name+' .drs-cont').html(content)
}
// Меняет параметры окна
Wnd.param = function (name, param) {
    if (param.close == true) {
        $('#'+name+' .drs-close').css({display: "block"})
    } else if (param.close == false) {
        $('#'+name+' .drs-close').css({display: "none"})
    }

    if (param.overlay == true) {
        $('body').append('<div class="overlay overlay_'+name+'"></div>')
    } else {
        $('body').remove('.overlay_'+name)
    }

    if (param.css != undefined) {
        $('#'+name).css(param.css)
    }

    if (param.align != undefined) {
        $('#'+name+' .drs-cont').css({"text-align": param.align})
    }

    if (param.time > 0) {
        setTimeout(function() {
            Wnd.hide(name)
        }, param.time)
    }
}
// Меняет тип окна
// по сути тип окна - это просто заготовка стилей
Wnd.type = function (name, type) {
    if (type == 'alert') {
        Wnd.param(name, {close: false, overlay: false, css: {left: 'auto', top: 'auto', right: '30px', top: '65px'}})
    } else if (type == 'grand') {
        Wnd.param(name, {close: true, overlay: true, css: {left: '40%', top: '40%', right: 'auto', bottom: 'auto'}})
    }
}



/**
 * For Darsi Windows
 */
function drag_object( evt, obj )
{
    evt = evt || window.event;

    // флаг, которые отвечает за то, что мы кликнули по объекту (готовность к перетаскиванию)
    obj.clicked = true;

    // устанавливаем первоначальные значения координат объекта
    obj.mousePosX = evt.clientX;
    obj.mousePosY = evt.clientY;

    // отключаем обработку событий по умолчанию, связанных с перемещением блока (это убирает глюки с выделением текста в других HTML-блоках, когда мы перемещаем объект)
    if( evt.preventDefault ) evt.preventDefault(); 
    else evt.returnValue = false;

    // когда мы отпускаем кнопку мыши, убираем «проверочный флаг»
    document.onmouseup = function(){ obj.clicked = false }

    // обработка координат указателя мыши и изменение позиции объекта
    document.onmousemove = function( evt )
    {
        evt = evt || window.event;
        if( obj.clicked )
        {
            posLeft = !obj.style.left ? obj.offsetLeft : parseInt( obj.style.left );
            posTop = !obj.style.top ? obj.offsetTop : parseInt( obj.style.top );

            mousePosX = evt.clientX;
            mousePosY = evt.clientY;

            obj.style.left = posLeft + mousePosX - obj.mousePosX + 'px';
            obj.style.top = posTop + mousePosY - obj.mousePosY + 'px';

            obj.mousePosX = mousePosX;
            obj.mousePosY = mousePosY;
        }
    }
}



/**
 * Selector for package actions
 */
// Define the bbCode tags
packIds = [];
function addToPackage(id) {
    packIds.push(id);
    var button = document.getElementById('packButton');
    button.value = '(' + packIds.length + ')';
    if (packIds.length > 0) button.disabled = false;
}
function delFromPackage(id) {
    for(key in packIds) {
        if(packIds[key] == id) {
            packIds.splice(key, 1);
        }
    }
    var button = document.getElementById('packButton');
    button.value = '(' + packIds.length + ')';
    if (packIds.length < 1) button.disabled = true;
}
function sendPack(action) {
    var pack = document.getElementById('actionPack');
    pack.action = action;
    for(key in packIds) {
        pack.innerHTML += '<input type="hidden" name="ids[]" value="' + packIds[key] + '">';
    }
    pack.submit();
}
function checkAll(_className, check) {
    var f = $('input.' + _className);
    for (key in f) {
        var ent = f[key];
        if (typeof ent.value != 'undefined') {
            ent.checked = check;
            if(check) addToPackage(ent.value);
            else delFromPackage(ent.value);
        }
    }
}




/** Checking new PM */
function check_pm(bef_data){
    $.ajax({
      cache: false,
      type: 'GET',
      url: WWW_ROOT+'/users/get_count_new_pm/',
      data: {ajax: true},
      xhr: function() {
        var xhr = new XMLHttpRequest();
        
        xhr.onload = function(e) {
            data = this.response;
            if (typeof data != 'undefined' && data != 0) {
                if (typeof bef_data == 'undefined' || bef_data != data) {
                    Wnd('NewPM_Block', 'Новые сообщения', data);
                    Wnd.type('NewPM_Block', 'alert');
                    Wnd.param('NewPM_Block', {time: 5000});
                }
            } else {
                data = 0;
            }
            
            if(typeof check_pm.callback == 'function') {
                is_return = check_pm.callback(this.getResponseHeader('CountNewPMs'),xhr);
                if (is_return === false) return;
            }
            
            setTimeout(function(){check_pm(data)}, 20000);
        }
        
        return xhr;
      }
    });
}

/** Setting users rating */
function setRating(uid, formId) {
    var fpoints = $('#' + formId + ' input[name=points]:checked:first');
    if (fpoints[0] != undefined) var points = fpoints[0].value;
    else var points = 0;
    
    var fcomm = $('#' + formId + ' textarea[name=comment]:first');
    if (fcomm[0] != undefined) var comm = fcomm[0].value;
    else var comm = '';
    
    $.post(WWW_ROOT+'/users/rating/' + uid + '/?points=' + points, {"points":points,"comment":comm}, function(data){
        if (data == 'ok') {
            var infomess = 'Голос добавлен';
        } else {
            var infomess = data;
        }
        $('#infomess_' + uid).html(infomess);
        setTimeout("$('#setRating_"+uid+"').hide()", 2000);
        return true;
    });
}
/** Delete user vote */
function deleteUserVote(voteID) {
    $.get(WWW_ROOT+'/users/delete_vote/' + voteID, '', function(data){
        if (data == 'ok') {
            $('#uvote_' + voteID).hide();
        }
    });
}
/** Adding user warning */
function addWarning_(uid, formid) {
    var str = $('#'+formid).serialize();
    $.post(WWW_ROOT+'/users/add_warning/'+uid, str, function(data){
        if (data == 'ok') {
            var infomess = 'Голос добавлен';
        } else {
            var infomess = data;
        }
        $('#winfomess_'+uid).html(infomess);
        setTimeout("$('#addWarning_"+uid+"').hide()", 2000);
        return true;
    });
}
/** Delete user warning */
function deleteUserWarning(wID) {
    $.get(WWW_ROOT+'/users/delete_warning/' + wID, '', function(data){
        if (data == 'ok') {
            $('#uvote_w' + wID).hide()
        }
    })
}
/** Setting user group */
function setGroup(uid, formId) {
    var fgroup = $('#' + formId + ' option:selected');
    if (fgroup[0] != undefined) {
        var group = fgroup[0].value;
        $.post(WWW_ROOT+'/users/update_group/' + uid + '/?group=' + group, {"group":group}, function(data){
            if (data == 'ok') {
                $('#infomess_group_' + uid).html('Группа изменена успешно!');
            } else {
                $('#infomess_group_' + uid).html(data);
            }
            return true;
        });
    }
}
