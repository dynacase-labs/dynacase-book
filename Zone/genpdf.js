var XHT_FILES;

function verifycomputedfiles(docid) {
  var corestandurl=window.location.pathname+'?sole=Y';
  // branch for native XMLHttpRequest object
  if (window.XMLHttpRequest) {
    XHT_FILES = new XMLHttpRequest(); 
  } else if (window.ActiveXObject) {
    // branch for IE/Windows ActiveX version     
    XHT_FILES = new ActiveXObject("Microsoft.XMLHTTP");
  }
  if (XHT_FILES) {     
    XHT_FILES.onreadystatechange = XMLprocessverificationfiles;
    XHT_FILES.open("GET", corestandurl+'&app=FDL&action=VERIFYCOMPUTEDFILES&id='+docid,true);   
    XHT_FILES.send('');
  }  	
  return true;  
}

function XMLprocessverificationfiles() {  
  if (XHT_FILES.readyState == 4) {    
    if (XHT_FILES.status == 200) {
      // ...processing statements go here...
      if (XHT_FILES.responseXML) {
	var xmlres=XHT_FILES.responseXML;
	var elts = xmlres.getElementsByTagName("status");
	if (elts.length == 1) {
	  var elt=elts[0];
	  var code=elt.getAttribute("code");
	  var docid=elt.getAttribute("docid");
	  var w=elt.getAttribute("warning");
	  
	  if (w != '') alert(w);
	  if (code != 'OK') {
	    alert('code not OK\n'+req.responseText);
	    return;
	  }
	  var values=xmlres.getElementsByTagName("file");
	  var needverify=false;
	  var state;
	  for (var i=0;i<values.length;i++) {
	    state=values[i].getAttribute('status');
	    if (state=='2') needverify=true;	    
	  }
	  if (needverify) {
	    var so=document.getElementById('counter');
	    if (so) so.innerHTML=so.innerHTML+'.',
	    setTimeout(function() { verifycomputedfiles(docid) }, 2000);
	  } else {
	    alert('Fichier PDF produit');
	    if (window.opener) {
	      window.opener.location.href=window.opener.location.href;
	      setTimeout('self.close()',1000);
	    }
	  }
	}
      }


    } else {
      alert("There was a problem retrieving the XML data:\n" +
	    XHT_FILES.statusText+' code :'+XHT_FILES.status);
      return;
    }
  } 
}
