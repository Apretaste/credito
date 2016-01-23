<p>Usted tiene actualmente <b>${$credit|money_format}</b> de cr&eacute;dito, lo cual no es suficiente para enviar <b>${$amount|money_format}</b> a {$email}.</p>

{if $credit gt 0}
	{space10}
	<center>
		{button href="CREDITO {$credit} {$email}" caption="Enviar ${$credit|money_format}"}
	</center>
{/if}