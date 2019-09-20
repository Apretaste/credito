function pad(n, width, z) {
	z = z || '0';
	n = n + '';
	return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

function formatDate(dateStr) {
	var date = new Date(dateStr);
	var year = date.getFullYear();
	var month = pad(1 + date.getMonth(),2);
	var day = pad(date.getDay(),2);
	var hour = (date.getHours() < 12) ? date.getHours() : date.getHours() - 12;
	var minutes = date.getMinutes();
	var amOrPm = (date.getHours() < 12) ? "am" : "pm";

	return day + '/' + month + '/' + year + ' ' + hour + ':' + minutes + amOrPm;
}

//
// super parse a float number
//
function superParseFloat(value) {
	var v = value.replace(',','.');
	if (v.indexOf('.') == 0) v = '0' + v;
	v = v.replace('.',',');
	if (v.indexOf(',') == ',') v = '0' + v;
	v =  parseFloat(v.replace(',','.')) * 1;
	return v;
}

//
// starts a new credit transfer
//
function transferCredits(total) {
	var username = $('#username').val().trim();
	var amount = superParseFloat($('#amount').val().trim());
	var reason = $('#reason').val().trim();

	// do not allow you to transfer more than what you have
	if(amount > superParseFloat(total)) {
		M.toast({html: 'No tiene suficientes créditos'});
		return false;
	}

	// do not allow empty username or amounts
	if(username == '' || amount == 'NaN' || amount == 0) {
		M.toast({html: 'Llene todos los campos correctamente'});
		return false;
	}

	// force a valid reason
	if(reason.length < 5) {
		M.toast({html: 'Detalle la razón de esta transferencia'});
		return false;
	}

	// start a new transfer
	apretaste.send({
		command: "CREDITO PROCESAR", 
		data: {"username":username, "price":amount, "reason":reason},
		redirect: true
	});
}

//
// get the position of a char in a string 
//
function strpos(haystack, needle, offset) {
	var i=(haystack+'').indexOf(needle,(offset||0));
	return i===-1?false:i;
}

//
// add key events when the service starts
//
$(function(){
	$("#amount").keydown(function(e){
		var value = $(this).val();
		var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;

		if ((keyCode < 48 || keyCode > 60) && keyCode != 8 && keyCode != 190 && keyCode != 188 && keyCode != 39 && keyCode != 37 && keyCode != 46 && keyCode != 9) return false;
		if (strpos(value, '.') != false && (keyCode == 190 || keyCode == 188)) return false;
		return true;
	});

	$("#amount").keyup(function(e){
		$(this).val($(this).val().replace(',', '.'));
	});
});