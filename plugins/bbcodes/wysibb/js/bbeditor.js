WBBPRESET = {
    buttons: 'bold,italic,underline,strike,|,justifyleft,justifycenter,justifyright,|,smilebox,|,code,quote,spoiler,hide,bullist,numlist,|,link,img,video,|,fontcolor,fontsize,cut,removeFormat',
    traceTextarea: true,
    imgupload: false,
    allButtons: {
        spoiler : {
            title: CURLANG.spoiler,
            buttonText: 'spoiler',
            transform : {
                '<div><div><b>Сворачиваемый текст</b></div><div style="border: 1px inset ; overflow: auto;">{SELTEXT}</div></div>':"[spoiler]{SELTEXT}[/spoiler]"
            }
        },
        hide : {
            title: 'Скрытый текст',
            buttonText: 'hide',
            transform : {
                '<div><div><b>Скрытый текст</b></div><div style="border: 1px inset ; overflow: auto;">{SELTEXT}</div></div>':"[hide]{SELTEXT}[/hide]"
            }
        },
        quote : {
            transform : {
                '<div class="bbQuoteBlock"><div class="bbQuoteName"><b>Цитата</b></div><div class="quoteMessage">{SELTEXT}</div></div>':'[quote]{SELTEXT}[/quote]',
                '<div class="bbQuoteBlock"><div class="bbQuoteName"><b>{AUTHOR} пишет:</b></div><div class="quoteMessage">{SELTEXT}</div></div>':'[quote="{AUTHOR}"]{SELTEXT}[/quote]',
                '<div class="bbQuoteBlock"><div class="bbQuoteName"><b>{AUTHOR} пишет:</b></div><div class="quoteMessage">{SELTEXT}</div></div>':'[quote={AUTHOR}]{SELTEXT}[/quote]'
            }
        },
        bullist: {
            transform: {
                '<ul>{SELTEXT}</ul>':'[list]{SELTEXT}[/list]',
                '<li>{SELTEXT}</li>':'[*]{SELTEXT[^\[\]\*]}'
            }
        },
        numlist: {
            transform: {
                '<ol>{SELTEXT}</ol>':'[list=1]{SELTEXT}[/list]',
                '<ol type="a">{SELTEXT}</ol>':'[list=a]{SELTEXT}[/list]',
                '<li>{SELTEXT}</li>':'[*]{SELTEXT[^\[\]\*]}'
            }
        },
        img : {
            transform : {
                '<img src="{SRC}" style="max-width:400px; max-height:400px; float:left;" />':"[imgl]{SRC}[/imgl]",
                '<img src="{SRC}" style="max-width:400px; max-height:400px; float:inherit;" />':"[img]{SRC}[/img]"
            }
        },
        video: {
            transform: {
                '<iframe src="http://www.youtube.com/embed/{SRC}" width="450" height="300" frameborder="0"></iframe>':'[video]http://www.youtube.com/watch?v={SRC}[/video]'
            }
        },
        cut: {
            title:"В списке материалов будет отображаться текст только до этого тега",
            buttonText: '[CUT]',
            transform: {
                '<hr class="wysicut">':'[cut]'
            }
        }
    }
}



function catchSelection() {
    if (window.getSelection) {
        selection = window.getSelection().toString();
    } else if (document.getSelection) {
        selection = document.getSelection();
    } else if (document.selection) {
        selection = document.selection.createRange().text;
    }
}

function quoteSelection(name) {
    l_no_text_selected = "Выделите текст на странице и попробуйте еще раз";

    if (selection) {
        $('#editor').insertAtCursor('<div class="bbQuoteBlock"><div class="bbQuoteName"><b>'+name+' пишет:</b></div><div class="quoteMessage">'+selection+'</div></div>', false);
        selection = '';
        return;
    } else {
        alert(l_no_text_selected);
        return;
    }
}

function checkForm() {
    l_empty_message = "Вы должны ввести текст сообщения";

    $('#editor').sync();
    if (document.getElementById("sendForm").mainText.value.length < 2) {
        alert(l_empty_message);
        return false;
    } else {
        return true;
    }
}
