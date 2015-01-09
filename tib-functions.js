// version 1.2.32

var tibWindow = null;
var tibWindowCheck = null;

function tib(payaddr, subref) // open tibdit window, watch for closing
{ 
	tibWindow= window.open("https://tib.tibdit.com/t/" + payaddr + "/" + subref + "?callback_url=" + encodeURIComponent(window.location) ,
		"tibdit", "height=600,width=700,menubar=no,location=no,resizable=no,status=no");
    tibWindowCheck= setInterval(function(){tibbedReload();}, 1000);   // 100);    
    tibWindow.focus();
}

function tibbedReload() // reload on tibdit window close
{
	if (tibWindow.closed) 
	{
		clearInterval(tibWindowCheck);
		location.reload();
	} 
}

function getCookie(cname)   // get the cookie based on a name
{
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) 
	{
		var c = ca[i];
		while (c.charAt(0)==' ') { c = c.substring(1); }
		if (c.indexOf(name) != -1) { return c.substring(name.length,c.length); }
	}
	return "";
}

function tibbed(subref)  // tell me if there are cookies
{
	return (getCookie('tibbed_'+subref)) ? true : false;
}

function setCookie(lifespan, subref)    // set the tibbed cookie and give it a lifespan (minutes / days)
{
  var expires = new Date();
  expires.setTime(expires.getTime() + lifespan * 60 * 1000 ); // -> microseconds

  document.cookie = 'tibbed_'+ subref + '=true;expires=' + expires.toUTCString() +'"';
}


function lowercase_tib( f)
{
  ss = f.selectionStart;
  se = f.selectionEnd;
  f.value = f.value.replace( /([Tt][iI][bB])([^ACE-Zace-z][\w]*|$)/g, function(tibword) { return tibword.toLowerCase(); } );
  f.setSelectionRange(ss,se);
}


function anytibbedcookies()
{
	var tibsfound = document.cookie.search('tibbed');
	if (tibsfound == -1)
	{
		var buttons = document.getElementsByClassName("tooltip");
		for( i=0; i<buttons.length; i++)
		{
			buttons[i].className += " show"; 
			console.log(buttons[i]);
		}
		return true;
	}
	return false;
}
