<div class="card mt-2">

<div class="card-header">
    <div class="row">
      <div class="col-md-6">
        
			<img src="{$logoUrl}" alt="PayVector" />
			<p>
      {l s='This order has been placed using PayVector. ' mod='payvector'}
    </p>
    {if $is_refunded}
    <div class="alert alert-info">
      {l s='This order has been refunded.' mod='payvector'}
    </div>

  {elseif $show_refund_button}
	  <form action="{$payvector_form|escape:'htmlall':'UTF-8'}" method="post">
      <button class="btn btn-primary" type="submit" name="payvectorrefund" value="refund">
          {l s='Refund' mod='payvector'}
       </button>      
    </form>
{else}  
 <p><em>{l s='Refund not available. Payment not accepted.' mod='payvector'}</em></p>
{/if}  
        
      </div>
          </div>
  </div>   
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('.partial-refund-display');
    if (button) {
      button.style.display = 'none';
    }
  });
</script>
