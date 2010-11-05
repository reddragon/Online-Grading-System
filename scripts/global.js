var initList = new Array()
var GET = new Array();
var newsInterval = 10*60;
var newsReq;

function addInit(func) {
	if (func)
		initList[initList.length] = func;
}

function init() {
	var i;
	for (i=0; i<initList.length; ++i) {
		initList[i]();
	}
}

function parseQueryString() {
    var query = window.location.search.substring(1);
    var parms = query.split('&');
    for (var i=0; i<parms.length; i++) {
    var pos = parms[i].indexOf('=');
    if (pos > 0) {
            var key = parms[i].substring(0,pos);
            var val = parms[i].substring(pos+1);
            GET[key] = val;
        }
    }
}
parseQueryString();

function getObj(id,d) {
    var i,x;
    if(!d) d=document; 
    if(!(x=d[id])&&d.all) x=d.all[id]; 
    for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][id];
    for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=getObj(id,d.layers[i].document);
    if(!x && document.getElementById) x=document.getElementById(id); 
    return x;
}

// for use with anchor.onclick
function newWindow(url) {
    var win = window.open(url, "", "width=500,height=300,scrollbars=yes,resizable=yes");
    return false;
}

function asyncRequest(url, post, handler) {
    var req;

    // branch for native XMLHttpRequest object
    if (window.XMLHttpRequest) {
        req = new XMLHttpRequest();
    // branch for IE/Windows ActiveX version
    } else if (window.ActiveXObject) {
        req = new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    if (req) {
        req.onreadystatechange = handler;
        req.open("POST", url, true);
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		req.send(post);
        return req;
    } else {
        alert("Failed to create XMLHttpRequest object. Please update your browser!");
        return null;
    }
}

function createCookie(name,value,days)
{
	if (days)
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name)
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++)
	{
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name)
{
	createCookie(name,"",-1);
}

