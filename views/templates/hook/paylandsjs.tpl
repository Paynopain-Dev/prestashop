<!--

 -->
<div class="paylands-container">
	<div class="loader">
		<img src="{$loader}">
	</div>
	{if (count($cards) > 0 && $save_card)}
		<div class="select-card">
			<p>{l s='Select a card to pay' mod='paylands'}</p>
			<p class="paylands-cards-messages"></p>
			{foreach from=$cards item=card}
				<div class="card">
					<input type="radio" name="paylands_card" value="{$card->uuid}">
					<span class="card-number"><strong>{l s='Card' mod='paylands'}:</strong> {$card->last4}</span>
					<span class="card-date"><strong>{l s='Expiration Date' mod='paylands'}:</strong> {$card->expire_month}/{$card->expire_year}</span>
				</div>
			{/foreach}
			<div class="card">
				<p class="option">
					<input type="radio" name="paylands_card" value="custom">
					<strong class="card-number">{l s='Add New Card' mod='paylands'}</strong>
				</p>
				<div class="custom-form">
					<p class="paylands-messages"></p>
					<div id="paylands-frame"></div>
				</div>
			</div>
		</div>
	{else}
		<p class="paylands-messages"></p>
		<div id="paylands-frame"></div>
	{/if}


</div>
<script src="https://api.paylands.com/js/v1-iframe.js"></script>
<script>
    var paylandConfig = {$configurations|@json_encode nofilter}
</script>
