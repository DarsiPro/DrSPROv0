function TmplHighlight(t, e = {}) {
    if (this.constructor = TmplHighlight, this.extended = "ucoz", this.idPrefix = "codepad_", this.textarea_id = t, this.editor = {}, this.button = {}, this.srwnd = null, this.searches = {
            pos: null,
            query: null,
            replQuery: null,
            caseSensitive: !1,
            regExp: !1,
            beginFrom: 1,
            newSearch: !0,
            marked: []
        }, this.opts = Object.assign({
            mode: "text/html",
            wysiwyg: "",
            widgets: 1
        }, e), this.translate = null != e ? Object.assign(TmplHighlight.translate, e.translate || {}) : TmplHighlight.translate, "text/css" == this.opts.mode && (this.opts.wysiwyg = "", this.opts.widgets = 0), !CodeMirror) throw new Error("CodeMirror must be loaded for code sintax highlight.");
    if (!document.getElementById(this.textarea_id)) throw new Error("No textarea with this ID.");
    this.init()
}
TmplHighlight.translate = {
    FileManager: "File Manager",
    Search: "Search",
    Replace: "Replace",
    Undo: "Undo",
    Redo: "Redo",
    LineNumbers: "Line Numbers",
    Adjust: "Adjust Code",
    WYSIWYG: "WYSIWYG Editor",
    Widgets: "Widgets",
    HighlightOff: "Code Highlight Off",
    SearRepl: "Search & Replace",
    FinW: "Find",
    RepW: "Replace With",
    Begin: "Begin from",
    BeginFC: "Start from Cursor",
    BeginFB: "Start from Beginning",
    Options: "Options",
    MatC: "Match Case",
    RegExp: "Regular Expression",
    SearBut: "Search",
    RepBut: "Replace",
    RepAllBut: "Replace all",
    Cancel: "Cancel",
    NotFound: "Phrase not found",
    RegExpIncorrect: "Regular Expression Incorrect",
    ChangeTheme: "Change Color Theme",
    MadeRep: "Replaced complete",
    FullScreen: "Enlarge template editor",
    FullScreenBack: "Reduce template editor"
}, TmplHighlight.prototype = {
    onChangeText: function() {
        var t = this.editor.historySize();
        0 == t.undo ? this.button.$Undo.addClass("disabled") : this.button.$Undo.removeClass("disabled"), 0 == t.redo ? this.button.$Redo.addClass("disabled") : this.button.$Redo.removeClass("disabled")
    },
    init: function() {
        this.buttonPanel = document.createElement("div"), this.buttonPanel.classList.add("panel-control","button-group","with-mid-padding","s12"), 
        this.makeButton("FileManager", this._txt("FileManager"),' icon-cloud-upload', {
            hint: 1
        }), 
        //this.makeSeparator(), 
        this.makeButton("Search", this._txt("Search"),' icon-search'), 
        
        
        
        
        
        
        this.makeButton("Replace", this._txt("Replace"),''), 
        //this.makeSeparator(), 
        this.makeButton("Undo", this._txt("Undo"),' icon-undo', {
            disabled: 1
        }), this.makeButton("Redo", this._txt("Redo"),' icon-redo', {
            disabled: 1
        }), 
        //this.makeSeparator(), 
        this.makeButton("LineNumbers", this._txt("LineNumbers"),' icon-numbered-list', {
            active: 1
        }), 
        this.makeButton("Reindent", 
        this._txt("Adjust"),' icon-flow-cascade'), 
        //(this.opts.wysiwyg || this.opts.widgets) && this.makeSeparator(), 
        
        
        this.opts.wysiwyg && this.makeButton("WYSIWYG", this._txt("WYSIWYG"),' icon-browser', {
            hint: 1
        }), this.opts.widgets && this.makeButton("Widgets", this._txt("Widgets"),' icon-drawer', {
            hint: 1
        }), this.makeButton("Off", this._txt("HighlightOff"),' icon-code', {
            hint: 1,
            right: 1
        }), this.makeButton("Fullscreen", this._txt("FullScreen"), ' icon-expand',{
            right: 1
        }), this.makeThemeButton(), 
        $("#" + this.textarea_id).before(this.buttonPanel); 
        
        
        //$("#" + this.textarea_id).parent().width($(this.buttonPanel).parent().prev().width() - 2);
        var t = this;
        this.editor = CodeMirror.fromTextArea(document.getElementById(this.textarea_id), {
            mode: this.opts.mode,
            onChange: function() {
                t.onChangeText()
            },
            onCursorActivity: function() {
                t.editor.setLineClass(t.editor.hlLine, null), t.editor.hlLine = t.editor.setLineClass(t.editor.getCursor().line, "codepad-activeline")
            },
            indentUnit: 4,
            matchBrackets: !0,
            indentWithTabs: !0,
            enterMode: "indent",
            tabMode: "classic",
            lineNumbers: !0,
            theme: this.opts.theme || "default"
        }), this.editor.active = !0, this.editor.focus(), this.editor.hlLine = this.editor.setLineClass(0, "codepad-activeline"), this.codesContainer = document.querySelector("ul.uz")
    },
    _txt: function(t) {
        return this.translate[t] || t
    },
    /*makeSeparator: function(t) {
        var e = document.createElement("span");
        e.className = "codepad-separator", t && (e.style.width = parseInt(t) + "px"), this.buttonPanel.appendChild(e)
    },*/
    makeButton: function(t, e, icon, s) {
        icon = icon || '';
        s = s || {};
        var i = document.createElement("div");
        i.id = this.idPrefix + "button" + t, i.title = e;
        var n = "button codepad-button-" + t+icon+' icon-size2';
        
        
        
        
        s.disabled && (n += " disabled"), s.active && (n += " active"), i.className = n, s.right && (i.style.styleFloat = "right", i.style.cssFloat = "right"), 
        
        
        
        // при наведении
        i.onmouseout = function() {
            this.style.backgroundColor = ""
        }, i.onmouseover = function() {
            this.classList.contains("disabled") || (this.style.backgroundColor = "silver")
        }, i.onmousedown = function() {
            this.classList.contains("disabled") || (this.style.backgroundColor = "gray")
        }, i.onmouseup = function() {
            this.style.backgroundColor = ""
        };
        
        
        //var r = new Image;
        //if (r.src = "/.s/img/ma/1px.gif", r.alt = e, i.appendChild(r), s.hint) {
        //    var o = document.createElement("span");
        //    o.innerHTML = t, i.appendChild(o)
        //}
        //var o = document.createElement("span");
        if (o = document.createElement("span"), o.title = e, s.hint) {
            o.innerHTML = t, i.appendChild(o)
        }
        
        
        
        
        
        //<a href="#" class="button icon-star">Create</a>
        
        
        
        
        this.buttonPanel.appendChild(i);
        var a = $(i);
        this.button[t] = i, this.button["$" + t] = a, a.on("click", this, (function(e) {
            null != e.data.editor && (this.classList.contains("disabled") || e.data["button" + t + "Click"].call(e.data))
        }))
    },
    
    
    
    
    
    
    
    
    
    
    
    
    makeThemeButton: function() {
        var t = document.createElement("div");
        t.className = "icon-adjust icon-size2 float-right margin-right codepad-themebutton", t.title = this._txt("ChangeTheme"), $(t).on("click", this, (function(t) {
            null != t.data.editor && (this.classList.toggle("codepad-themebutton-night"), this.classList.contains("codepad-themebutton-night") ? t.data.editor.setOption("theme", "night") : t.data.editor.setOption("theme", editor.opts.theme || CodeMirror.defaults.theme), t.data.editor.focus())
        })), this.buttonPanel.appendChild(t)
    },
    buttonFileManagerClick: function() {
        window.open("/panel/?a=fm;n=1", "fman", "resizable=1,scrollbars=1,top=0,left=0")
    },
    buttonWYSIWYGClick: function() {
        this.getTxtCode(), window.open(this.opts.wysiwyg, "", "scrollbars=1,top=0,left=0,resizable=1")
    },
    buttonWidgetsClick: function() {
        new _uWnd("wnd_", " ", 600, 400, {
            closeonesc: 1,
            waitimages: 1,
            align: "left"
        }, {
            url: "/tmpls/?a=desget&l=widgets&tp=1&gn=1"
        })
    },
    buttonFullscreenClick: function(t) {
        
        this._fulscreenEnlargeForm(), this._fulscreenRepositionCodes(), this.editor.focus()
    },
    _fulscreenEnlargeForm: function() {
        let t = document.tmplForm?.classList.contains("tmpl-enlarged");
        document.tmplForm?.classList[t ? "remove" : "add"]("tmpl-enlarged"), self.codepad_buttonFullscreen && (self.codepad_buttonFullscreen?.classList[t ? "remove" : "add"]("icon-reduce"), self.codepad_buttonFullscreen.title = this._txt(t ? "FullScreen" : "FullScreenBack"), this.codesContainer?.classList[t ? "remove" : "add"]("codes-collapsed")), this.editor.refresh()
    },
    _fulscreenRepositionCodes: function() {
        
        
        
        this.codesContainer?.classList.toggle("like-sidebar"), 
        self.tmplCodesToggler || this.codesContainer?.insertAdjacentHTML("beforeend", `<div id=tmplCodesToggler class="tmpl-codes-toggler" >${this._txt("SidebarCodes")}</div>`),
        self.tmplCodesToggler.onclick = function(t) {
            
            t.target.parentElement.classList.toggle("codes-collapsed"), this.editor.focus(), this.editor.refresh()
        }.bind(this)
    },
    buttonOffClick: function() {
        var t = window.location.pathname + window.location.search;
        t = t.replace(/;?hloff=\d+/, ""), window.location.href = t + ";hloff=1"
    },
    
    
    
    
    
    
    
    
    dsMdl: function(title, content, onClose, onOpen) {
            $.modal({
                title: title,
                content: content,
                //beforeContent: '<div class="carbon">',
                //afterContent: '</div>',
                //buttonsAlign: 'center',
                
                onClose: onClose, //onClose
                onOpen: onOpen, //Default: false
                
                resizable: false
            });
    },
    
    
    
    
    
    buttonSearchClick: function() {
        this.buttonReplaceClick()
    },
    buttonReplaceClick: function() {
        if (!this.srwnd) {
            var t = this,
                c = `<div class="codepad-searchForm">\n\t\t\t
                <table style="width:100%;border-collapse:collapse;border:0">\n\t\t\t\t
                <tr><td quarter-width nowrap >${this._txt("FinW")}:</td>\n\t\t\t\t\t
                <td><input type="text" id="${this.idPrefix}searchWord"  value="${this.searches.query?this.searches.query:""}" onfocus="if (this.defaultValue==this.value) this.select()" full-width ></td></tr>\n\n\t\t\t\t
                <tr><td quarter-width nowrap >${this._txt("RepW")}:</td>\n\t\t\t\t\t
                <td><input type="text" id="${this.idPrefix}replaceWord" value="${this.searches.replQuery?this.searches.replQuery:""}" onfocus="if (this.defaultValue==this.value) this.select()" full-width ></td></tr>\n\t\t\t
                </table>\n\n\t\t\t
                <div flex-between half-gap half-break-before >\n\t\t\t\t
                <fieldset flex-column flex-grow1 quarter-gap ><legend>${this._txt("Begin")}</legend>\n\t\t\t\t\t
                <label flex-align-center quarter-gap >\n\t\t\t\t\t\t
                <input type="radio" name="beginFrom" value="1" id="${this.idPrefix}beginFromCur" ${this.searches.beginFrom?"checked":""} onchange="var w=editor;if (w) {w.searches.newSearch=true;}">${this._txt("BeginFC")}</label>\n\t\t\t\t\t
                <label flex-align-center quarter-gap >\n\t\t\t\t\t\t
                <input type="radio" name="beginFrom" ${this.searches.beginFrom?"":"checked"} onchange="var w=editor;if (w) {w.searches.newSearch=true;}">${this._txt("BeginFB")}</label>\n\t\t\t\t
                </fieldset>\n\t\t\t\t<fieldset flex-column flex-grow1 quarter-gap ><legend>${this._txt("Options")}</legend>\n\t\t\t\t\t
                <label flex-align-center quarter-gap >\n\t\t\t\t\t\t
                <input type="checkbox" value="1" id="${this.idPrefix}caseSensitive" ${this.searches.caseSensitive?"checked":""}>${this._txt("MatC")}</label>\n\t\t\t\t\t
                <label flex-align-center quarter-gap >\n\t\t\t\t\t\t
                <input type="checkbox" value="1" id="${this.idPrefix}regExp"        ${this.searches.regExp?"checked":""}>${this._txt("RegExp")}</label>\n\t\t\t\t
                </fieldset>\n\t\t\t</div>\n\t\t</div>`+
                '<div flex-end="" quarter-gap="" half-break-before="">'+
                '<button class="ubtn-default " type="button" onclick="editor.searchWord();" style="padding:0 20px">'+this._txt("SearBut")+'</button>&nbsp;'+
                '<button class="ubtn-default light-btn" type="button" onclick="editor.replaceWord(false);">'+this._txt("RepBut")+'</button>'+
                '<button class="ubtn-default light-btn" type="button" onclick="editor.replaceWord(true);">'+this._txt("RepAllBut")+'</button>&nbsp;'+
                '</div>';
                
                //console.log(m.app._txt("RepAllBut"));
                
                
                
                //this.srwnd = new _uWnd(this),
                
                
                    this.srwnd = this.dsMdl(this._txt("SearRepl"),// title
                    c, // content
                    function() {t.searches.newSearch = !0, delete t.searches.query, delete t.searches.replQuery, this.srwnd = null, t.editor.focus()}, // onClose
                    function() {$("#" + t.idPrefix + "searchWord").focus()} //onOpen
                    )
                
                
                
                
                
            
            //t.call(this, s)
            
            
            
            
            
            
            
            
            
            /*
            this.srwnd = new _uWnd("srwnd", this._txt("SearRepl"), -385, -138, {
                resize: 0,
                fadeclosetype: 0,
                closeonesc: 1,
                max: 0,
                autosize: 1,
                oncontent: function() {
                    $("#" + this.idPrefix + "searchWord", this.srwnd.wnd).focus()
                },
                onclose: function() {
                    this.searches.newSearch = !0, delete this.searches.query, delete this.searches.replQuery, this.srwnd = null, this.editor.focus()
                }
            }, t + e, null, this)
            
            */
            
            
            
            
            
            
            
            
            
            
        }
    },
    
    
    
    
    
    
    
    
    
    
    
    
    searchWord: function() {
        var t = $("#" + this.idPrefix + "searchWord"/*, this.srwnd.wnd*/).val();
        if (t.length) {
            if (this.checkOptions(), (this.searches.query != t || this.searches.newSearch) && (this.searches.pos = this.searches.beginFrom ? this.editor.getCursor() : null), this.searches.query = t, this.searches.regExp) try {
                t = new RegExp(t, "i")
            } catch (t) {
                return _uWnd.messageBox(this._txt("RegExpIncorrect"), this._txt("Search"), [{
                    id: "ok",
                    t: this._txt("Ok"),
                    def: 3
                }], null, {
                    w: -280,
                    h: -100,
                    parent: this.srwnd
                }), !1
            }
            this.cursor = this.editor.getSearchCursor(t, this.searches.pos, !this.searches.caseSensitive), this.cursor.findNext() ? (this.editor.setSelection(this.cursor.from(), this.cursor.to()), this.searches.pos = this.cursor.to(), this.searches.newSearch = !1) : 
            
            
            
            /*
            
            _uWnd.messageBox(this._txt("NotFound"), this._txt("Search"), [{
                id: "ok",
                t: this._txt("Ok"),
                def: 3
            }], null, {
                w: -280,
                h: -100,
                parent: this.srwnd
            })
            */
            
            
            this.dsMdl(
                    this._txt("Search"),// title
                    this._txt("NotFound") // content
                )
            
            
            
            
            
            
            
            
            
        }
    },
    
    
    
    
    
    
    
    
    
    replaceWord: function(t) {
        var e = $("#" + this.idPrefix + "searchWord"/*, this.srwnd.wnd*/).val(),
            s = $("#" + this.idPrefix + "replaceWord"/*, this.srwnd.wnd*/).val();
        if (e.length)
            if (this.checkOptions(), (this.searches.query != e || this.searches.newSearch) && (this.searches.pos = this.searches.beginFrom ? this.editor.getCursor() : null, this.cursor = null), this.searches.query = e, this.searches.replQuery = s, t) {
                if (this.searches.regExp) try {
                    e = new RegExp(e, "i")
                } catch (t) {
                    return _uWnd.messageBox(this._txt("RegExpIncorrect"), this._txt("Search"), [{
                        id: "ok",
                        t: this._txt("Ok"),
                        def: 3
                    }], null, {
                        w: -280,
                        h: -100,
                        parent: this.srwnd
                    }), !1
                }
                var i = 0;
                for (this.cursor = this.editor.getSearchCursor(e, this.searches.beginFrom ? this.editor.getCursor() : 0, !this.searches.caseSensitive); this.cursor.findNext();) i++, this.editor.replaceRange(s, this.cursor.from(), this.cursor.to()), this.searches.pos = this.cursor.from(), this.searches.pos.ch += s.length, this.cursor = this.editor.getSearchCursor(e, this.searches.pos, !this.searches.caseSensitive);
                i ? (
                    
                    /*
                    _uWnd.messageBox(this._txt("MadeRep"), this._txt("Replace"), [{
                    id: "ok",
                    t: this._txt("Ok"),
                    def: 3
                }], null, {
                    w: -280,
                    h: -100,
                    parent: this.srwnd
                })
                */
                this.dsMdl(
                    this._txt("Replace"),// title
                    this._txt("MadeRep") // content
                )
                
                
                
                
                
                , this.editor.setCursor(this.searches.pos)) : 
                
                
                
                
               /* _uWnd.messageBox(this._txt("NotFound"), this._txt("Search"), [{
                    id: "ok",
                    t: this._txt("Ok"),
                    def: 3
                }], null, {
                    w: -280,
                    h: -100,
                    parent: this.srwnd
                })*/
                this.dsMdl(
                    this._txt("Search"),// title
                    this._txt("NotFound") // content
                )
                
                
                
                
                
                
                
                
            } else this.editor.getSelection() != e || this.cursor ? this.cursor && this.cursor.from() != this.cursor.to() && (this.editor.replaceRange(s, this.cursor.from(), this.cursor.to()), this.searches.pos = this.cursor.from(), this.searches.pos.ch += s.length) : (this.editor.replaceSelection(s), this.searches.pos && (this.searches.pos.ch += s.length)), this.editor.somethingSelected() && this.editor.setCursor(this.editor.getCursor(!0)), this.searchWord()
    },
    
    checkOptions: function() {
        this.searches.beginFrom = $("#" + this.idPrefix + "beginFromCur:checked"/*, this.srwnd.wnd*/).length, this.searches.caseSensitive = $("#" + this.idPrefix + "caseSensitive:checked"/*, this.srwnd.wnd*/).length, this.searches.regExp = $("#" + this.idPrefix + "regExp:checked"/*, this.srwnd.wnd*/).length
    },
    buttonReindentClick: function() {
        for (var t = this.editor.lineCount(), e = 0; e < t; e++) this.editor.indentLine(e)
    },
    buttonUndoClick: function() {
        this.editor.undo(), this.editor.focus()
    },
    buttonRedoClick: function() {
        this.editor.redo(), this.editor.focus()
    },
    buttonMacroClick: function() {
        var t = prompt("Name your function:", "");
        t && this.editor.replaceSelection("function " + t + "() {\n  \n}\n")
    },
    buttonLineNumbersClick: function() {
        this.button.$LineNumbers.toggleClass("active"), this.editor.setOption("lineNumbers", this.button.$LineNumbers.hasClass("active")), this.editor.focus()
    },
    getTxtCode: function() {
        $("#" + this.textarea_id).val(this.editor.getValue())
    },
    setTxtCode: function(t) {
        $("#" + this.textarea_id).val(t), this.editor.focus(), this.editor.setValue(t), this.onChangeText()
    }
};
