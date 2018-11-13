<center>
	{space10}
	<h1>Su cr&eacute;dito<br/>§{$credit}</h1>

	{if $APRETASTE_ENVIRONMENT neq "web" and $APP_TYPE eq "original"}
		<p>Use su cr&eacute;dito para canjear por productos o servicios dentro de nuestra app, o transfiera cr&eacute;ditos a otros usuarios.</p>
		{button href="CREDITO OBTENER" caption="Obtener cr&eacute;dito" color="grey"}
		{button href="CREDITO" caption="Transferir" desc="Inserte el @username del receptor*|n:Inserte la cantidad a enviar*" popup="true"}
	{/if}

	{if $items !== false}
		{space10}
		<h2>Sus &uacute;ltimas compras</h2>

		<table width="100%" cellspacing="0">
			<tr>
				<th>Fecha</th>
				<th>Art&iacute;culo</th>
				<th>Costo</th>
			</tr>
			{foreach from=$items item=item}
				<tr {if $item@iteration is odd}bgcolor="F2F2F2"{/if} align="center">
					<td>{$item->transfer_time|date_format:"%e/%m/%Y"}</td>
					<td>{$item->name}</td>
					<td>&sect;{$item->amount|money_format}</td>
				</tr>
			{/foreach}
		</table>
	{/if}
</center>