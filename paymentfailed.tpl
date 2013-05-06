<h2>{l s='Gharpay Payment Failed' mod='gharpay'}</h2><br />
<h5>{l s='Error' mod='gharpay'}: <span style="color:red">{$error}</span></h5><br />
{if $id_order < 1}
	<meta http-equiv="refresh" content="5;url={$orderPage}">
    {l s='Redirecting to order page' mod='gharpay'}<br />
	{l s='If page does not redirect to order page in 10 secs, ' mod='gharpay'}<a href="{$orderPage}">{l s='click here' mod='gharpay'}</a>
{else}
	{if $isGuest == 0}
        <meta http-equiv="refresh" content="5;url={$historyPage}">
        {l s='Redirecting to history page, you can reorder again' mod='gharpay'}<br />
        {l s='If page does not redirect to order page in 10 secs, ' mod='gharpay'}<a href="{$historyPage}">{l s='click here' mod='gharpay'}</a>
    {/if}
{/if}
