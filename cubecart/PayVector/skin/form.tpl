{literal}
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	
	<script type="text/javascript" >
	var jQuery_1_10_2 = jQuery.noConflict(true);
	    jQuery_1_10_2(document).ready(function()
	    {
	    	var cdtTransaction = jQuery_1_10_2('.cdtCardDetailsTransasction');
	    	var crtTransaction = jQuery_1_10_2('.crtCrossReferenceTransasction');
	    	
	    	{/literal}
			{if $DISPLAY_CRT}
			{literal}
			
	    	cdtTransaction.hide();
	    	crtTransaction.show();
			get3DSv2Params();
	    	
	    	{/literal}
			{else}
			{literal}
			
	    	crtTransaction.hide();
	    	cdtTransaction.show();
			get3DSv2Params();
			
	    	
	    	{/literal}
			{/if}
			{literal}   	
			
	    	jQuery_1_10_2('input[name=CurrentTransactionType]').change(function () {
	    		switch ($(this).val())
	    		{
	    			case 'cdt' :
	    				jQuery_1_10_2(".crtCrossReferenceTransasction").hide();
	    				jQuery_1_10_2(".cdtCardDetailsTransasction").show();
	    				break;
	    				
    				case 'crt' :
	    				jQuery_1_10_2(".cdtCardDetailsTransasction").hide();
	    				jQuery_1_10_2(".crtCrossReferenceTransasction").show();
    					break;
	    		}	
	    	});
    	});

		 // Collect 3DSv2 Parameters
    function get3DSv2Params() {
        document.getElementById("browserJavaEnabled").value = navigator.javaEnabled();
        document.getElementById("browserLanguage").value = navigator.language || navigator.userLanguage;
        document.getElementById("browserColorDepth").value = screen.colorDepth;
        document.getElementById("browserScreenHeight").value = screen.height;
        document.getElementById("browserScreenWidth").value = screen.width;
        document.getElementById("browserTZ").value = new Date().getTimezoneOffset();
        document.getElementById("browserUserAgent").value = navigator.userAgent;
    }
	
    </script>
    {/literal}
   	    
	<table width="100%" cellpadding="3" cellspacing="10" border="0">
		<tr>
			{if $DISPLAY_CRT}
			<td width="25%">
				<input type="radio" name="CurrentTransactionType" id="NewOrExisting" value="crt" checked />
                <label for="CurrentTransactionType">Use Existing Card</label>
            </td>
            <td>
				<input type="radio" name="CurrentTransactionType" id="NewOrExisting" value="cdt" />
                <label for="CurrentTransactionType">Use New Card</label>
			</td>
			{else}
            <td>
				<input type="radio" name="CurrentTransactionType" id="NewOrExisting" value="cdt" checked />
                <label for="CurrentTransactionType">Use New Card</label>
			</td>			
            {/if}
		</tr>
		{if $mode == 'api'}
		<tr>
			<td colspan="2"><h2>{$LANG.orders.title_card_details}</h2></td>
		</tr>
        
		<tr class="cdtCardDetailsTransasction">
            <td width="140">{$LANG.gateway.name_on_card}Name On Card</td>
            <td><input type="text" name="CardName" class="required" value="{$CUSTOMER.name_on_card}" /></td>
        </tr>
        <tr class="cdtCardDetailsTransasction" >
            <td width="140">{$LANG.gateway.card_number}</td>
            <td><input type="text" name="CardNumber" class="required" value="" size="16" maxlength="16" /></td>
        </tr>		
        <tr class="cdtCardDetailsTransasction" >
            <td width="140">{$LANG.gateway.card_expiry_date}</td>
            <td>
                <select name="ExpiryDateMonth" style="width:100px;margin-right: 15px; " >
	                {foreach from=$CARD.expiry.months item=month}
	                	<option value="{$month.value}" {$month.selected}>{$month.display}</option>
	                {/foreach}
	            </select>
	            <select name="ExpiryDateYear" style="width:100px; ">
	            	{foreach from=$CARD.expiry.years item=year}
	            		<option value="{substr($year.value,-2,2)}" {$year.selected}>{$year.value}</option>
            		{/foreach}
	        	</select>
	    	</td>
		</tr>
		<tr class="cdtCardDetailsTransasction">
		    <td width="140">{$LANG.gateway.card_security}</td>
		    <td>
		    	<input type="text" name="CV2" class="textbox_small required" value="" size="2" maxlength="4" style="text-align: center;width:100px;" />
		        <a href="images/general/cvv.gif" class="colorbox" title="{$LANG.gateway.card_security}" /> {$LANG.common.whats_this}</a>
		    </td>
		</tr>
		{/if}
		{if $mode == 'hpf'}
		<tr class="cdtCardDetailsTransasction">
			<td colspan="2"><h3>On clicking Make Payment you will be redirected to our secure payment form</h3></td>
		</tr>
		{/if}

		<tr class="crtCrossReferenceTransasction">
            <td width="140">Card Last Four</td>
        	<td width="140">				
				{$CRT.CardType} ending in {$CRT.LastFour}	
    		</td>
        </tr>
		<tr class="crtCrossReferenceTransasction">
		    <td width="140">{$LANG.gateway.card_security}</td>
		    <td>
		    	<input type="text" name="CV2_CTR" class="textbox_small required" value="" size="2" maxlength="4" style="text-align: center;width:100px;" />
		        <a href="images/general/cvv.gif" class="colorbox" title="{$LANG.gateway.card_security}" /> {$LANG.common.whats_this}</a>
		    </td>
		</tr>	
	{if $mode == 'api'}			
		<tr class="cdtCardDetailsTransasction" >
			<td colspan=2><h2>{$LANG.basket.customer_info}</h2></td>
		</tr>

	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.common.email}</td>
	        <td><input type="text" name="EmailAddress" value="{$CUSTOMER.email}" size="50" /></td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.phone}</td>
	        <td><input type="text" name="PhoneNumber" value="{$CUSTOMER.phone}" size="50" /></td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.line1}</td>
	        <td><input type="text" name="Address1" class="required" value="{$CUSTOMER.add1}" size="50" /></td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.line2}</td>
	        <td><input type="text" name="Address2" value="{$CUSTOMER.add2}" size="50" /> {$LANG.common.optional}</td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.line3}</td>
	        <td><input type="text" name="Address3" value="{$CUSTOMER.add3}" size="50" /> {$LANG.common.optional}</td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.line4}</td>
	        <td><input type="text" name="Address4" value="{$CUSTOMER.add4}" size="50" /> {$LANG.common.optional}</td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.town}</td>
	        <td><input type="text" name="City" class="required" value="{$CUSTOMER.city}" /></td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.state}</td>
	        <td><input type="text" name="State" class="required" value="{$CUSTOMER.state}" size="10" /></td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.postcode}</td>
	        <td><input type="text" name="Postcode" class="required" value="{$CUSTOMER.postcode}" size="10" maxlength="10" /></td>
	    </tr>
	    <tr class="cdtCardDetailsTransasction" >
	        <td width="140">{$LANG.address.country}
	        <td>
	            <select name="CountryCode" class="required" >
		            {foreach from=$COUNTRIES item=country}<option value="{$country.numcode}"{$country.selected}>{$country.name}</option>{/foreach}
		        </select>
		    </td>
		</tr>
	{/if}
	</table> 
	
	
<input type="hidden" name="browserjavaenabled" id="browserJavaEnabled" value="">
    <input type="hidden" name="browserlanguage" id="browserLanguage" value="">
    <input type="hidden" name="browsercolordepth" id="browserColorDepth" value="">
    <input type="hidden" name="browserscreenheight" id="browserScreenHeight" value="">
    <input type="hidden" name="browserscreenwidth" id="browserScreenWidth" value="">
    <input type="hidden" name="browsertz" id="browserTZ" value="">
	<input type="hidden" name="browserUserAgent" id="browserUserAgent" value=""> 
