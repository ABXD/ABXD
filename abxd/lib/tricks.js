/* Spoiler buttons for posts
   -------------------------
   Used to be a simple one-way trick.
 */
function toggleSpoiler(obj)
{
	var button = obj.children[0];
	var div = obj.children[1];

	if(div.className == "spoiled")
	{
		if(button.className != "named")
			button.textContent = "Show spoiler";
		div.className = "spoiled hidden";
	}
	else
	{
		if(button.className != "named")
			button.textContent = "Hide spoiler";
		div.className = "spoiled";
	}
}



/* Quote support
   -------------
   Thanks to Mega-Mario for the idea
 */
function insertQuote(pid)
{
	$.get("ajaxcallbacks.php", "a=q&id="+pid, function(data)
	{
		var editor = $("#text")[0]; //we want the HTMLTextElement kthx
		editor.focus();
		if (document.selection)
			document.selection.createRange().text += data;
		else
			editor.value = editor.value.substring(0, editor.selectionEnd) + data + editor.value.substring(editor.selectionEnd, editor.value.length);
		editor.scrollTop = editor.scrollHeight;
	});
}

function insertChanLink(pid)
{
	var editor = document.getElementById("text");
	var linkText = ">>" + pid + "\r\n";
	editor.focus();
	if (document.selection)
		document.selection.createRange().text += linkText;
	else
		editor.value = editor.value.substring(0, editor. selectionEnd) + linkText + editor.value.substring(editor.selectionEnd, editor.value.length);
	editor.scrollTop = editor.scrollHeight;
}




/* Smiley tricks
   -------------
   Inspired by Mega-Mario's quote system.
 */
function insertSmiley(smileyCode)
{
	var editor = document.getElementById("text");
	editor.focus();
	if (document.selection)
	{
		document.selection.createRange().text += " " + smileyCode;
	}
	else
	{
		editor.value = editor.value.substring(0, editor. selectionEnd) + smileyCode + editor.value.substring(editor.selectionEnd, editor.value.length);
	}
	editor.scrollTop = editor.scrollHeight;
}
function expandSmilies()
{
	var button = document.getElementById("smiliesExpand");
	var expandedSet = $("#expandedSet");
	if(expandedSet.is(":hidden"))
	{
		expandedSet.slideDown(200, function()
		{
			button.textContent = String.fromCharCode(0x25B2);
		});
	}
	else
	{
		expandedSet.slideUp(200, function()
		{
			button.textContent = String.fromCharCode(0x25BC);
		});
	}
}

function expandPostHelp()
{
	var button = document.getElementById("postHelpExpand");
	var expandedSet = $("#expandedHelp");

	if(expandedSet.is(":hidden"))
	{
		expandedSet.slideDown(700, function()
		{
			button.textContent = String.fromCharCode(0x25B2);
		});
	}
	else
	{
		expandedSet.slideUp(700, function()
		{
			button.textContent = String.fromCharCode(0x25BC);
		});
	}
}



/* Bare metal AJAX support functions
   ---------------------------------
   Press button, recieve content.
 */
var xmlHttp = null; //Cache our request object

// is this really used anymore now that we mostly use jQuery?
function GetXmlHttpObject()
{
	//If we already have one, just return that.
	if (xmlHttp != null) return xmlHttp;
	xmlHttp = new XMLHttpRequest();
	return xmlHttp;
}


/* Flashloops */
function startFlash(id)
{
	var url = document.getElementById("swf" + id + "url").innerHTML;
	var mainPanel = document.getElementById("swf" + id + "main");
	var playButton = document.getElementById("swf" + id + "play");
	var stopButton = document.getElementById("swf" + id + "stop");
	mainPanel.innerHTML = '<object data="' + url + '" style="width: 100%; height: 100%;"><embed src="' + url + '" style="width: 100%; height: 100%;"></embed></object>';
	playButton.className = "swfbuttonon";
	stopButton.className = "swfbuttonoff";
}
function stopFlash(id)
{
	var mainPanel = document.getElementById("swf" + id + "main");
	var playButton = document.getElementById("swf" + id + "play");
	var stopButton = document.getElementById("swf" + id + "stop");
	mainPanel.innerHTML = '';
	playButton.className = "swfbuttonoff";
	stopButton.className = "swfbuttonon";
}



function startPoraUpdate()
{
	var ta = document.getElementById("editbox");
	var tt = document.getElementById("title");
	var prt = document.getElementById("previewtext");
	var pri = document.getElementById("previewtitle");

	prt.innerHTML = ta.value;//.replace("\n", "<br />");
	pri.textContent = tt.value;
	//setTimeout("startPoraUpdate();", 100);
}


var onlineFID = 0;

function startOnlineUsers()
{
	//onlineFID = fid;
	//setTimeout("getOnlineUsers()", 10000);
	//var onlineUsersBar = $('.header0').get(1);
	//onlineUsersBar.id="onlineUsersBar";
	var tmrid = window.setInterval("getOnlineUsers();", 10000);
	
	$(window).blur(function() {if (tmrid != -9999) {window.clearInterval(tmrid); tmrid = -9999;}});
	$(window).focus(function() {if (tmrid == -9999) tmrid = window.setInterval("getOnlineUsers();", 10000);});
}

function getOnlineUsers()
{
	//$("#onlineUsers").load("ajaxcallbacks.php", "a=ou&f=" + onlineFID + "&salt=" + Date())
	//$("#viewCount").load("ajaxcallbacks.php", "a=vc&f=" + onlineFID + "&salt=" + Date())
	$.get("ajaxcallbacks.php", "a=vc", function(data)
	{
	    var viewCount = $("#viewCount");
	    var oldCount = viewCount[0].innerHTML;
	    if(oldCount != data)
	    {
			viewCount.html(data);
		}
	});
	$.get("ajaxcallbacks.php", "a=ou&f=" + onlineFID, function(data)
	{
	    var onlineUsers = $("#onlineUsers");
	    var oldOnline = onlineUsers[0].innerHTML;
	    if(oldOnline != data)
	    {
			onlineUsers.html(data);
		}
	});
}


function showEditProfilePart(newId)
{
	tables = document.getElementsByClassName('eptable');
	for (i=0;i<tables.length;i++) {
		tables[i].style.display = "none";
	}
	document.getElementById(newId).style.display = "table";
	tabs = document.getElementsByClassName('tab');
	for (i=0;i<tabs.length;i++) {
		tabs[i].className = "tab";
	}
	document.getElementById(newId+"Button").className = "tab selected";
}

var textEditor;
function hookUpControls()
{
	//Now functional!
	textEditor = document.getElementById("text");
	textEditor.addEventListener("keypress", HandleKey, true);
	ConstructToolbar();
}

function ConstructToolbar()
{
	var toolbar = document.createElement("DIV");
	toolbar.className = "postToolbar";

	var buttons =
	[
		{ label: "B", title: "Bold", style: "font-weight: bold", insert: "b" },
		{ label: "I", title: "Italic", style: "font-style: italic", insert: "i" },
		{ label: "U", title: "Underlined", style: "text-decoration: underline", insert: "u" },
		{ label: "S", title: "Strikethrough", style: "text-decoration: line-through", insert: "s" },
		{ label: "-" },
		{ label: "x&#x00B2;", title: "Superscript", insert: "sup", html: true },
		{ label: "x&#x2082;", title: "Subscript", insert: "sub", html: true },
		//{ label: "A", title: "Big", insert: "big", html: true },
		//{ label: "a", title: "Small", insert: "small", html: true },
		{ label: "-" },
		{ label: "url", title: "Link", style: "color: blue; text-decoration: underline", insert: "url" },
		{ label: "<img src=\"img/stdimg.png\" style=\"height: 0.9em;\" />", title: "Image", insert: "img" },
		{ label: "-" },
		{ label: "&ldquo; &rdquo;", title: "Quote", insert: "quote" },
		{ label: "&hellip;", title: "Spoiler", style: "opacity: 0.25", insert: "spoiler" },
		//{ label: "abc", title: "Insert code block", style: "font-family: monospace", insert: "code" },

	];

	for(var i = 0; i < buttons.length; i++)
	{
		var button = buttons[i];
		if(button.label == "-")
		{
			toolbar.innerHTML += " ";
			continue;
		}
		var newButton = "<button ";
		if (button.title != undefined)
			newButton += "title=\"" + button.title + "\" ";
		newButton += "onclick=\"Insert('" + button.insert + "', " + button.html + "); return false;\">";
		if (button.style != undefined)
			newButton += "<span style=\"" + button.style + "\">";
		newButton += button.label;
		if (button.style != undefined)
			newButton += "</span>";
		newButton += "</button>";
		toolbar.innerHTML += newButton;
	}

	textEditor.parentNode.insertBefore(toolbar, textEditor);
}
function HandleKey()
{
	if(event.ctrlKey && !event.altKey)
	{
		var charCode = event.charCode ? event.charCode : event.keyCode;
		var c = String.fromCharCode(charCode).toLowerCase();
		if (c == "b" || c == "i" || c == "u")
		{
			textEditor.focus();
			Insert(c);
			event.preventDefault();
			return false;
		}
	}
}
function Insert(stuff, html)
{
	var oldSelS = textEditor.selectionStart;
	var oldSelE = textEditor.selectionEnd;
	var scroll = textEditor.scrollTop;
	var selectedText = textEditor.value.substr(oldSelS, oldSelE - oldSelS);

	if(html)
		textEditor.value = textEditor.value.substr(0, oldSelS) + "<" + stuff + ">" + selectedText + "</" + stuff + ">" + textEditor.value.substr(oldSelE);
	else
		textEditor.value = textEditor.value.substr(0, oldSelS) + "[" + stuff + "]" + selectedText + "[/" + stuff + "]" + textEditor.value.substr(oldSelE);

	textEditor.selectionStart = oldSelS + stuff.length + 2;
	textEditor.selectionEnd = oldSelS + stuff.length + 2 + selectedText.length;
	textEditor.scrollTop = scroll;
	textEditor.focus();
}




function startNewMarkers()
{
	getNewMarkers();
	
	var tmrid = window.setInterval("getNewMarkers();", 30000);
	
	$(window).blur(function() {if (tmrid != -9999) {window.clearInterval(tmrid); tmrid = -9999;}});
	$(window).focus(function() {if (tmrid == -9999) tmrid = window.setInterval("getNewMarkers();", 30000);});
}

function getNewMarkers()
{
	xmlHttp = GetXmlHttpObject();
	xmlHttp.onreadystatechange = function()
	{
		if(xmlHttp.readyState == 4)
		{
			var news = xmlHttp.responseText.split(',');
			var pic = "<img src=\"img/status/new.png\" alt=\"New!\"/>";

			var allTDs = document.getElementsByTagName("TD");
			var j = 0;
			for (var i = 0; i < allTDs.length; i++)
			{
				var classes = allTDs[i].getAttribute("class");
				if (classes != null && classes.indexOf("newMarker") > -1)
				{
					if(news[j] > 0)
						allTDs[i].innerHTML = pic + news[j];
					else
						allTDs[i].innerHTML = "";
					j++;
				}
			}

			//startNewMarkers();
		}
	};
	xmlHttp.open("GET", "ajaxcallbacks.php?a=ni&salt=" + Date(), true);
	xmlHttp.send(null);

}



// Live theme changer by Mega-Mario
function ChangeTheme(newtheme)
{
	$.get("ajaxcallbacks.php", "a=tf&t="+newtheme, function(data)
	{
		var stuff = data.split('|');
		$("#theme_css")[0].href = stuff[0];
		$("#theme_banner")[0].src = stuff[1];
	});
}



//Search page pager
function ChangePage(newpage)
{
        var pagenums = document.getElementsByClassName('pagenum');
        for (i = 0; i < pagenums.length; i++)
                pagenums[i].href = '#';

        pagenums = document.getElementsByClassName('pagenum'+newpage);
        for (i = 0; i < pagenums.length; i++)
                pagenums[i].removeAttribute('href');

        var pages = document.getElementsByClassName('respage');
        for (i = 0; i < pages.length; i++)
                pages[i].style.display = 'none';

        pages = document.getElementsByClassName('respage'+newpage);
        for (i = 0; i < pages.length; i++)
                pages[i].style.display = '';
}




function expandTable(tableName, button)
{
	var table = document.getElementById(tableName);
	var rows = table.getElementsByTagName("tr");

	for(var i = 0; i < rows.length; i++)
	{
		//alert(rows[i].className + ", " + rows[i].style['display']);
		if(rows[i].className == "header1")
			continue;

		if(rows[i].style['display'] == "none")
			rows[i].style['display'] = "";
		else
			rows[i].style['display'] = "none";
	}
}

function hideTricks(pid)
{
	$("#dyna_"+pid).hide(200);//, function()
	$("#meta_"+pid).show(200);
}

function showRevisions(pid)
{
	$("#meta_"+pid).hide(200);//, function()
	$("#dyna_"+pid).load("ajaxcallbacks.php", "a=srl&id="+pid, function()
	{
		$("#dyna_"+pid).show(200);
	});
}

function showRevision(pid, rev)
{
	var post = $("#post_"+pid);
	$.get("ajaxcallbacks.php", "a=sr&id="+pid+"&rev="+rev, function(data)
	{
		post.fadeOut(200, function()
		{
			post[0].innerHTML = data;
			post.fadeIn(200);
		});
	});
}

function checkAll()
{
	var ca = document.getElementById("ca");
	var checked = ca.checked;
	var checks = document.getElementsByTagName("INPUT");
	for(var i = 0; i < checks.length; i++)
		checks[i].checked = checked;
}


function hookUploadCheck(id, type, size)
{
	var obj = document.getElementById(id);
	if(type == 0)
	{
		obj.onchange = function()
		{
			var submit = document.getElementById("submit");
			var sizeWarning = document.getElementById("sizeWarning");
			var typeWarning = document.getElementById("typeWarning");

			submit.disabled = (obj.value == "");

			if(obj.files != undefined)
			{
				var file = obj.files[0];
				var fileSize = 0;
				if(file != undefined)
					fileSize = file.size;
				sizeWarning.style['display'] = (fileSize > size) ? "inline" : "none";
				submit.disabled = (fileSize > size);
				if(file != undefined)
				{
					switch(file.type)
					{
						case "image/jpeg":
						case "image/png":
						case "image/gif":
							typeWarning.style['display'] = "none";
							break;
						default:
							typeWarning.style['display'] = "inline";
							submit.disabled = true;
					}
				}
			}
		};
	}
	else if(type == 1)
	{
		obj.onchange = function()
		{
			var submit = document.getElementById("submit");
			var sizeWarning = document.getElementById("sizeWarning");
			var typeWarning = document.getElementById("typeWarning");

			submit.disabled = (obj.value == "");
			if(obj.files != undefined)
			{
				var file = obj.files[0];
				var fileSize = 0;
				if(file != undefined)
					fileSize = file.size;
				sizeWarning.style['display'] = (fileSize > size) ? "inline" : "none";
				submit.disabled = (fileSize > size);
				if(file != undefined)
				{
					switch(file.type)
					{
						case "application/x-msdownload":
						case "text/html":
							typeWarning.style['display'] = "inline";
							submit.disabled = true;
							break;
						default:
							typeWarning.style['display'] = "none";
					}
				}
			}
		};
	}
}

fid = 0;
hint = true;
function pickForum(id) {
	if (hint == true) {
		$("#hint").remove();
		hint = false;
	}
	$(".f, .c").removeClass("fe_selected");
	$("#forum"+id).addClass("fe_selected");
	if ($("#editcontent").is(":hidden")) $("#editcontent").slideDown(250);
	$("#editcontent").load('./editfora.php?editforum='+id);
	fid = id;
}

function changeForumInfo(forum)
{
	//<Kawa> Okay, let's see what we got here...
	xmlHttp = GetXmlHttpObject();
	var title = document.getElementById("title").value;
	var description = document.getElementById("description").value;
	var category = document.getElementById("category").value;
	var forder = document.getElementById("forder").value;
	var minpower = document.getElementById("minpower").value;
	var minpowerthread = document.getElementById("minpowerthread").value;
	var minpowerreply = document.getElementById("minpowerreply").value;

	var data = "updateforum=1&id="+forum+"&title="+encodeURIComponent(title)+"&description="+encodeURIComponent(description)+"&forder="+forder+"&category="+category+"&minpower="+minpower+"&minpowerthread="+minpowerthread+"&minpowerreply="+minpowerreply;
	xmlHttp.onreadystatechange = function()
	{
		if(xmlHttp.readyState == 4)
		{
			if(xmlHttp.responseText.indexOf("Change OK") != -1)
				$("#flist").load(editforaurl+"forumTable&s"+forum);
			else
				alert("Something went wrong.");
		}
	}
	xmlHttp.open("GET", "editfora.php?" + data, true);
	xmlHttp.send(null);
}

function deleteForum(fid)
{
	xmlHttp = GetXmlHttpObject();
	xmlHttp.onreadystatechange = function()
	{
		if(xmlHttp.readyState == 4)
		{
			if(xmlHttp.responseText.indexOf("Deleted OK") != -1)
			{
				$("#flist").load(editforaurl+"forumTable");
				document.getElementById("editcontent").textContent = "";
			}
			else
				alert("Something went wrong." + xmlHttp.responseText);
		}
	}
	xmlHttp.open("GET", "editfora.php?deleteforum="+fid, true);
	xmlHttp.send(null);
}

function addForum()
{
	xmlHttp = GetXmlHttpObject();
	var title = document.getElementById("title").value;
	var description = document.getElementById("description").value;
	var category = document.getElementById("category").value;
	var forder = document.getElementById("forder").value;
	var minpower = document.getElementById("minpower").value;
	var minpowerthread = document.getElementById("minpowerthread").value;
	var minpowerreply = document.getElementById("minpowerreply").value;

	var data = "addforum=2&title="+encodeURIComponent(title)+"&description="+encodeURIComponent(description)+"&forder="+forder+"&category="+category+"&minpower="+minpower+"&minpowerthread="+minpowerthread+"&minpowerreply="+minpowerreply;
	xmlHttp.onreadystatechange = function()
	{
		if(xmlHttp.readyState == 4)
		{
			if(xmlHttp.responseText.indexOf("Added OK") != -1)
			{
				$("#flist").load(editforaurl+"forumTable");
				document.getElementById("editcontent").textContent = "";
			}
			else
				alert("Something went wrong.");
		}
	}
	xmlHttp.open("GET", "editfora.php?" + data, true);
	xmlHttp.send(null);
}

function ReplacePost(id, opened)
{
	$.get("ajaxcallbacks.php?a=rp"+(opened ? "&o":"")+"&id="+id, function(data)
	{
		$("#post"+id).replaceWith(data);
	});
}



