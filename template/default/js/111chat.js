// дата последнего сообщения, что получил пользователь, в unixtime
var last = 0;
// можем ли мы выполнять сейчас загрузку сообщений. Сначала стоит false, что значит - да, можем
// это сделано для того, чтобы мы не начали загрузку заново, если прошлая загрузка ещё не закончилась.
var load_in_process = false;

// получаем список сообщений и ставим их получение на таймер
$(document).ready(function () {
    chat.jspAPI = $('#chat').jScrollPane({
        mouseWheelSpeed:10
    }).data('jsp');
    update();
    setInterval(function() { update(true) }, 5000);

    if (readCookie('switchsound')=='deactive')
        $('#switchsound').addClass('deactive');
});

// функция для формирования html кода сообщения и его добавления на страницу
function addChatLine(params){
    del = '';
    if (params["del"] != '') {
        del =   '<a class="hjgh" href="'+params["del"]+'" onClick="chatDel(this); return false;">'+
                '   <div class="drs-del"></div>'+
                '</a>';
    }
    html =  '<div class="block_chat">'+
                '<div style="float: right">'+
                    '<a href="'+WWW_ROOT+'/users/info/'+params["user_id"]+'" target="_blank">'+
                        '<div class="drs-user"></div>'+
                    '</a>'+
                    params["ip"]+
                    del+
                '</div>'+
                '('+params["time"]+') '+
                '<a href="javascript:void(\'Ответить на сообщение\')" onclick="parent.window.document.getElementById(\'chatModule\').value+=\'[b]'+params["name"]+'[/b], \';return false;" class="name">'+
                    params["name"]+
                '</a>:'+
                '<div class="mess">'+
                    params["message"]+
                '</div>'+
            '</div>';
    chat.jspAPI.getContentPane().prepend(html);
    chat.jspAPI.reinitialise();
    last = params["unixtime"];
}

// загрузка сообщений
function update(n) {
    if(!load_in_process) {
        load_in_process = true;
        $.post(WWW_ROOT+"/chat/ajax_messages/"+last, {},
        function (result) {
            result = $.parseJSON(result);
            // перебираем все сообщения и формируем их
            for(var i=0;i<result.length;i++){
                addChatLine(result[i]);
            }
            if ((n == true) && (i>0) && ($("#switchsound").is(".deactive") == false)) {
                $("#soundchat")[0].play();
            }
            load_in_process = false;
        });
    }
}

// удаление сообщений
function chatDel(e) {
    $.post($(e).attr('href'), {},
    function (result) {
        // если по какой то причине не true, стараемся не замечать клика
        if (result == true) {
            $(e).parent().parent().hide();
            chat.jspAPI.reinitialise();
        }
    });
}

// отправка сообщений
function submitChat(e) {
    var message = $("textarea#chatModule").val();
    var message = encodeURIComponent(message);
    var keystring = $(".chat_captcha input[name='captcha_keystring']").val();

    var params = 'message='+message;
    if (typeof keystring != 'undefined') params = params + '&captcha_keystring='+keystring;

    $("chatWait").show();

    $.post($(e).attr('action'), params, 
        function(resp, data){responseData(resp, data);}
    );
}

// слушаем ответ о статусе отправленного сообщения
function responseData(resp, data) {
    $("chatWait").hide();
    if (resp == 'ok') {
        update();
        $("#chat_meta").html('');
        $("textarea#chatModule").val('');
        $(".chat_captcha input[name='captcha_keystring']").val('');
    } else {
        $("#chat_meta").html(resp);
    }
    refreshCaptcha();
}

// перезагрузка каптчи
function refreshCaptcha() {
    if ($("img").is("#drs_captcha")) {
        $('.chat_captcha img#drs_captcha').attr('src',
            $('.chat_captcha img#drs_captcha').attr('src') + '&rand=' + Math.round(Math.random(0)*1000)
        );
    }
} 



function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else
        expires = "";
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        pos = ca[i].indexOf("=");
        x = ca[i].substr(0, pos).replace(/^\s+|\s+$/g,"");
        if (x == name) {
            return unescape(ca[i].substr(pos + 1));
        }
    }
    return null;
}

$('#switchsound').click(function() {
    if ($("#switchsound").is(".deactive")) {
        $('#switchsound').removeClass('deactive');
        createCookie('switchsound', '', 360);
    } else {
        $('#switchsound').addClass('deactive');
        createCookie('switchsound', 'deactive', 360);
    }
})
