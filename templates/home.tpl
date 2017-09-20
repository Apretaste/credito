{space5}

<center>
	<h1>Su cr&eacute;dito<br/>§{$credit}</h1>
</center>

{space5}

<p>Algunos servicios le permitir&aacute;n comprar dentro de Apretaste usando su cr&eacute;dito. Visite servicios como {link href="RIFA" caption="Rifa"} o {link href="MERCADO" caption="Mercado"} para realizar compras. Tambi&eacute;n puede transferir cr&eacute;ditos a otros usuarios de Apretaste.</p>

<center>
	{button href="WEB credito.apretaste.com" caption="Obtener cr&eacute;dito" color="grey"}
	{button href="CREDITO" caption="Transferir" body="Agregue al asunto el @username o email del receptor seguido de la cantidad a recibir despues de la palabra CREDITO. Por ejemplo: CREDITO @amigo 2.40" desc="Inserte el @username o email del receptor seguido de la cantidad a recibir. Por ejemplo: @amigo 2.40" popup="true"}
</center>

{if $items !== false}
	{space15}
	<h1>Sus &uacute;ltimas compras</h1>

	<table width="100%" cellspacing="0">
		<tr>
			<th>Fecha</th>
			<th>Art&iacute;culo</th>
			<th>Costo</th>
		</tr>
		{foreach from=$items item=item}
			<tr {if $item@iteration is odd}bgcolor="F2F2F2"{/if} align="center">
				<td width="40%">{$item->transfer_time|date_format:"%e/%m/%Y"}</td>
				<td>{$item->name}</td>
				<td>&sect;{$item->amount|money_format}&nbsp;&nbsp;</td>
			</tr>
		{/foreach}
	</table>
{/if}
