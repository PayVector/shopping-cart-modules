<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

<script type="text/javascript" >
	var jQuery_1_10_2 = jQuery.noConflict(true);
    function toggleCredentials() {
        var testCredentials = jQuery_1_10_2("div#test_credentials");
		var productionCredentials = jQuery_1_10_2("div#production_credentials");        
        var val = jQuery_1_10_2('#testMode').val(); // "1" or "0"

    if (val === "1") {        
      testCredentials.show();
      productionCredentials.hide();      
    } else {
        testCredentials.hide();
        productionCredentials.show();            
    }
  }
    
    

    jQuery_1_10_2(document).ready(function() 
    {
    	var testCredentials = jQuery_1_10_2("div#test_credentials");
		var productionCredentials = jQuery_1_10_2("div#production_credentials");
		var caCredentials = jQuery_1_10_2("div#ca_credentials");
		
		var modeSelected = jQuery_1_10_2('select[name="module[mode]"] option:selected').text();
		
	    if ({if isset($MODULE.testMode)} {$MODULE.testMode} {else} 0 {/if}) {
	        testCredentials.show();
	        productionCredentials.hide();
	        caCredentials.hide();
	    } else {
	        productionCredentials.show();
	    	
	    	switch (modeSelected.text())
	    	{
	        	case "{$LANG.payvector.api}":
	        		caCredentials.show();
	    			break;
				default :
					break;
			}
			
	        testCredentials.hide();
	    }	    
	    
	    var hpfVariables = jQuery_1_10_2(".hpfVariables");
	    var trVariables = jQuery_1_10_2(".trVariables");
	    var apiVariables = jQuery_1_10_2(".apiVariables");

		switch (jQuery_1_10_2('select[name="module[mode]"] option:selected').text())
	    {
	        case "{$LANG.payvector.api}":
	        
	            $(".hpfVariables").hide();
	            $(".trVariables").hide();
	            $(".apiVariables").show();
	        	break;
	        	
	        case "{$LANG.payvector.tr}":
	            $(".apiVariables").show();	            
	            $(".hpfVariables").hide();
	            $(".trVariables").show();
	            break;
	
	        case "{$LANG.payvector.hpf}":
	            
	            $(".apiVariables").show();
	            $(".trVariables").hide();
	            $(".hpfVariables").show();
	            break;
	    }
    
	    jQuery_1_10_2('select[name="module[mode]"]').change(function () {
	
	        switch ($("option:selected", this).text())
	        {
	            case "{$LANG.payvector.api}":
	        
		            $(".hpfVariables").hide();
		            $(".trVariables").hide();
		            $(".apiVariables").show();
		        	break;
		        	
		        case "{$LANG.payvector.tr}":
		        
		            $(".apiVariables").show();
		            $(".hpfVariables").hide();
		            $(".trVariables").show();
		            break;
		
		        case "{$LANG.payvector.hpf}":
		            
		            $(".apiVariables").show();
		            $(".trVariables").hide();
		            $(".hpfVariables").show();
		            break;
	        }
	    });
	});
</script>

<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
    <div id="PayVector" class="tab_content">
        <h3>{$TITLE}</h3>
        <fieldset>
            <legend>{$LANG.module.cubecart_settings}</legend>
            <div>
                <label for="description">{$LANG.common.description} *</label>
                <span>
                    <input type="text" name="module[desc]" id="description" class="textbox" value="{$MODULE.desc}" />
                </span>
            </div>
            <div>
                <label for="status">{$LANG.common.status}</label>
                <span>
                    <input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" />
                </span>
            </div>
            <div>
                <label for="default">{$LANG.common.default}</label>
                <span>
                    <input type="hidden" name="module[default]" id="default" class="toggle" value="{$MODULE.default}" />
                </span>
            </div>
            <div>
                <label for="scope">{$LANG.module.scope}</label>
                <span>
                    <select name="module[scope]">
                        <option value="both" {$SELECT_scope_both}>{$LANG.module.both}</option>
                        <option value="main" {$SELECT_scope_main}>{$LANG.module.main}</option>
                        <option value="mobile" {$SELECT_scope_mobile}>{$LANG.module.mobile}</option>
                    </select>
                </span>
            </div>
            <div>
                <label for="testMode">{$LANG.module.mode_test}</label>
                <span>
                    <input type="hidden" name="module[testMode]" id="testMode" onchange="toggleCredentials()" class="toggle" value="{$MODULE.testMode}" />
                </span>
            </div>            
            <div id="gateway_mode">
                <label for="mode">{$LANG.payvector.mode}</label>
                <span>
                    <select name="module[mode]" id="mode">
                        <option value="hpf" {$SELECT_mode_hpf}>{$LANG.payvector.hpf}</option>
                        <option value="api" {$SELECT_mode_api}>{$LANG.payvector.api}</option>
                    </select>
                </span>
            </div>
            <div class="apiVariables">
                <label for="crt">{$LANG.payvector.crt}</label>
                <span>
                    <input type="hidden" name="module[crt]" id="crt" class="toggle" value="{if isset($MODULE.crt)}{$MODULE.crt}{else}1{/if}" />
                </span>
            </div>
            <div id="test_credentials">
                <div>
                    <label for="mid_test">{$LANG.payvector.merchant_id_test}</label>
                    <span><input name="module[mid_test]" id="mid_test" class="textbox" type="text" value="{$MODULE.mid_test}" /></span>
                </div>
                <div>
                    <label for="pass_test">{$LANG.payvector.password_test}</label>
                    <span><input name="module[pass_test]" id="pass_test" class="textbox" type="text" value="{$MODULE.pass_test}" /></span>
                </div>                
            </div>
            <div id="production_credentials">
                <div>
                    <label for="mid_prod">{$LANG.payvector.merchant_id_prod}</label>
                    <span>
                        <input name="module[mid_prod]" id="mid_prod" class="textbox" type="text" value="{$MODULE.mid_prod}" />
                    </span>
                </div>
                <div>
                    <label for="pass_prod">{$LANG.payvector.password_prod}</label>
                    <span>
                        <input name="module[pass_prod]" id="pass_prod" class="textbox" type="text" value="{$MODULE.pass_prod}" />
                    </span>
                </div>                
            </div>
            <div id="hpfVariables">
                <div class="hpfVariables trVariables">
                    <label for="hpfPreSharedKey">{$LANG.payvector.hpfPreSharedKey}</label>
                    <span><input name="module[hpfPreSharedKey]" id="hpfPreSharedKey" class="textbox" type="text" value="{$MODULE.hpfPreSharedKey}" /></span>
                </div>
                <div class="hpfVariables trVariables">
                    <label for="hpfHashMethod">{$LANG.payvector.hpfHashMethod}</label>
                    <span>
                        <select name="module[hpfHashMethod]">
                            <option value="SHA1" {$SELECT_hpfHashMethod_SHA1}>{$LANG.payvector.hmSHA1}</option>
                            <option value="MD5" {$SELECT_hpfHashMethod_MD5}>{$LANG.payvector.hmMD5}</option>
                            <option value="HMACSHA1" {$SELECT_hpfHashMethod_HMACSHA1}>{$LANG.payvector.hmHMACSHA1}</option>
                            <option value="HMACMD5" {$SELECT_hpfHashMethod_HMACMD5}>{$LANG.payvector.hmHMACMD5}</option>
                        </select>
                    </span>
                </div>
                <div class="hpfVariables">
                    <label for="hpfResultDeliveryMethod">{$LANG.payvector.hpfResultDeliveryMethod}</label>
                    <span>
                        <select name="module[hpfResultDeliveryMethod]">
                            <option value="POST" {$SELECT_hpfResultDeliveryMethod_POST}>{$LANG.payvector.rdmPost}</option>                            
                            <option value="SERVER_PULL" {$SELECT_hpfResultDeliveryMethod_SERVER_PULL}>{$LANG.payvector.rdmServer_Pull}</option>
                        </select>
                    </span>
                </div>
                
            </div>
        </fieldset>
        <p>{$LANG.module.description_options}</p>
    </div>


{$MODULE_ZONES}
<div class="form_control">
    <input type="submit" name="save" value="{$LANG.common.save}" />
</div>

<input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>