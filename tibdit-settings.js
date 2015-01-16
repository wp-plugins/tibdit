// v1.2.34

function payaddr_change( f, plugurl)
{
  base54_clean( f);
  valid = "132mn";

  if( f.value === "")
  {
    payaddr_field_status.innerHTML="&emsp;&#10068;";
    payaddr_field_status.style.color='orange';
    submit.disabled = false;
  }
  else if( valid.indexOf(f.value.substr(0, 1)) == -1)
  {
    payaddr_field_status.innerHTML="&emsp;&cross;";
    payaddr_field_status.style.color='red';
    submit.disabled = true;
  }
  else if( f.value.length < 26)  // too short
  {
    payaddr_field_status.innerHTML="&emsp;&quest;";
    payaddr_field_status.style.color='blue';
    submit.disabled = true;
  }
  else if (check_address(f.value))  
  {
    payaddr_field_status.innerHTML="&emsp;&check;&nbsp;";
    payaddr_field_status.style.color='green';
    submit.disabled = false;
  }
  else
  {
    payaddr_field_status.innerHTML="&emsp;&cross;"; // long enough but invalid
    payaddr_field_status.style.color='orange';
    submit.disabled = true;
  }

  if( valid.indexOf(f.value.substr(0, 1)) >= 2 )
  {
    payaddr_field_status.innerHTML=
      payaddr_field_status.innerHTML.concat("&ensp;<img src='" + plugurl + "/testmode-icon-24px.png' style='width: 1em; vertical-align: middle'>");
  }
}


function base54_clean( f) 
{
  ss = f.selectionStart;
  se = f.selectionEnd;
  f.value = f.value.replace(/[^A-HJ-NP-Za-km-z1-9]/g,"");
  f.setSelectionRange(ss,se);
}


function lowercase_tib( f)
{
  ss = f.selectionStart;
  se = f.selectionEnd;
  f.value = f.value.replace( /([Tt][iI][bB])([^ACE-Zace-z][\w]*|$)/g, function(tibword) { return tibword.toLowerCase(); } );
  f.setSelectionRange(ss,se);
}


// function hidetooltips()
// {
//   document.styleSheets[0].cssRules[0].cssText = 
//     ".tooltip { display: none; }";
// }

