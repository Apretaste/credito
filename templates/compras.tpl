<h1>Compras</h1>

<table width="100%">
	<tr>
		<th>Art&iacute;culo</th>
		<th>Costo</th>
		<th>Fecha de compra</th>
	</tr>
{foreach from=$items item=item}
	<tr {if $item@iteration is odd}style="background-color:#F2F2F2;"{/if}>
		<td style="padding:5px;" width="30%">{$item->name}</td>
		<td style="padding:5px;" align="right"><b>&sect;{$item->amount|money_format}&nbsp;&nbsp;</b></td>
		<td style="padding:5px;" align="right" width="40%"><small>{$item->transfer_time|date_format:"%e de %B del %Y"}</small></td>
	</tr>
{/foreach}
</table>
{space5}
<center>
	{button href="WEB credito.apretaste.com" caption="Obtener cr&eacute;dito" color="grey"}
	{button href="CREDITO" caption="Transferir" body="Agregue al asunto el @username o email del receptor seguido de la cantidad a recibir despues de la palabra CREDITO. Por ejemplo: CREDITO @amigo 2.40" desc="Inserte el @username o email del receptor seguido de la cantidad a recibir. Por ejemplo: @amigo 2.40" popup="true"}
	{button href="MERCADO" caption="Mercado" color="blue"}
</center>
