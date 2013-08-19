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
error_log("Entering view function");
$get_officials=$this->action('get_elected_officials');
$get_new_legislative_districts=$this->action('get_new_legislative_districts');
$get_districts=$this->action('get_nonlegislative_districts');
$get_maps=$this->action('get_maps');
error_log("Get maps is: ".$get_maps);
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
        if (office.district.district_type.indexOf('local') >= 0) {
            catBox = '<' + 'div class="catBox" id="catBoxLocal">Local<' + '/div>';
        }
        else if (office.district.district_type.indexOf('state') >= 0) {
            catBox = '<' + 'div class="catBox" id="catBoxState">State<' + '/div>';
        }
        else if (office.district.district_type.indexOf('national') >= 0) {
            catBox = '<' + 'div class="catBox" id="catBoxNational">National<' + '/div>';
        }
        else {
            catBox = '';	
        }
    {/eval}
    
    ${catBox}

    <h1>
    {if isNaN(office.district.district_id)}
        ${office.district.district_id}
    {else}
        District ${office.district.district_id}
    {/if}
    </h1>
    
    {if office.representing_state != ''}
        {if office.representing_city != ''}
        <h2>${office.representing_city}, ${office.representing_state}, ${office.district.country}</h2>
        {else}
        <h2>${office.representing_state}, ${office.district.country}</h2>
        {/if}
    {else}
        <h2>${office.district.country}</h2>
    {/if}
    
    <h3>
    {if office.title !== ''}
        ${office.title}
    {/if}
    {if defined('first_name')}
        ${first_name}
    {if defined('last_name')}
    {/if}
        ${last_name}
    {/if}
    {if party != ''}
        (${party})
    {/if}
    </h3>

    
    {if addresses[0].address_1 !== ''}
        {var primary = addresses[0]}
        <h4>Primary Address</h4>
        ${primary.address_1}<br />
    {/if}
    {if primary.address_2 != ''}
        ${primary.address_2}<br />
    {/if}
    {if primary.address_3 != ''}
        ${primary.address_3}<br />
    {/if}
    {if primary.state != ''}
        {if primary.city != ''}
            ${primary.city},
        {/if}
        ${primary.state}
    {else}
        {if primary.city != ''}
            ${primary.city}&nbsp;&nbsp;
        {/if}
    {/if}
    {if primary.postal_code != ''}
        ${primary.postal_code|capitalize}
    {/if}<br />
    
    {if primary.phone_1 != ''}
        {if primary.fax_1 != ''}
            <div class = "half">
                <h4 id="H1">Phone</h4> 
                ${primary.phone_1}
            </div>
            
            <div class="half">
                <h4>Fax</h4>
                ${primary.fax_1}
            </div>
        {else}
            <div>
                <h4 id="PrimaryPhone1">Phone</h4> 
                ${primary.phone_1}
            </div>
        {/if}
    {elseif primary.fax_1 != ''}
        <div>
            <h4 id="fax">Fax</h4>
            ${primary.fax_1}
        </div>
    {/if}
    
    {if email_addresses[0] != ''}
        {var email1 = email_addresses[0]}
        <h4>Email</h4> 
        {var mailLink = '<' + 'a href="mailto:' + email1 + '" target="_blank"' + '>' + email1 + '<' + '/a>'}
        ${mailLink}
    {/if}
    {if urls[0] != ''}
        {var url1 = urls[0]}
        <h4>Website:</h4>
        {eval}
            if (url1.length > 36) {
                urlText = url1.substring(0, 36) + '...';
            }
            else {
                urlText = url1;
            }
            urlLink = 'a href="' + url1 + '" target="_blank"';
            
            urlTotal = '<' + urlLink + '>' + urlText + '<' + '/a>';
        {/eval}

        ${urlTotal}
    {/if}
</textarea>
  
<textarea cols="0" rows="0" id="nonlegislative-district-template" style="display:none">	
    &lt;div class="catBox" id="catBoxNonLeg"&gt;Non-Legislative&lt;/div&gt;	
    &lt;h1 class="capitalize"&gt;${district_type.replace(/_/g, " ")}
    {if subtype != ''}
        (${subtype.replace(/_/g, " ")})
    {/if}
    &lt;/h1&gt;
    &lt;h2&gt;
        {if isNaN(district_id)}
             &lt;span class="capitalize"&gt;${district_id.toLowerCase().replace(/sd/g,"S.D.")}&lt;/span&gt;
        {else}
            {eval}
            if (subtype == "HUC2") {
                distName = "Hydrologic Region";
                }
            else if (subtype == "HUC4") {
                distName = "Hydrologic Subregion";
                }					
            else if (subtype == "HUC6") {
                distName = "Basin";
                }
            else if (subtype == "HUC8") {
                distName = "Subbasin";
                LA = label.split( /[,.]|\bbasin\b/ig );
                label = LA[0];
                }			
            else if (subtype == "HUC10") {
                distName = "Watershed";
                }	
            else if (subtype == "HUC12") {
                distName = "Subwatershed";
                }
            else if (district_type == "COUNTY") {
                distName = "FIPS Code";
                }
            else if (district_type == "POLICE" &amp;&amp; label == "PSA"){
                distName = "Police Service Area";
                if( Math.ceil( district_id ) !== NaN ){
                    district_id = Math.ceil( district_id );
                    }
                }
            else if ( district_type == "POLICE" &amp;&amp; ( city == "NEW YORK" || ( city == "PORTLAND" &amp;&amp; state == "OR" ) || city == "BOSTON" || city == "SEATTLE" || city == "BALTIMORE" || city == "DETROIT" ) ){
                distName = "Precinct";
                }
            else {
                distName = "District";
                }
            {/eval}
            {if district_type == 'watershed'}
                ${label} ${distName}
            {elseif district_type == 'census'}
                ${label} 
            {else}
                ${distName} ${district_id}
            {/if}
        {/if}
        {if city != ''}
            ${city}
            {if defined('state') &amp;&amp; state != ''},{/if}
        {/if}
        {if state != ''} ${state} {/if}
        {if country != ''} ${country} {/if}
    &lt;/h2&gt;

    &lt;h4&gt;${label}&lt;/h4&gt;
</textarea>      

<textarea cols="0" rows="0" id="official-accordion-template" style="display:none;">
    <div class="elected-official" {if office.district.district_id.length > 27} title="${office.district.district_id}" {/if}>
    {if isNaN(office.district.district_id)}
        {eval}
            if( office.district.district_id == "AT LARGE" ){
                repDistrict = "At-Large";
            } else if( office.district.district_id.split(" ")[0].length > 2 || office.district.district_id.split(" ")[0] == "FT" || office.district.district_id.split(" ")[0] == "ST" || office.district.district_id.split(" ")[0] == "MT" ) { /*do not make state abbrevs lowercase - make exceptions for FT (WORTH), ST (LOUIS)*/
                repDistrict = office.district.district_id.toLowerCase();
            } else {
                repDistrict = office.district.district_id;
            }
            if (repDistrict.length > 27) {
                repDistrict = repDistrict.substring(0, 24) + '...';
            }
        {/eval}
        <span class="capitalize">${repDistrict}</span>
    {else}
        <span class="capitalize">District ${office.district.district_id}</span>
    {/if}
    
    {eval}
        if (office.title !== undefined) {
            repField = office.title + " ";
        }
    {/eval}
    {if defined('first_name')}
        {eval}
            repField = repField + first_name + " ";
        {/eval}
    {/if}
    {if defined('last_name')}
        {eval}
            repField = repField + last_name;
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
        {if isNaN(district_id) && district_type != 'WATERSHED' && district_id.length > 1}
            <span class="capitalize">${district_id.toLowerCase().replace(/sd/g,"S.D.")}</span>
        {elseif district_type == "POLICE" && label == "PSA"}
            {eval}
                displaydistrict_id = Math.ceil(district_id)
            {/eval}
            <span class="capitalize">Police Service Area ${displaydistrict_id}</span>
        {else}
            {eval}
                if (subtype == "COUNTY") {
                    distName = "FIPS Code";
                } else if (district_type == "SCHOOL"){
                    distName = "School District";
                } else if ( district_type == "POLICE" && ( city == "NEW YORK" || ( city == "PORTLAND" && state == "OR" ) || city == "BOSTON" || city == "SEATTLE" || city == "BALTIMORE" || city == "DETROIT" ) ){
                    distName = "Precinct";
                } else {
                    distName = "District";
                }
                district_type = district_type.toLowerCase();
            {/eval}
                {if district_type == 'watershed'}
                    {eval}
                        if (subtype == "HUC2") {
                            LA = label.split( /[,.]|\bregion\b/ig );
                            label = LA[0];
                            distName = "Hydrologic Region";
                        } else if (subtype == "HUC4") {
                            distName = "Hydrologic Subregion";
                        } else if (subtype == "HUC6") {
                            distName = "Basin";
                        } else if (subtype == "HUC8") {
                            distName = "Subbasin";
                            LA = label.split( /[,.]|\bbasin\b/ig );
                            label = LA[0];
                        } else if (subtype == "HUC12") {
                            distName = "Subwatershed";
                        } else if (subtype == "WSC_MDA") {
                            distName = "";
                        } else if (subtype == "WSC_SDA") {
                            distName = "Sub-Drainage Area";
                        } else if (subtype == "WSC_SSDA") {
                            distName = "Sub-Sub-Drainage Area";
                        } else {
                            distName = "Watershed";
                        }
                    {/eval}
                    ${label} ${distName}
                {elseif district_type == 'national_lower_2010'}
                    Congressional District ${state}-${district_id}
                {elseif district_type == 'census' && country == 'CA'}
                    {if subtype == 'BLOCK_GROUP'}
                        Census Dissemination Area ${district_id}
                    {elseif subtype == 'TRACT'}
                        Census Tract ${district_id}
                    {elseif subtype == 'URBAN_AREA'}
                        ${label} Census Metropolitan Area
                    {/if}
                {elseif defined('label') && label != '' && $.inArray(district_type, ['county', 'census']) !== -1}
                    ${label}
                {else}
                    <span class="capitalize">${district_type.replace(/_(2010)?/g, " ")}</span> ${distName} ${district_id}
                {/if}
        {/if}
    </div>
</textarea>
