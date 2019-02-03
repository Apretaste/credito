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

//
// starts a new credit transfer
//
function transferCredits(total) {
	var username = $('#username').val().trim();
	var amount = parseFloat($('#amount').val().trim());

	// do not allow you to transfer more than what you have
	if(amount > parseFloat(total)) {
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