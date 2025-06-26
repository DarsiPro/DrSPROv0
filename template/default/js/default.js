function setCookie (name, value, expires, path, domain, secure) {
      document.cookie = name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
}

function getCookie(name) {
    var cookie = " " + document.cookie;
    var search = " " + name + "=";
    var setStr = null;
    var offset = 0;
    var end = 0;
    if (cookie.length > 0) {
        offset = cookie.indexOf(search);
        if (offset != -1) {
            offset += search.length;
            end = cookie.indexOf(";", offset)
            if (end == -1) {
                end = cookie.length;
            }
            setStr = unescape(cookie.substring(offset, end));
        }
    }
    return(setStr);
}

// Подсветка активных пунктов меню
$(function () {
    $('.ds_MainMenu li a').each(function () {
        var location = window.location.href;
        var link = this.href;
        var result = location.match(link);
        if(location == link || location == link+'/') {
          $(this).addClass('active');
        }
        
        if(result != null && link != window.location.protocol + '//' + window.location.hostname + WWW_ROOT + '/') {
          $(this).addClass('current');
        }
    });
});

function show_chat() {
    $('#ochat').animate({bottom:'10px'},200)
    $('#ochat .show_chat').css('display', 'none')
    $('#ochat .hide_chat').css('display', 'block')
    setCookie('chat', '1', 10, "/")
}
function hide_chat() {
    $('#ochat').animate({bottom: -$('#ochat').height()+75},200)
    $('#ochat .show_chat').css('display', 'block')
    $('#ochat .hide_chat').css('display', 'none')
    setCookie('chat', '0', 10, "/")
}

$(document).ready(function() {
    chtcc = getCookie('chat')
    if(chtcc == '1') {
        $('#ochat').css('bottom', '10px')
        $('#ochat .show_chat').css('display', 'none')
        $('#ochat .hide_chat').css('display', 'block')
    } else {
        $('#ochat').css('bottom', -$('#ochat').height()+75)
    }
})

// Запуск FancyBox
$(document).ready(function() {
	$("a.gallery").fancybox();
});

// Скрытие/раскрытие дополнительных полей при добавлении материала
function metaTags(element) {
	if (!$(element).is(':checked')) {
		$('#meta').slideUp("slow");
		$('#tags').slideUp("slow");
		$('#sourse').slideUp("slow");
		$('#sourse_email').slideUp("slow");
		$('#sourse_site').slideUp("slow");
	} else {
		$('#meta').slideDown("slow");
		$('#tags').slideDown("slow");
        $('#sourse').slideDown("slow");
		$('#sourse_email').slideDown("slow");
        $('#sourse_site').slideDown("slow");
	}
};

//Автоподстановка имени пользователя в форме
$(function() {
    $('[list=findusers]').keyup(function() {
        if ($('[list=findusers]').val().length > 2) {
            $.get(WWW_ROOT+'/users/search_niks/'+$('[list=findusers]').val()+'/', {}, function(data){
                $('#findusers').html(data);
            });
        } else {
            $('#findusers').html('');
        };
    });
    return;
});

// Выводит индекс в конце текущего url(если он есть)
function intURLafter(){
    var loc = window.location.href,
         url = loc.split('/'),
		 output;
		  
	var i = 5; // начинаем с 5 чтобы меньше итераций делать(http:1/2/site.ru3/meseges4/наш id)
    while (url[i] !== undefined) { // если www_root чему то равен, то увеличиваем "глубину"
      id = url[i];
	  i++;
	}
	
	if ( /\d/.test(id) ) output = id; //если id из цифр, то выводим
	
	return output;
};

$(function() {
    $('.charcount').keyup(function(){
        maxLength = $(this).attr('maxlength');
        name = $(this).attr('name');
        if ($(this).val().length > 0 && maxLength != null) {
            $('.charcount.'+name).text('Введено ' + $(this).val().length + ' из ' + maxLength + ' символов');
        } else {
            $('.charcount.'+name).text('');
        }
    });
});

function replyComment(el, id, name){
    $('.comment_tree').removeClass('reply_comment');
    $(el).parents('.comment_tree').addClass('reply_comment');
    $('.addcomment .inftitle').html('Ответ для '+name);
    $('.addcomment input[name=reply]').val(id);
    $('.addcomment .canselReplyComment').css('display', 'inline-block');
}

function canselReplyComment(){
    $('.comment_tree').removeClass('reply_comment');
    $('.addcomment .inftitle').html('Добавление комментария');
    $('.addcomment input[name=reply]').val(0);
    $('.addcomment .canselReplyComment').css('display', 'none');
}