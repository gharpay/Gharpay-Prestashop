<form action="{$payment_url}" method="post" id="gharpay_form" style="display:inline">
<p class="payment_module">
	{if $pincode_valid != 1}
	<strong><span style="color:red">Your pincode of the billing address not lies in the Gharpay's serviceable area</span></strong><br /><br />
    {/if}
	<a href="javascript:{if $pincode_valid == 1}$('#gharpay_form').submit();{else}void(0);{/if}" title="{$title}">
		<img src="{$module_dir}logo_org.png" alt="{$title}" width="160" height="75" />
		{$description}
		<br />
        <br />
	</a>
    {l s='Choose payment option' mod='gharpay'}: 
	<input type="radio" name="payment_option" value="cash" /> Cash
    &nbsp;
	<input type="radio" name="payment_option" value="cheque" /> Cheque
</p>
</form>
