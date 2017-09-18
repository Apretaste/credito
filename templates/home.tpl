<center>
	<h1>Su cr&eacute;dito es</h1>
	<p><b><big><big><big>§{$credit}</big></big></big></b></p>
</center>

{space10}

<p>Algunos servicios le permitir&aacute;n comprar dentro de Apretaste usando su cr&eacute;dito. Visite servicios como {link href="RIFA" caption="Rifa"} o {link href="MERCADO" caption="Mercado"} para realizar compras. Tambi&eacute;n puede transferir cr&eacute;ditos a otros usuarios de Apretaste.</p>

{if $items !== false}
{space10}
<h2>Sus &uacute;ltimas compras</h2>

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
{/if}

{space5}

<center>
	{button href="WEB credito.apretaste.com" caption="Obtener cr&eacute;dito" color="grey"}
	{button href="CREDITO" caption="Transferir" body="Agregue al asunto el @username o email del receptor seguido de la cantidad a recibir despues de la palabra CREDITO. Por ejemplo: CREDITO @amigo 2.40" desc="Inserte el @username o email del receptor seguido de la cantidad a recibir. Por ejemplo: @amigo 2.40" popup="true"}
</center>
