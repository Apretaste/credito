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
	return parseFloat(value.replace('.',',')) * 1 + parseFloat(value.replace(',','.')) * 1;
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

    if ((keyCode < 48 || keyCode > 60) && keyCode != 8 && keyCode != 190 && keyCode != 39 && keyCode != 37 && keyCode != 46 && keyCode != 9)
      return false;

    if (e.keyCode === 188) {
     e.keyCode = 190; //e.preventDefault();
      //$(this).val($(this).val() + ".");
      //return true;
    }

    if (strpos(value, '.') != false && e.keyCode == 190)
      return false;

    return e.keyCode;
  });
});