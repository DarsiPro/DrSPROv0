//var protocol = '';//,
    //sessionLifeTime = 0,
    //currency = '';
// массив подключенных скриптов, для избежания дублей
var javascripts = [];

// главный модуль админки, управляет всем остальным, включает в себя ряд полезных функций используемых повсеместно.
var admin = (function () {

  return {
    SITE: "null", // домен сайта
    SECTION: "", // страница по умолчанию
    WAIT_PROCESS: false, // процесс загрузки
    PROTOCOL: "", // домен сайта
    
    PULIC_MODE: false, // становится true когда включен режим редактирования на сайте
    searcharray: [], //массив найденных товаров в строке поиска
    
    /**
     * Инициализация компонентов админки
     */
    init: function () {
      // Настройки из вёрстки пока не берем D:
      
	  var dsBaseDir = location.protocol + '//' + location.host;
	  this.SITE = dsBaseDir; 
	  
      this.PROTOCOL = location.protocol;
      this.SECTION = location.pathname.split('/')[2].split('.').slice(0, -1).join('.');
      

      // Инициализация обработчиков
      admin.initEvents();
    },
    initEvents: function() {
      
      
      
      
      
      //Подключаем и запускаем JS если он есть у этой SECTION
      if (admin.SECTION && ['category', 'jane'].includes(admin.SECTION)) {
          includeJS(admin.SITE + '/admin/js/'+admin.SECTION+'.js?v'+Math.random(), admin.SECTION+'.init');
      }
      
      
      
      
      
      
      
      //тыкалка для меню разделов админки  ПОКА НЕ ИСПОЛЬЗУЕТСЯ
      $('body').on('click', 'a', function () {
        //var section = $(this).data('section');
        var callback = false;
        //admin.SECTION = section;
        admin.PegeUrl = $(this).attr('href');
        
        
        alert(admin.PegeUrl);

        switch(section) {
          /*case 'catalog':
            includeJS(admin.SITE + '/mg-core/script/admin/catalog.js');
            callback = catalog.init;
            break;
          
          */
          
          /*case 'category':
            includeJS(admin.SITE + '/mg-core/script/admin/category.js');
            callback = category.init;
            break;
          case 'page':
            includeJS(admin.SITE + '/mg-core/script/admin/page.js');
            callback = page.init;
            break;
          case 'orders':
            includeJS(admin.SITE + '/mg-core/script/admin/orders.js');
            callback = order.init;
            break;
          case 'users':
            includeJS(admin.SITE + '/mg-core/script/admin/users.js');
            callback = user.init;
            break;
          case 'plugins':
            includeJS(admin.SITE + '/mg-core/script/admin/plugins.js');
            callback = plugin.init;
            break;
          case 'settings':
            includeJS(admin.SITE + '/mg-core/script/admin/settings.js');
            callback = settings.init;
            break;
          case 'marketplace':
            includeJS(admin.SITE + '/mg-core/script/admin/marketplace.js');
            callback = marketplaceModule.init;
            break;
          case 'statistic':
            includeJS(admin.SITE + '/mg-core/script/chart.js');
            includeJS(admin.SITE + '/mg-core/script/admin/statistic.js');
            callback = statistic.callBack;
            break;*/
        }

        //admin.show(section+".php", "adminpage", cookie(section + "_getparam"), callback);
      });
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
 
      
      
      
      
      
      
      // Отметить/cнять все checkbox
      $('#main').on('change', '#check-all', function () {
		if ($(this).is(':checked')){
		    $('#dsList input[type="checkbox"][name*="check"]').prop('checked', true).change();
		} else {
		    $('#dsList input[type="checkbox"][name*="check"]').prop('checked', false).change();
		}
      });	
      
      
      
      // автотранслит заголовка в URL. При клике, или табе, на поле URL, если оно пустое то будет автозаполненно транслитироированным заголовком
      /*$('.mg-admin-body').on('click, focus', 'input[name=url]', function () {
        if ($('input[name=url]').val() == '') {
          var text = $('input[name=title]').val();
          if (text) {
            text.replace('%', '-');
            text = admin.urlLit(text, 1);
            $(this).val(text);
          }
        }
      });*/
      
      // защита для ввода в числовое поле символов
      /*$('.mg-admin-body').on('keyup', '.numericProtection', function () {
        if (isNaN($(this).val()) || $(this).val() < 0) {
          $(this).val('1');
        }
      });*/
	  
      // обработка клика по кнопке - сбросить кэш
      /*$(document.body).on('click', '.js-admin-clear-cash', function () {
        admin.ajaxRequest({
          mguniqueurl: "action/clearСache",
        },
          function (response) {
            location.reload();
          }
        );
      });*/
      
      // Фиксируем футер главной таблицы, если выбрана хотя бы одна строка
      /*$('.mg-admin-body').on('change', '.main-table [name=product-check],.main-table .checkbox [type="checkbox"]', function () {
        let footer = $('.widget-footer');
        let fixClass = 'widget-footer--fixed';
        if ($('.main-table [type="checkbox"]:checked').length > 0) {
          footer.addClass(fixClass);
        } else {
          footer.removeClass(fixClass);
        }
      });*/
    },
	// END initEvents
	
   
    

      // делаем пункт выбранным
      /*$('#' + this.SECTION).addClass('active');
      $('.mg-admin-mainmenu-item[data-section='+this.SECTION+']').addClass('active');*/
    

    /**
     * Индикатор сообщений
     * Функция выводит информацию об успешности или ошибки
     * различных действия администратора в админке.
     */
    /*indication: function (status, text) {

      $('.message-error').remove();
      $('.message-succes').remove();
      var object = "";
      switch (status) {
        case 'success':
        {
          $('body').append('<div class="message-succes"></div>');
          object = $('.message-succes');
          break;
        }
        case 'error':
        {
          $('body').append('<div class="message-error"></div>');
          object = $('.message-error');
          text = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> '+text;
          break;
        }
        default:
        {
          $('body').append('<div class="message-error"></div>');
          object = $('.message-error');
          text = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> '+text;
          break;
        }
      }

      object.addClass("up");
      object.html(text);
      setTimeout(function () {
        object.remove();
      }, 3000);
    },*/
    /**
     * Обертка для всех аякс запросов админки
     * необходимо для оптимизации вывода процесса загрузки
     * и унификации всех аякс вызовов
     */
    /*ajaxRequest: function (data, callBack, loader, dataType, noAlign) {

      

    },*/

    

    /**
     * Транслитирирует строку
     */
    /*urlLit: function (string, lower) {
      var dictionary = {'А': 'a', 'Б': 'b', 'В': 'v', 'Г': 'g', 'Д': 'd', 'Е': 'e', 'Ё': 'yo', 'Ж': 'j', 'З': 'z', 'И': 'i', 'Й': 'y', 'К': 'k', 'Л': 'l', 'М': 'm', 'Н': 'n', 'О': 'o', 'П': 'p', 'Р': 'r', 'С': 's', 'Т': 't', 'У': 'u', 'Ф': 'f', 'Х': 'h', 'Ц': 'ts', 'Ч': 'ch', 'Ш': 'sh', 'Щ': 'sch', 'Ъ': '', 'Ы': 'y', 'Ь': '', 'Э': 'e', 'Ю': 'yu', 'Я': 'ya', 'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'j', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya', '1': '1', '2': '2', '3': '3', '4': '4', '5': '5', '6': '6', '7': '7', '8': '8', '9': '9', '0': '0', 'І': 'i', 'Ї': 'i', 'Є': 'e', 'Ґ': 'g', 'і': 'i', 'ї': 'i', 'є': 'e', 'ґ': 'g'};
      // старый вариант
      //var dictionary = {'а':'a', 'б':'b', 'в':'v', 'г':'g', 'д':'d', 'е':'e', 'ж':'g', 'з':'z', 'и':'i', 'й':'y', 'к':'k', 'л':'l', 'м':'m', 'н':'n', 'о':'o', 'п':'p', 'р':'r', 'с':'s', 'т':'t', 'у':'u', 'ф':'f', 'ы':'i', 'э':'e', 'А':'A', 'Б':'B', 'В':'V', 'Г':'G', 'Д':'D', 'Е':'E', 'Ж':'G', 'З':'Z', 'И':'I', 'Й':'Y', 'К':'K', 'Л':'L', 'М':'M', 'Н':'N', 'О':'O', 'П':'P', 'Р':'R', 'С':'S', 'Т':'T', 'У':'U', 'Ф':'F', 'Ы':'I', 'Э':'E', 'ё':'yo', 'х':'h', 'ц':'ts', 'ч':'ch', 'ш':'sh', 'щ':'shch', 'ъ':'', 'ь':'', 'ю':'yu', 'я':'ya', 'Ё':'YO', 'Х':'H', 'Ц':'TS', 'Ч':'CH', 'Ш':'SH', 'Щ':'SHCH', 'Ъ':'', 'Ь':'',	'Ю':'YU', 'Я':'YA','і':'i', 'ї':'i', 'є':'e', 'ґ':'g', 'І':'i', 'Ї':'i', 'Є':'e', 'Ґ':'g' };
      var result = string.replace(/[\s\S]/g, function (x) {
        if (dictionary.hasOwnProperty(x))
          return dictionary[ x ];
        return x;
      });
      result = result.replace(/\W/g, '-').replace(/[-]{2,}/gim, '-').replace(/^\-+/g, '').replace(/\-+$/g, '');
      if (lower) {
        result = result.toLowerCase();
      }
      return  result;
    },
	*/
    /*
     * альтернатива htmlspecialchars
     */
    /*htmlspecialchars: function (text) {
      if (text) {
        return text
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;");
      }
      return text;
    },*/
    /**
     * альтернатива htmlspecialchars_decode
     */
    /*htmlspecialchars_decode: function (text) {
      if (text) {
        return text
          .replace(/&amp;/g, "&")
          .replace(/&lt;/g, "<")
          .replace(/&gt;/g, ">")
          .replace(/&quot;/g, "\"")
          .replace(/&#039;/g, "\'");
      }
      return text;
    },*/
    
    /**
     * Открывает модальное окно
     */
    /*openModal: function (object) {
      
	  
    },*/

    /**
     * Закрывает модальное окно
     */
    /*closeModal: function (object) {
      
	  
	  
    },*/

    

    /**
     * Шаблоны регулярных выражений для проверки ввода в поля
     * admin.regTest(4,'текст')
     */
    /*regTest: function (regId, text) {
      switch (regId) {
        case 1:
        {
          return /^[-0-9a-zA-Zа-яА-ЯёЁїЇєЄґҐ&`'іІ«»()$%\s_\"\.,!?:]+$/.test(text);
          break;
        }
        case 2:
        {
          return /^[-0-9a-zA-Zа-яА-ЯёЁїЇєЄґҐ&`'іІ«»()$%\s_]+$/.test(text);
          break;
        }
        case 3:
        {
          return /^[,\s]+$/.test(text);
          break;
        }
        case 4:
        {
          return /["']/.test(text);
          break;
        }
        case 5: // проверка email
        {
          return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(text);
          break;
        }
      }
    },*/
    /**
     * отсечение символа по краям строки
     */
    /*trim: function (s, simb) {
      if (!simb) {
        s = s.replace(/\s+$/g, '');
        s = s.replace(/^\s+/g, '');
      } else {
        s = s.replace(eval("/^\\" + simb + "+/g"), '');
        s = s.replace(eval("/\\" + simb + "+$/g"), '');
      }
      return s;
   },*/
    
    
    /**
     * Метод для редактирования контента в публичной части для администратора
     */
    /*publicAdmin: function () {
      admin.PULIC_MODE = true;

      if ($(".admin-top-menu").length > 0) {
        $("body").addClass("admin-on-site");
      }
      else {
        $("body").removeClass("admin-on-site");
      }

      // клик по элементу открывающему модалку
      $('body').on('click', '.modalOpen', function (e) {
        admin.showLoaderModal($(this)); //Блокируем кнопку, чтобы не кликали много раз
        e.preventDefault();
        $('.admin-center .reveal-overlay').remove();

        var section = $(this).data('section');
        $('body').append('<div class="admin-center" ><div class="data"></div></div>');
        includeJS(admin.SITE + '/mg-core/script/admin/'+section+'.js');

        // перечень функций выполняемых после  получения ответа от сервера
        // (вырезаем только модалку из полученного контента, и открываем ее с нужными параметрами)

        admin.AJAXCALLBACK = [
          {callback: 'admin.cloneModal', param: [section]},
          {callback: section+'.openModalWindow', param: eval($(this).data('param'))},
          {callback: 'admin.hideLoaderModal', param: [$(this)]}, //Разблокируем кнопку
          {callback: section+'.init', param: null},
          {callback: 'admin.initEvents', param: null}
        ];

        // открываем раздел из которого вызовем модалку
        admin.ajaxRequest(
          "mguniqueurl=" + section+ ".php&mguniquetype=adminpage&",
          function (data) {
            $(".admin-center .data").html(data);
            admin.initCustom();
          },
          false,
          "html");
      });


      // контекстное меню при наведении на элемент в публичной части
      $('body').on({
        mouseenter: function () {
          $(this).find('.admin-context').show();
        },
        mouseleave: function () {
          $(this).find('.admin-context').hide();
        }
      },'.exist-admin-context');
      $(".exist-admin-context").parent().css({display: "block"});

 },*/
    /*
    includeColorPicker: function() {
      includeJS(admin.SITE + '/mg-core/script/colorPicker/js/colorpicker.js');
      includeJS(admin.SITE + '/mg-core/script/colorPicker/js/eye.js');
      includeJS(admin.SITE + '/mg-core/script/colorPicker/js/utils.js');
    },
    includeCodemirror: function() {
      includeJS(dsBaseDir+'/mg-core/script/codemirror/lib/codemirror.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/mode/javascript/javascript.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/mode/xml/xml.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/mode/php/php.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/mode/css/css.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/mode/clike/clike.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/search/search.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/search/searchcursor.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/search/jump-to-line.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/search/match-highlighter.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/search/matchesonscrollbar.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/dialog/dialog.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/scroll/annotatescrollbar.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/scroll/scrollpastend.js');
      includeJS(dsBaseDir+'/mg-core/script/codemirror/addon/scroll/simplescrollbars.js');
    },
    */
    
    
    
    
    
    
    
    // не используется в планах
    
    /**
     * Обертка для всех аякс запросов админки
     * необходимо для оптимизации вывода процесса загрузки
     * и унификации всех аякс вызовов
     */
    ajaxRequest: function (data, callBack, loader, dataType, noAlign) {

      if (!dataType)
        dataType = 'json';
      //if (!loader)
       // loader = $('.mailLoader');

      $.ajax({
        type: "POST",
        url: "ajax",
        data: data,
        cache: false,
        dataType: dataType,
        success: callBack,
        beforeSend: function () {
          // флаг, говорит о том что начался процесс загрузки с сервера
          admin.WAIT_PROCESS = true;
         // loader.hide();
         // loader.before('<span class="loader huge working"></span>');
          // через 300 msec отобразится лоадер.
          // Задержка нужна для того чтобы не мерцать лоадером на быстрых серверах.
          setTimeout(function () {
            if (admin.WAIT_PROCESS) {
              //admin.waiting(true);
            }
          }, 300);
        },
        complete: function () {

          // завершился процесс
          admin.WAIT_PROCESS = false;
          //прячем лоадер если он успел появиться
          //admin.waiting(false);
/*
          if ($('[data-tooltip]').length) {
            if($('.tooltip').length != 0) {
              $('.tooltip').remove();
            }

            admin.foundationInit();
          }
*/
          // инициализация стрелок скролла
          //admin.scrollInit();

          //loader.show();
          //$('.view-action').remove();

          //if ($('.b-modal').length > 0 && !noAlign) {
          //  admin.centerPosition($('.b-modal'));
          //}

          // выполнение стека отложенных функций после AJAX вызова
          /*if (admin.AJAXCALLBACK) {
            //debugger;
            var tmpAJAXCALLBACK = admin.AJAXCALLBACK;
            admin.AJAXCALLBACK = null;
            tmpAJAXCALLBACK.forEach(function (element, index, arr) {
              eval(element.callback).apply(this, element.param);
            });
          }*/
        },
        error: function (request, status, error) {
          if(error == 'Internal Server Access denied') {
            location.reload();
            return false;
          }
          
          
          $('#main').html();
          

          /*if ($('.session-info').html()) {
            sessInfoText = "<div class='help-text'>"+lang.USER_NOT_ACTIVE+" "
                +"<strong>" + Math.round(admin.SESSION_LIFE_TIME/60) + "</strong> "+lang.MINUTES+" <br />"
                +lang.SESSION_CLOSED+"</div>"
                +"<form method='POST' action='" + admin.SITE + "/enter'>"
                +"<ul class='form-list'><li><span>" + lang.EMAIL + ":</span>"
                +"<input type='text' name='email'></li>"
                +"<li><span>" + lang.PASS + ":</span>"
                +"<input type='password' name='pass'></li></ul>"
                +"<button type='submit' class='default-btn'>" + lang.LOGIN + "</button></form>";
            $('.session-info').html(sessInfoText);
            $('.session-info').removeClass('alert');
            clearInterval(admin.SESSION_CHECK_INTERVAL);
            return false;
          }*/

          //errors.showErrorBlock(request.responseText);
        }
      });

    },
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    callFromString: function (callback) {
      var cb = '';
      if (callback) {
        if (callback.indexOf('.') > -1) {
          var parts = callback.split('.');
          if (typeof (window[parts[0]]) !== 'undefined') {
            cb = window[parts[0]][parts[1]];
          }
        } else {
          if (typeof (window[callback]) !== 'undefined') {
            cb = window[callback];
          }
        }
        if (typeof cb === "function") {
          cb();
        }
      }
    },
    
    
    // Переход на главную страницу модуля
    ViewHomePageModule: function (callback) {
        alert('dfgd');
        return false;
    },
    
    
    
    
    
    
    
    
    
    
    
    
    
    
  };

})();

//функция для работы с куками
/*function cookie(name, value, options) {
  if (name !== 'PHPSESSID') {
    if (typeof value != 'undefined') {
      if (value === null) {
        value = '';
      }
      window.sessionStorage[name] = value;
    }
    else{
      if (null !== window.sessionStorage[name]) {
        return window.sessionStorage[name];
      }
    }
  }
  if (typeof value != 'undefined') {
    options = options || {};
    if (value === null) {
      value = '';
      options.expires = -1;
    }
    var expires = '';
    if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
      var date;
      if (typeof options.expires == 'number') {
        date = new Date();
        date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
      } else {
        date = options.expires;
      }
      expires = '; expires=' + date.toUTCString();
    }

    // var path = options.path ? '; path=' + (options.path) : '';
    var path = "; path=/";
    var domain = options.domain ? '; domain=' + (options.domain) : '';
    var secure = options.secure ? '; secure' : '';
    document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
  } else {
    var cookieValue = null;
    if (document.cookie && document.cookie != '') {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = jQuery.trim(cookies[i]);
        if (cookie.substring(0, name.length + 1) == (name + '=')) {
          cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
          break;
        }
      }
    }
    return cookieValue;
  }
}*/














/**
 * подключает javascript файл и выполняет его
 * заносит название файла в реестр подключенных,
 * дабы не дублировать
 */
function includeJS(path, callback) {
  for (var i = 0; i < javascripts.length; i++) {
    if (path == javascripts[i]) {
      alert('JavaScript: ['+path+'] уже был подключен ранее!');
      admin.callFromString(callback);
      return false;
    }
  }
  javascripts.push(path);
  //var version = $('.mg-version').html();
  //if (version) {version = version.trim();}
  $.ajax({
    url: path,//+'?v='+version,
    dataType: "script", // при типе script JS сам инклюдится и воспроизводится без eval
    async: false
  });
  admin.callFromString(callback);
}

/**
 * подключет CSS файл
 */
function includeCSS(name) {
  if (!$('link[href="'+name+'"]').length) {
    $('head').append('<link rel="stylesheet" href="'+name+'" type="text/css" />');
  }
}



$(document).ready(function () {
  // js переменные из движка
  //document.cookie.split(/; */).forEach(function(cookieraw){
    //if(cookieraw.indexOf('mg_to_script') === 0) {
      //var cookie = cookieraw.split('=');
      //var name = cookie[0].substr(13);
      //var value = decodeURIComponent(decodeURI(cookie[1]));
      //window[name] = admin.tryJsonParse(value.replace(/&nbsp;/g, ' '));
    //}
  //});

  //window.CKEDITOR_BASEPATH = dsBaseDir+"/mg-core/script/ckeditor/";

  // все скрипты в админке нужно подключать через функцию includeJS,
  //includeJS(dsBaseDir + '/mg-admin/locales/'+lang+'.js');
  //includeJS(dsBaseDir + '/mg-core/script/jquery-ui.min.js');
  //includeJS(dsBaseDir + '/mg-core/script/toggles.js');
  //includeJS(dsBaseDir + '/mg-core/script/jquery.form.js');
  //includeJS(dsBaseDir + '/mg-core/script/ckeditor/ckeditor.js');
  //includeJS(dsBaseDir + '/mg-core/script/ckeditor/adapters/jquery.js');
  //includeJS(dsBaseDir + '/mg-core/script/sumoselect.min.js');
  //includeCSS(dsBaseDir + '/mg-admin/design/css/sumoselect.min.css');
  admin.init();
});