//
// formats a date
//
function formatDate(dateStr) {
	var date = new Date(dateStr);
	var year = date.getFullYear();
	var month = (1 + date.getMonth()).toString().padStart(2, '0');
	var day = date.getDate().toString().padStart(2, '0');
	var hour = (date.getHours() < 12) ? date.getHours() : date.getHours() - 12;
	var minutes = date.getMinutes();
	var amOrPm = (date.getHours() < 12) ? "am" : "pm";

	return day + '/' + month + '/' + year + ' ' + hour + ':' + minutes + amOrPm;
}

function superParseFloat(value){
  var v = value.replace(',','.');
  if (v.indexOf('.') == 0) v = '0' + v;

  v = v.replace('.',',');
  if (v.indexOf(',') == ',') v = '0' + v;

	v =  parseFloat(v.replace('.',',')) * 1 + parseFloat(v.replace(',','.')) * 1;

	return v;
}
//
// starts a new credit transfer
//
function transferCredits(total) {
	var username = $('#username').val().trim();
	var amount = superParseFloat($('#amount').val().trim());

	// do not allow you to transfer more than what you have
	if(amount > superParseFloat(total)) {
		M.toast({html: 'Usted no tiene suficientes creditos para realizar esta transferencia'});
		return false;
	}

	// do not allow empty username or amounts
	if(username == '' || amount == 'NaN' || amount == 0) {
		M.toast({html: 'Debe llenar ambos campos con valores validos antes de continuar'});
		return false;
	}

	// start a new transfer
	apretaste.send({
		command: "CREDITO PROCESAR", 
		data: {"username":username, "price":amount},
		redirect: true});
}

function strpos(haystack,needle,offset){
  var i=(haystack+'').indexOf(needle,(offset||0));
  return i===-1?false:i;
}

$(function(){
  $("#amount").keydown(function(e){
    var value = $(this).val();
    var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;

    if ((keyCode < 48 || keyCode > 60) && keyCode != 8 && keyCode != 190 && keyCode != 188 && keyCode != 39 && keyCode != 37 && keyCode != 46 && keyCode != 9)
      return false;

    if (strpos(value, '.') != false && (keyCode == 190 || keyCode == 188))
      return false;

    return true;
  });

  $("#amount").keyup(function(e){
    $(this).val($(this).val().replace(',', '.'));
  });
});