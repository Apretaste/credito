$(document).ready(function(){
	$('.modal').modal();
});

// super parse a float number
function superParseFloat(value) {
	var v = value.replace(',','.');
	if (v.indexOf('.') == 0) v = '0' + v;
	v = v.replace('.',',');
	if (v.indexOf(',') == ',') v = '0' + v;
	v =  parseFloat(v.replace(',','.')) * 1;
	return v;
}

// show the modal popup
function openModal (total) {
	// validate the transfer
	var data = validate();
	if(!data) return false;

	// open the modal
	$('#modalUsername').html(data.username);
	$('#modalAmount').html(data.amount);
	$('#transferModal').modal('open');
}

// start a new transfer
function transfer(total) {
	// validate the transfer
	var data = validate();
	if(!data) return false;

	// execute the transfer
	apretaste.send({
		command: "CREDITO TRANSFER", 
		data: {"username":data.username, "price":data.amount, "reason":data.reason},
		redirect: true
	});
}

// validates a transfer
function validate() {
	// get all the values
	var username = $('#username').val().trim();
	var amount = superParseFloat($('#amount').val().trim());
	var total = superParseFloat($('#total').val().trim());
	var reason = $('#reason').val().trim();

	// do not allow you to transfer more than what you have
	if(amount > total) {
		M.toast({html: 'No tiene suficientes créditos'});
		return false;
	}

	// do not allow empty username or amounts
	if(username == '' || amount == 'NaN' || amount <= 0) {
		M.toast({html: 'Llene todos los campos correctamente'});
		return false;
	}

	// force a valid reason
	if(reason.length < 5) {
		M.toast({html: 'Detalle la razón de esta transferencia'});
		return false;
	}

	// return validated JSON structure
	return {username:username, amount:amount, reason:reason};
}

// get the position of a char in a string 
function strpos(haystack, needle, offset) {
	var i=(haystack+'').indexOf(needle,(offset||0));
	return i===-1?false:i;
}

// add key events when the service starts
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