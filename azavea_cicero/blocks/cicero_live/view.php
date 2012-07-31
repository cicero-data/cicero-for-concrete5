<?php

/* Copyright 2010-2012 Azavea, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

//get the user's country code
$httpAcceptLangArray = explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);//returns single-element array (e.g. en-US)
$langCountryArray = explode("-",$httpAcceptLangArray[0]);//returns array w/ two elements: language (e.g. en) and country (e.g. US)
$recognizedCountryCodes = array('AU','CA','NZ','UK','US');
$get_c_ucase = strtoupper($_GET['c']);//if country code is in 
if(in_array($get_c_ucase, $recognizedCountryCodes)):
	$clientCountry = $get_c_ucase;
else:
	$clientCountry = $langCountryArray[1];//creates a string with two-digit ISO country code (e.g. US), we'll use this to create a JS variable later.	
	if(!in_array($clientCountry,$recognizedCountryCodes)):
		if( in_array( $clientCountry, array( 'GB', 'IE', 'FR' ) ) ):
			$clientCountry = 'UK';//use UK for GB and some of its neighbours (UK is not ISO-std., but GB is)
		else:
			$clientCountry = 'US';//default to US
		endif;
	endif;//!in_array
endif;
?>
<script type="text/javascript" src="/js/jquery.cookie.js"></script>
<script type="text/javascript" src="http://serverapi.arcgisonline.com/jsapi/arcgis/?v=2.4"></script>
<script type="text/javascript">
	clientCountry = '<?php echo $clientCountry; ?>';
	prepopAddress = '<?php echo str_replace('\'', '\\\'', $_GET['a']); ?>';
	bingKey = '<?php echo $bing_key; ?>';
</script>
<link href="http://serverapi.arcgisonline.com/jsapi/arcgis/2.4/js/dojo/dijit/themes/tundra/tundra.css" rel="stylesheet" type="text/css" >

<?php
$get_officials=$this->action('get_elected_officials');
$get_new_legislative_districts=$this->action('get_new_legislative_districts');
$get_districts=$this->action('get_nonlegislative_districts');
$get_maps=$this->action('get_maps');
$e=array_pop(explode('/',$_SERVER['REQUEST_URI']));
if($e==''||strpos($e,'.')!==false){//relative ref okay but get rid of slashes
	$get_officials = substr($get_officials,1);
    $get_new_legislative_districts = substr($get_new_legislative_districts,1);
	$get_districts = substr($get_districts,1);
}else{//use absolute ref
	$get_officials = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $get_officials;
    $get_new_legislative_districts = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $get_new_legislative_districts;
	$get_districts = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $get_districts;
}
?>
<a href="<?=$get_officials?>" id="get-elected-officials" class="hidden-link">Call cicero</a>
<a href="<?=$get_new_legislative_districts?>" id="get-new-legislative-districts" class="hidden-link">Call cicero</a>
<a href="<?=$get_districts?>" id="get-nonlegislative-districts" class="hidden-link">Call cicero</a>
<a href="<?=$get_maps?>" id="get-maps" class="hidden-link">Call cicero</a>

<div id="cicero-live">
	<div id="address-content">
        <form autocomplete="on" onSubmit="return false;">
            <label for="address-input">Please enter an address</label>
            <input type="text" id="address-input" autofocus/>
            <input type="submit" id="geocode-button" value="Search" />
        </form>
		<div id="address-candidates-note">Did you mean...</div>
	    <div id="locations" class="locations">&nbsp;</div>
		<a id="address-permalink" href="#" >Permalink to this search</a>
	</div>

	<div id="map-container">
		<div id="map-column">
			<div id="country-selector">
				<input type="button" class="bing-maps" value="AU" />
				<input type="button" class="esri-geocoder" value="CA" />
				<input type="button" class="bing-maps" value="NZ" />
				<input type="button" class="esri-geocoder" value="UK" />
				<input type="button" class="esri-geocoder" value="US" />
			</div>
			<div id="map">
				<div id="map-overlay" style="display:none;">
					<img src="/packages/azavea_cicero/blocks/cicero_live/images/pl.gif" />
				</div>
			</div>
            <div id="official-info-container">
                <div id="official-info"></div>
            </div>
			
			<div id="report-a-problem"><a target="_blank" href="/products/cicero/support">Report a problem</a></div>
		</div>
	</div>

	<div id="officials-content" class="officials">
	</div>
	<div style="clear:both;">&nbsp;</div>
</div>
<a id="geocode-marker" href="/packages/azavea_cicero/blocks/cicero_live/images/geocode_marker.png"></a>

<!-- HTML templates for use in the javascript templating system -->
<textarea cols="0" rows="0" id="official-full-template" style="display:none;">
    {eval} 
        if (DistrictType.indexOf('local') >= 0) {
            catBox = '<' + 'div class="catBox" id="catBoxLocal">Local<' + '/div>';
        }
        else if (DistrictType.indexOf('state') >= 0) {
            catBox = '<' + 'div class="catBox" id="catBoxState">State<' + '/div>';
        }
        else if (DistrictType.indexOf('national') >= 0) {
            catBox = '<' + 'div class="catBox" id="catBoxNational">National<' + '/div>';
        }
        else {
            catBox = '';	
        }
    {/eval}
    
    ${catBox}

    <h1>
    {if isNaN(DistrictID)}
        ${DistrictID}
    {else}
        District ${DistrictID}
    {/if}
    </h1>
    
    {if RepresentingState != ''}
        {if RepresentingCity != ''}
        <h2>${RepresentingCity}, ${RepresentingState}, ${Country}</h2>
        {else}
        <h2>${RepresentingState}, ${Country}</h2>
        {/if}
    {else}
        <h2>${Country}</h2>
    {/if}
    
    <h3>
    {if Title != ''}
        ${Title}
    {/if}
    {if defined('FirstName')}
        ${FirstName}
    {if defined('LastName')}
    {/if}
        ${LastName}
    {/if}
    {if Party != ''}
        (${Party})
    {/if}
    </h3>

    {if PrimaryAddress1 != ''}
        <h4>Primary Address</h4>
        ${PrimaryAddress1}<br />
    {/if}
    {if PrimaryAddress2 != ''}
        ${PrimaryAddress2}<br />
    {/if}
    {if PrimaryAddress3 != ''}
        ${PrimaryAddress3}<br />
    {/if}
    {if PrimaryState != ''}
        {if PrimaryCity != ''}
            ${PrimaryCity},
        {/if}
        ${PrimaryState}
    {else}
        {if PrimaryCity != ''}
            ${PrimaryCity}&nbsp;&nbsp;
        {/if}
    {/if}
    {if PrimaryPostalCode != ''}
        ${PrimaryPostalCode|capitalize}
    {/if}<br />
    
    {if PrimaryPhone1 != ''}
        {if PrimaryFax1 != ''}
            <div class = "half">
                <h4 id="H1">Phone</h4> 
                ${PrimaryPhone1}
            </div>
            
            <div class="half">
                <h4>Fax</h4>
                ${PrimaryFax1}
            </div>
        {else}
            <div>
                <h4 id="PrimaryPhone1">Phone</h4> 
                ${PrimaryPhone1}
            </div>
        {/if}
    {elseif PrimaryFax1 != ''}
        <div>
            <h4 id="fax">Fax</h4>
            ${PrimaryFax1}
        </div>
    {/if}
    
    {if EMail1 != ''}
        <h4>Email</h4> 
        {var mailLink = '<' + 'a href="mailto:' + EMail1 + '" target="_blank"' + '>' + EMail1 + '<' + '/a>'}
        ${mailLink}
    {/if}
    {if 'Url1' != ''}
        <h4>Website:</h4>
        {eval}
            if (Url1.length > 36) {
                urlText = Url1.substring(0, 36) + '...';
            }
            else {
                urlText = Url1;
            }
            urlLink = 'a href="' + Url1 + '" target="_blank"';
            
            urlTotal = '<' + urlLink + '>' + urlText + '<' + '/a>';
        {/eval}

        ${urlTotal}
    {/if}
</textarea>
  
<textarea cols="0" rows="0" id="nonlegislative-district-template" style="display:none">	
    &lt;div class="catBox" id="catBoxNonLeg"&gt;Non-Legislative&lt;/div&gt;	
    &lt;h1 class="capitalize"&gt;${DistrictType.replace(/_/g, " ")}
    {if DistrictSubType != ''}
        (${DistrictSubType.replace(/_/g, " ")})
    {/if}
    &lt;/h1&gt;
    &lt;h2&gt;
        {if isNaN(DistrictID)}
             &lt;span class="capitalize"&gt;${DistrictID.toLowerCase().replace(/sd/g,"S.D.")}&lt;/span&gt;
        {else}
            {eval}
            if (DistrictSubType == "HUC2") {
                distName = "Hydrologic Region";
                }
            else if (DistrictSubType == "HUC4") {
                distName = "Hydrologic Subregion";
                }					
            else if (DistrictSubType == "HUC6") {
                distName = "Basin";
                }
            else if (DistrictSubType == "HUC8") {
                distName = "Subbasin";
                LA = Label.split( /[,.]|\bbasin\b/ig );
                Label = LA[0];
                }			
            else if (DistrictSubType == "HUC10") {
                distName = "Watershed";
                }	
            else if (DistrictSubType == "HUC12") {
                distName = "Subwatershed";
                }
            else if (DistrictType == "COUNTY") {
                distName = "FIPS Code";
                }
            else if (DistrictType == "POLICE" &amp;&amp; Label == "PSA"){
                distName = "Police Service Area";
                if( Math.ceil( DistrictID ) !== NaN ){
                    DistrictID = Math.ceil( DistrictID );
                    }
                }
            else if ( DistrictType == "POLICE" &amp;&amp; ( City == "NEW YORK" || ( City == "PORTLAND" &amp;&amp; State == "OR" ) || City == "BOSTON" || City == "SEATTLE" || City == "BALTIMORE" || City == "DETROIT" ) ){
                distName = "Precinct";
                }
            else {
                distName = "District";
                }
            {/eval}
            {if DistrictType == 'watershed'}
                ${Label} ${distName}
            {elseif DistrictType == 'census'}
                ${Label} 
            {else}
                ${distName} ${DistrictID}
            {/if}
        {/if}
        {if City != ''}
            ${City}
            {if defined('State') &amp;&amp; State != ''},{/if}
        {/if}
        {if State != ''} ${State} {/if}
        {if Country != ''} ${Country} {/if}
    &lt;/h2&gt;

    &lt;h4&gt;${Label}&lt;/h4&gt;
</textarea>      

<textarea cols="0" rows="0" id="official-accordion-template" style="display:none;">
    <div class="elected-official" {if DistrictID.length > 27} title="${DistrictID}" {/if}>
    {if isNaN(DistrictID)}
        {eval}
            if( DistrictID == "AT LARGE" ){
                repDistrict = "At-Large";
            } else if( DistrictID.split(" ")[0].length > 2 || DistrictID.split(" ")[0] == "FT" || DistrictID.split(" ")[0] == "ST" || DistrictID.split(" ")[0] == "MT" ) { /*do not make state abbrevs lowercase - make exceptions for FT (WORTH), ST (LOUIS)*/
                repDistrict = DistrictID.toLowerCase();
            } else {
                repDistrict = DistrictID;
            }
            if (repDistrict.length > 27) {
                repDistrict = repDistrict.substring(0, 24) + '...';
            }
        {/eval}
        <span class="capitalize">${repDistrict}</span>
    {else}
        <span class="capitalize">District ${DistrictID}</span>
    {/if}
    
    {eval}
        repField = '';
    {/eval}
    {if defined('Title')}
        {eval}
            repField = Title + " ";
        {/eval}
    {/if}
    {if defined('FirstName')}
        {eval}
            repField = repField + FirstName + " ";
        {/eval}
    {/if}
    {if defined('LastName')}
        {eval}
            repField = repField + LastName;
        {/eval}
    {/if}
    {eval}
        if (repField.length > 45) {
            repField = repField.substring(0, 45) + '...';
        }
    {/eval}
    ${repField}
    </div>
</textarea>
   
<textarea cols="0" rows="0" id="nonlegislative-district-picker-template" style="display:none">
    <div class="picker-node">
        {if isNaN(DistrictID) && DistrictType != 'WATERSHED' && DistrictID.length > 1}
            <span class="capitalize">${DistrictID.toLowerCase().replace(/sd/g,"S.D.")}</span>
        {elseif DistrictType == "POLICE" && Label == "PSA"}
            {eval}
                displayDistrictID = Math.ceil(DistrictID)
            {/eval}
            <span class="capitalize">Police Service Area ${displayDistrictID}</span>
        {else}
            {eval}
                if (DistrictSubType == "COUNTY") {
                    distName = "FIPS Code";
                } else if (DistrictType == "SCHOOL"){
                    distName = "School District";
                } else if ( DistrictType == "POLICE" && ( City == "NEW YORK" || ( City == "PORTLAND" && State == "OR" ) || City == "BOSTON" || City == "SEATTLE" || City == "BALTIMORE" || City == "DETROIT" ) ){
                    distName = "Precinct";
                } else {
                    distName = "District";
                }
                DistrictType = DistrictType.toLowerCase();
            {/eval}
                {if DistrictType == 'watershed'}
                    {eval}
                        if (DistrictSubType == "HUC2") {
                            LA = Label.split( /[,.]|\bregion\b/ig );
                            Label = LA[0];
                            distName = "Hydrologic Region";
                        } else if (DistrictSubType == "HUC4") {
                            distName = "Hydrologic Subregion";
                        } else if (DistrictSubType == "HUC6") {
                            distName = "Basin";
                        } else if (DistrictSubType == "HUC8") {
                            distName = "Subbasin";
                            LA = Label.split( /[,.]|\bbasin\b/ig );
                            Label = LA[0];
                        } else if (DistrictSubType == "HUC12") {
                            distName = "Subwatershed";
                        } else if (DistrictSubType == "WSC_MDA") {
                            distName = "";
                        } else if (DistrictSubType == "WSC_SDA") {
                            distName = "Sub-Drainage Area";
                        } else if (DistrictSubType == "WSC_SSDA") {
                            distName = "Sub-Sub-Drainage Area";
                        } else {
                            distName = "Watershed";
                        }
                    {/eval}
                    ${Label} ${distName}
                {elseif DistrictType == 'national_lower_2010'}
                    Congressional District ${State}-${DistrictID}
                {elseif DistrictType == 'census' && Country == 'CA'}
                    {if DistrictSubType == 'BLOCK_GROUP'}
                        Census Dissemination Area ${DistrictID}
                    {elseif DistrictSubType == 'TRACT'}
                        Census Tract ${DistrictID}
                    {elseif DistrictSubType == 'URBAN_AREA'}
                        ${Label} Census Metropolitan Area
                    {/if}
                {elseif defined('Label') && Label != '' && $.inArray(DistrictType, ['county', 'census']) !== -1}
                    ${Label}
                {else}
                    <span class="capitalize">${DistrictType.replace(/_(2010)?/g, " ")}</span> ${distName} ${DistrictID}
                {/if}
        {/if}
    </div>
</textarea>