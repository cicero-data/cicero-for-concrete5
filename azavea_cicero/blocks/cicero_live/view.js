/*
 * This is the javascript required for the Cicero Live module for Concrete 5
 *
 * Authors: Andrew Jennings, Joseph Tricarico
 * Copyright Azavea, Inc. 2010-2012
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
 * 
 */
var _gaq = _gaq || [];
$( function() {
    // Tile layers and map
    var map;
    var layerEsri;
    var layerBing;
    var layerDistrictMaps;
    // Our geocoder objects
    var geocoderNA;
    var geocoderEU;
    var geocoderVE;
    // Keeping some state
    var currentlySelectedAddress;
    var currentCountry = '';
    var countryPreferences;
    var eventHandles = [];
    // Geography to know
    var srid = new esri.SpatialReference({wkid:102100});
	var extentCA = new esri.geometry.Extent(-16000000, 5000000, -5000000, 13000000, srid);
    var extentUK = new esri.geometry.Extent(-1491312, 6042077, 465475, 8488062, srid);
    var extentUS = new esri.geometry.Extent(-14538196, 93441, -6711044, 9877381, srid);
    var extentAU = new esri.geometry.Extent(10743503, -7909820, 18570655, 1874118, srid);
    var extentNZ = new esri.geometry.Extent(18145054, -6295470, 20101842, -3849485, srid);
    // Templates
    var officialTemplate;
    var districtTemplate;

    var init = function() {
        initGeography();
        initCountryPreferences();
        dojo.connect(map, 'onLoad', function(){
			$('#country-selector input[value="' + clientCountry + '"]').click();
			map.disableMapNavigation();
			if( prepopAddress != '' ) {
				$('#address-input').val(prepopAddress);
                $('#geocode-button').trigger('click', [true]); // param indicates non-interaction 
			}
		});
        $('#address-input').focus();
    };

    /* Set up our map and geocoders */
    var initGeography = function() {
        layerEsri = new esri.layers.ArcGISTiledMapServiceLayer(
            // 'http://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer'
            'http://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer'
        );
        layerEsriRef = new esri.layers.ArcGISTiledMapServiceLayer(
            'http://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Reference/MapServer'
        );
        layerBing = new esri.virtualearth.VETiledLayer({
            bingMapsKey: bingKey,
            mapStyle: esri.virtualearth.VETiledLayer.MAP_STYLE_ROAD,
            visible:false
        });
        layerDistrictMaps = new esri.layers.MapImageLayer({visible:false});

        map = new esri.Map("map", { extent: extentUS, slider:false, logo:false, wrapAround180:true });
        map.addLayer(layerEsri);
        map.addLayer(layerEsriRef); // place name labels
        map.addLayer(layerBing);
        map.addLayer(layerDistrictMaps);
        

        geocoderVE = new esri.virtualearth.VEGeocoder({
            bingMapsKey: bingKey
        });
        geocoderNA = new esri.tasks.Locator(
            "http://tasks.arcgisonline.com/ArcGIS/rest/services/Locators/TA_Address_NA_10/GeocodeServer"
        );
        geocoderEU = new esri.tasks.Locator(
            "http://tasks.arcgisonline.com/ArcGIS/rest/services/Locators/TA_Address_EU/GeocodeServer"
        );
    };

    initCountryPreferences = function() {
        countryPreferences = { 
            'CA' : {
                geocoder: geocoderNA,
                placeholder: 'Address, City, Province/Territory, Postal Code',
                showVELayer: false,
                extent: extentCA
            },	'US' : {
                geocoder: geocoderNA,
                placeholder: 'Address, City, State, ZIP Code',
                showVELayer: false,
                extent: extentUS
            }, 'UK' : {
                geocoder: geocoderEU,
                placeholder: 'Address, City, Postal Code',
                showVELayer: false,
                extent: extentUK
            }, 'AU' : {
                geocoder: geocoderVE,
                placeholder: 'Address, City, State/Territory, Postal Code',
                showVELayer: true,
                extent: extentAU
            }, 'NZ' : {
                geocoder: geocoderVE,
                placeholder: 'Address, City, Postal Code',
                showVELayer: true,
                extent: extentNZ
            }
        };
    };
	
	var lastAddressInput = null;
	var lastAddressCountry = null;
	var addressCookieName = 'cicero_live_addresses';
	
	var getCookieArray = function() {
		var cookieArray = $.parseJSON($.cookie(addressCookieName)); //turn our cookie val to array
		if(cookieArray === null) {
			cookieArray = new Array(); //turn null into empty array so other functions dont get confused
		}
		return cookieArray;
	}
	
	var cookieArray = getCookieArray();
	
	var resetAutocompleter = function() {
		$('#address-input').autocomplete({
			delay: 0,
			select: function() {
				$('#geocode-button').click();
			},
			source: cookieArray
		});
	}
	resetAutocompleter(); //initialize
	
	var getAddressInput = function(){
		var clean_address = $.trim($('#address-input').val());
		if(clean_address!=='' && $.inArray(clean_address,cookieArray)===-1) { //don't want any empty strings in our cookie array!
			cookieArray.push(clean_address); //update our cookie array
			resetAutocompleter(); //remake the autocompleter using phrases from our new array
			$.cookie(addressCookieName, JSON.stringify(cookieArray)); //update actual cookie
		}
		return clean_address;
	}

	var geocode = function(addressInput) {
		switch( currentCountry ) {
			case 'CA':
			case 'US':
				var address = {SingleLine:addressInput, Country:currentCountry};
				geocoderNA.addressToLocations(address, ["Loc_name"]);
				break;
			case 'AU':
			case 'NZ':
				geocoderVE.addressToLocations(addressInput + ', ' + currentCountry );
				break;
			case 'UK':
				//var REstreetType = /\s+(ave?(nue)?|cl(ose)?|cres(cent)?|c(our)?t|dr(ive)?|est(ate)?|ga?r?de?ns|gr(ove)?|la?n?e?|p(ara)?de|pa?r?k|pl(ace)?|r(oa)?d|sq(uare)?|st(reet)?|ter+(ace)?)/i;
				var REunitNumbered = /(su?i?te|p\W*[om]\W*b(?:ox)?|(?:ap|dep)(?:ar)?t(?:me?nt)?|ro*m|flo*r?|uni?t|bu?i?ldi?n?g|ha?nga?r|lo?t|pier|slip|spa?ce?|stop|tra?i?le?r|bo?x|no\.?)\s+|#/i;
				var REunitNotNumbered = /(ba?se?me?n?t|fro?nt|lo?bby|lowe?r|off?i?ce?|pe?n?t?ho?u?s?e?|rear|side|uppe?r)/i;
				var REpostcode = /[A-Z]{1,2}[0-9R][0-9A-Z]? *[0-9][A-Z]{0,2}/ig;
				var REnotBlank = /\S/;
				var address = {
					Country: 'GB'
				};//define address obj & set address.Country
				var postcodeMatch = addressInput.match( REpostcode );//get postcode using the unsplit string
				if( postcodeMatch != null ){
					var postcode = postcodeMatch.pop();
					address.Postcode = postcode;
				}//postcodeMatch != null
                
				var addressInputPieces = addressInput.split(',');//make an array, splitting user input on commas
                
				if( address.Postcode && addressInputPieces[0].search(address.Postcode) != -1 ){//first element
					addressInputPieces[0] = addressInputPieces[0].split(address.Postcode,2);//split into 2 pieces on postcode
					//if first part is not empty, set as address
					if( REnotBlank.test( addressInputPieces[0][0] ) ){
						address.Address = $.trim( addressInputPieces[0][0] );
					}
				}else{
					address.Address = $.trim( addressInputPieces[0] );
				}//endif postcode exists && exists within this element
				addressInputPieces[0] = $.trim(addressInputPieces[0]);//trim the element
				for( var e = 1; e < addressInputPieces.length; e++ ){//loop through elements 1-n
					addressInputPieces[e] = $.trim(addressInputPieces[e]);//trim the element
					if( address.Postcode && addressInputPieces[e].search(address.Postcode) != -1 ){
						addressInputPieces[e] = $.trim( addressInputPieces[e].replace( address.Postcode, "" ) );//delete postcode from element
					}//endif postcode exists && exists within this element
					if( REunitNumbered.test( addressInputPieces[e] ) || REunitNotNumbered.test( addressInputPieces[e] ) ){
						if( address.Address != null){
							address.Address = address.Address + ', ' + addressInputPieces[e];//append
						}else{
							address.Address = addressInputPieces[e];//create
						}//endif
					}else{ //not a secondary address, send to geocoder as a city:
						if( address.City != null){
							address.City = address.City + ', ' + addressInputPieces[e];////append
						}else{
							address.City = addressInputPieces[e];//create
						}
					}//endif secondary address
				}//endfor
				geocoderEU.addressToLocations(address, ["Loc_name"]);
		}
	}

    var emptyAccordionHeaders = [ 'local', 'national', 'state', 'new legislative districts', 'non-legislative districts' ];
    var emptyAccordion = function() {
        var accordionNode = $('<div id="officials-accordion"></div>');
        $.each(emptyAccordionHeaders, function() {
            accordionNode.append('<h3 class="official-type-root h3 capitalize"><a href="#">' + this + '</a></h3><div class="officials-list">Enter an address to search.</div>');
        });
        return accordionNode;
    }();
    
    //var emptyAccordion = function() {
    //    $('#officials-accordion div').empty();
    //}

    var resetPageState = function(saveAddress) {
        if (saveAddress !== true) {
            $('#address-input').val('');
        }
		$('#address-permalink').hide();
		$('#address-candidates-note').hide();
        $('#locations').empty();
        $('#official-info').hide();
        $('#officials-content').empty().append(
            emptyAccordion.accordion({ autoHeight:false, clearStyle:true, active:false })
        );
        if (map.graphics.graphics.length > 0) {
            map.graphics.clear();
        }
        layerDistrictMaps.removeAllImages();
        var extent = countryPreferences[currentCountry]['extent'];
        map.setExtent(extent);
    };
    
    // Google Analytics Event Tracking
    var searches = [];
    var logCategory = 'Cicero Live Demo';
    var logSearch = function(input, opt_noninteraction){
        _gaq.push(['_trackEvent', logCategory, 'Search', input, 1, opt_noninteraction]);
    }
    var escapeAddress = function(addressString) {
        return addressString.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }
    var logGeocode = function(search, candidates){
        // Asynchronously log Google Event "Geocode"
        //
        // Arguments:
        // str input                -- concatenated address input and country selection
        // array candidates         -- array of address candidates
        // bool opt_noninteraction  -- True if not triggered by user
        response_time_ms = new Date().getTime()-search[2];
        candidates_addresses = []
        for (x in candidates)
        {
            // make JSON happy by escaping " and \; make array of "-surrounded addresses
            var escaped_address = 'Could not get address.';
            if(candidates[x].address !== undefined) {
                if(candidates[x].address.addressLine !== undefined) {
                    escaped_address = escapeAddress(candidates[x].address.addressLine);
                } else if(typeof(candidates[x].address) === 'string') {
                    escaped_address = escapeAddress(candidates[x].address);
                }
            }
            candidates_addresses.push('"'+escaped_address+'"')
        }
        candidates_str = candidates_addresses.join(", \n    ");
        label = '{"input":"'+search[0]+'", '+'\n"candidates":['+candidates_str+'], '+'}';
        opt_noninteraction = search[1]
        _gaq.push(['_trackEvent', logCategory, 'Geocode', label, response_time_ms, opt_noninteraction]);
    }
    var logAPICall = function(address, opt_noninteraction){
        // Asynchronously log Google Event "API Call"
        //
        // Arguments:
        // str address              -- selected address node
        // bool opt_noninteraction  -- True if not triggered by user
        _gaq.push(['_trackEvent', logCategory, 'API Call', address, 1, opt_noninteraction]);
    }
    var logAPICallSuccess = function(address, response_time_ms, opt_noninteraction){
        // Asynchronously log Google Event "API Response"
        //
        // Arguments:
        // str address              -- selected address node
        // int response_time_ms     -- callCicero() response time in milliseconds
        // bool opt_noninteraction  -- True if not triggered by user
        _gaq.push(['_trackEvent', logCategory, 'API Response', address, response_time_ms, opt_noninteraction]);
    }    

    $('#country-selector input').click( function() {
        var button = $(this);
		$('.selected-country').removeClass("selected-country");//remove selected-country style from all buttons
		button.addClass("selected-country");//add selected-country style to this button
		
        var thisCountry = button.val();
        if (currentCountry === thisCountry) {
            return false;
        }
        currentCountry = thisCountry;

        $.each(eventHandles, function() { dojo.disconnect(this); });
        eventHandles = [];

        resetPageState();

        var preferences = countryPreferences[thisCountry];
        eventHandles.push(dojo.connect(preferences['geocoder'], "onAddressToLocationsComplete", showGeocodingResults));
        layerEsri.setVisibility(!preferences['showVELayer']);
        layerBing.setVisibility(preferences['showVELayer']);
        map.setExtent(preferences['extent']);
        $('#address-input').attr('placeholder', preferences['placeholder']);
        $('#geocode-button').click( function(event, opt_noninteraction) {
            // handle the non-interaction param, if it is not set, make it false (e.g. interactive trigger):
            opt_noninteraction = typeof(opt_noninteraction) != 'undefined' ? opt_noninteraction : false;
            
			var addressInput = getAddressInput();
			if( lastAddressInput !== addressInput || lastAddressCountry !== currentCountry ){
                // TODO: asynchronously log Google Analytics search event:
                logSearch(addressInput, opt_noninteraction)
                gaEventLabel = addressInput+'; '+currentCountry
				lastAddressInput = addressInput;
                searches.push([addressInput, opt_noninteraction, new Date().getTime()]);
				lastAddressCountry = currentCountry;
				resetPageState(true);
				geocode(addressInput);
			}
		} );
    });

    var typeOrder = ['national_exec', 'national_upper', 'national_lower', 'state_exec', 'state_upper', 'state_lower', 'local_exec', 'local'];
	var backwardsType = typeOrder.reverse();//reverse so we have local officials first

    var appendOfficials = function(officials, root, header, currentType) {
        if (officials.length == 0) return root;
		if (officials.DistrictType !== undefined){//this is just a single result, put it into an array for use below
			oldOfficials = officials;
			officials = []; //clear out officials
			officials[0] = oldOfficials;
		}
		var currentOfficial = officials.shift();
		thisType = currentOfficial.office.district.district_type.split('_')[0];
		if (typeof currentType === "undefined" || thisType !== currentType) {
			currentType = thisType;
			var node = $('<h3 class="official-type-root h3 capitalize"><a href="#">' + currentType.toLowerCase() + '</a></h3>');
			var currentHeader = $('<div class="officials-list" />');
			root.append(node);
			node.after(currentHeader);
			header = currentHeader;
		}
		var officialAccordionTemplate;
		if (officialAccordionTemplate === undefined) {
			officialAccordionTemplate = TrimPath.parseDOMTemplate('official-accordion-template');
		} try { // Populate accordion section
			var html = officialAccordionTemplate.process(currentOfficial);
			var officialNode = $(html);
		} catch (error) {
			showErrorDialog('Sorry', 'There\'s been an error. The system cannot display official/district information. [' + error + ']');
		}
		var city = null;
		var state = null;
		if (currentOfficial.office.representing_city !== undefined){
			city = currentOfficial.office.representing_city;
		}
		if (currentOfficial.office.representing_state !== undefined){
			state = currentOfficial.office.representing_state;
		}
		officialNode.data('officialInfo', currentOfficial);
		officialNode.data('mapRequest', {
			ID: currentOfficial.office.district.id,
			//city: city,
			//state: state,
			//country: currentOfficial.office.district.country,
			districtType: currentOfficial.office.district.district_type,
			imgHeight: map.height,
			imgWidth: map.width
		});
		officialNode.click( showOfficial );
		officialNode.click( showMap );
		header.append(officialNode);
		return appendOfficials(officials, root, header, currentType);
    };

    var appendNonLegislativeDistricts = function(districts, root, headerText, lat, lon, fillColor) {
        var header = $('<h3 class="official-type-root h3"><a href="#">'+ headerText +'</a></h3>');
        if(fillColor === undefined){
            fillColor = "#53A8C8";
        }
        var districtDiv = $('<div class="district-list" />');
		var nonLegislativeDistrictTemplate;
		var allNull = true;
        $.each(districts, function( index, value ) {
			if( value !== null ){
				allNull = false;
				//generate HTML from template:
				if (nonLegislativeDistrictTemplate === undefined) {
					nonLegislativeDistrictTemplate = TrimPath.parseDOMTemplate('nonlegislative-district-picker-template');
				} try { // Populate accordion section
					var html = nonLegislativeDistrictTemplate.process( value );
					var officialNode = $(html);
				} catch (error) {
					//
				}
				var districtNode = $(html);
				districtNode.data('districtInfo', this);
				districtNode.data('mapRequest', {
					ID: this.id,
					//city: this.city,
					//state: this.state,
					//country: this.country,
					//lat: lat,
					//lon: lon,
					districtType: this.district_type,
					imgHeight: map.height,
					imgWidth: map.width,
                    fillColor: fillColor
				});
				districtNode.click( showDistrict );
				districtNode.click( showMap );
				districtDiv.append( districtNode );
			}//value !== null
        });
			
		if( allNull === false ) {
            var active = root.accordion('option', 'active');
			root.append(header).accordion('destroy').accordion({autoHeight: false});
			root.append(districtDiv).accordion('destroy').accordion({autoHeight: false}).accordion('activate', active);
		}
    };

    var showLocationMarker = function(geocoderInfo) {
        if (map.graphics.graphics.length > 0) {
            map.graphics.clear();
        }
        var markerUrl = $('#geocode-marker').attr('href');
        var symbol = new esri.symbol.PictureMarkerSymbol(markerUrl, 25, 32).setOffset(0, 14);
        var graphic = new esri.Graphic(geocoderInfo.location, symbol);
        map.graphics.add(graphic);
        map.centerAndZoom(geocoderInfo.location, 15);
    };

    var clearImages = function() {
        $.each(layerDistrictMaps.getImages(), function() {
            layerDistrictMaps.removeImage(this);
        });    
    }
    
    var showImage = function(mapImage, extent) {
        clearImages();
        layerDistrictMaps.addImage(mapImage);
		if( extent == null ){
			map.setExtent( mapImage.extent, true );
		} else {
			map.setExtent( extent );
		}
        layerDistrictMaps.setVisibility(true);
    };

    var showOfficial = function(event) {
        $('.elected-official').removeClass('selected-official');
        $('.picker-node').removeClass('selected-official');
        $(this).addClass('selected-official');
        var officialInfo = $(this).data('officialInfo');

        // If our template isn't parsed, parse it for performance
        if (officialTemplate === undefined) {
            officialTemplate = TrimPath.parseDOMTemplate('official-full-template');
        }
    
        // Update the elected official display
        try {
            var html = officialTemplate.process(officialInfo);
            $('#official-info').html(html);
			$('#official-info').show();
        } catch (error) {
            showErrorDialog('Sorry', 'There\'s been an error.  Cannot display information for that official');
        }
		
    };
    
    var showDistrict = function(event) {
        $('.elected-official').removeClass('selected-official');
        $('.picker-node').removeClass('selected-official');
        $(this).addClass('selected-official');
        var districtInfo = $(this).data('districtInfo');
        if (districtTemplate === undefined) {
            districtTemplate = TrimPath.parseDOMTemplate('nonlegislative-district-template');
        }
        try {
            var html = districtTemplate.process(districtInfo);
            $('#official-info').html(html);
			$('#official-info').show();
        } catch (error) {
            $('#official-info').hide();
        }
    };
    
	var getMapImage = function(node) {
		$.post($('#get-maps').attr('href'), $(node).data('mapRequest'), function(result) {
			try {
				var res = result;
				mapInfo = $.parseJSON(result)[0];
				var mapExtent = mapInfo.extent;
				mapExtent = new esri.geometry.Extent(mapExtent.x_min, mapExtent.y_min, mapExtent.x_max, mapExtent.y_max, srid);
				var mapImage = new esri.layers.MapImage({extent: mapExtent, height: map.height, width: map.width, href: mapInfo.url});
				$(node).data('mapImage', mapImage);
				$(node).click();
			} catch (error) {
				showErrorDialog('Sorry', 'There\'s been an error.  Cannot display a map for that official [' + error + ']');
			}
		});
	};
    
	var getCurrentDistImage = function(node) {
        request = $(node).data('mapRequest'),
        request.districtType = request.districtType.replace(/_2010/, '');
        request.fillColor = 'green';
		$.post($('#get-maps').attr('href'), request, function(result) {
			try {
				mapInfo = $.parseJSON(result);
				var mapExtent = mapInfo.MapExtent;
				mapExtent = new esri.geometry.Extent(mapExtent.x_min, mapExtent.y_min, mapExtent.x_max, mapExtent.y_max, srid);
				var mapImage = new esri.layers.MapImage({extent: mapExtent, height: map.height, width: map.width, href: mapInfo.MapUrl});
				$(node).data('currentDistImage', mapImage);
				$(node).click();
			} catch (error) {
				showErrorDialog('Sorry', 'There\'s been an error.  Cannot display a map for that official [' + error + ']');
			}
		});
	};
	
    var showMap = function(event) {
		$('#map-overlay').fadeTo(400, 0.4);
        var mapImage = $(this).data('mapImage');
        if (mapImage === undefined) {
            getMapImage( $(this) );
        } else {
			showImage( mapImage );
			$('#map-overlay').hide();
        }
    };
	
	var resizeMap = function() {
		$('#officials-content').hide();
		var newWidth = $('#officials-content').parent().width() - $('#officials-content').width() - 10;
		$('#map-column').animate(
            { width: newWidth + 'px' },
			'linear',
            function() {
                map.resize();
                $('#officials-content').fadeIn(250);
        });
	};

	var createOfficialsAccordion = function(officials) {
		officials.sort( function(a, b) {
			var a = makeSortString(a);
			var b = makeSortString(b);
			return a == b ? 0 : (a < b ? -1 : 1);//dont resort if same; move up if lexographic value is lower; else move down
        });
        var $accordion = appendOfficials(officials, $('<div id="officials-accordion" />'));
        return $accordion;
    };
	
	var makeSortString = function(a) {
		var dt = $.inArray(a.office.district.district_type.toLowerCase(), backwardsType);//will not work with > 10 type elements
		(a.office.district.district_type != 'AT LARGE') ? aL = 1 : aL = 2; //order at-large last
		a.title !== undefined ? t = a.title.toLowerCase() : t = 'zzzzz';
		a.last_name !== undefined ? ln = a.last_name.toLowerCase() : ln = 'zzzzz';
		a.first_name !== undefined ? fn = a.first_name.toLowerCase() : fn = 'zzzzz';
		if( t == 'council member' ){
			t = '0' ;//this will come before any other title
		}
		var r = dt + t + aL + ln + fn;//concat fields for sorting (ex: 7president2obamabarack)
		return r;
	}
	
	var startWaiting = function() {
		$('#cicero-live').addClass('waiting');
		map.setMapCursor('inherit');
	}
    
	var stillWaiting = function() {
		$('#cicero-live').removeClass('waiting');
        $('#cicero-live').addClass('still-waiting');
		map.setMapCursor('inherit');
	}
	
	var stopWaiting = function() {
		$('#cicero-live').removeClass('still-waiting');
		map.setMapCursor('auto');
	}
	
	var latestCiceroRequest;
	
    var callCicero = function(event, opt_noninteraction) {
        opt_noninteraction = typeof(opt_noninteraction) != 'undefined' ? opt_noninteraction : false;
        address=$(this).text();
        logAPICall(address, opt_noninteraction)
        $('#official-info').hide();
        $('#officials-content').empty();
        if (map.graphics.graphics.length > 0) {
            map.graphics.clear();
        }
        layerDistrictMaps.removeAllImages();
        if (currentlySelectedAddress === this) {
            return;
        }
        start_time = new Date().getTime()
		startWaiting();
        var currentlySelectedPrependText = 'Showing results for ';
        if (currentlySelectedAddress != undefined) {
            $(currentlySelectedAddress).text($(currentlySelectedAddress).text().substring(20));
        }
        permalinkUri = '?c=' + currentCountry + '&a=' + escape( address );
		$('#address-permalink').attr('href', permalinkUri);
		$('#address-permalink').show();
        $(this).text(currentlySelectedPrependText + address);
        $('#address-candidates-note').hide();
        $('span.address').remove();
        currentlySelectedAddress = this;
        
        var geocoder = $(this).data('geocoder');
        showLocationMarker(geocoder);
        var location4326 = esri.geometry.webMercatorToGeographic(geocoder.location);
        var officialsUrl = $('#get-elected-officials').attr('href') + '&latitude=' + location4326.y + '&longitude=' + location4326.x;
        
        var request = $.getJSON(officialsUrl, function(data, status, request) {
            if( request != latestCiceroRequest ){
				return false;
			}
            
            //officials = data.ElectedOfficialInfo
	    officials = data;
            if(officials === undefined)
            {
                if(data.message === undefined){
                    showErrorDialog("Error", "Could not get official info from response.");
                } else {
                    showErrorDialog(data.message, "Could not get official info from response.");
                }
            }
            else
            {
                var accordion = createOfficialsAccordion(officials);
                accordion.accordion({ autoHeight:false, clearStyle:true });
                $('#officials-content').empty().append(accordion);
                stillWaiting();
            }
            var nonlegislativeUrl = $('#get-nonlegislative-districts').attr('href') + '&latitude=' + location4326.y + '&longitude=' + location4326.x;
            var newDistrictURL = $('#get-new-legislative-districts').attr('href') + '&latitude=' + location4326.y + '&longitude=' + location4326.x;
            $.getJSON(newDistrictURL, function(newDistricts) {
                if(newDistricts.length > 0){
                    appendNonLegislativeDistricts(newDistricts, $('#officials-accordion'), 'New Legislative Districts', location4326.y, location4326.x);
                    $('#officials-accordion').accordion({ autoHeight:false, clearStyle:true });
                }
                $.getJSON(nonlegislativeUrl, function(nonLegDistricts) {
                    if(nonLegDistricts.length > 0){
                        appendNonLegislativeDistricts(nonLegDistricts, $('#officials-accordion'), 'Non-Legislative Districts', location4326.y, location4326.x);
                        $('#officials-accordion').accordion({ autoHeight:false, clearStyle:true });
                    }
                    stopWaiting();
                    response_time_ms = new Date().getTime()-start_time;
                    logAPICallSuccess(address, response_time_ms, opt_noninteraction);
                });
            });
        });
		latestCiceroRequest = request;
        return false;
    };

    var showGeocodingResults = function(candidates) {
        var candidate;
        candidates = $.grep(candidates, function(element, index) {
            if (element.calculationMethod && element.calculationMethod === 'Rooftop') {
                return true;
            }
            if (element.attributes && element.attributes.Loc_name.indexOf('Streets') < 0) {
                return false;
            }
            return true;
        });
        
        logGeocode(searches.shift(), candidates) // log GA event
        if (candidates.length == 0) {
            showErrorDialog('No geocoding results', 'No results for that address.  Please try a different address.');
            return false;
        }
				
        $.each(candidates, function() {
			//clear permalink
			$('#address-permalink').hide();
			$('#address-permalink').attr('href', '#');
			
            // Show the geocoder results
            var candidatetemplate = $('<div class="geocode-result"><span class="address" /></div>');
            var node = candidatetemplate.clone();
            this.location = esri.geometry.geographicToWebMercator(this.location);
            if (this.displayName) {
                node.find('.address').text(this.displayName);
            } else {
                node.find('.address').text(this.address);
            }
            node.data('geocoder', this);
            node.click(callCicero)
            $('#locations').append(node);

            if (this.score == 100 || this.confidence == 'high') {
				$('#address-permalink').show();
                node.trigger('click', true)
                return false;
            } else if ( $('#address-candidates-note').css('display') == 'none' ) {
				$('#address-candidates-note').show();
			}
        });

    };

    /*
     * Pop up a small Concrete5 error dialog
     */
    var showErrorDialog = function(title, message) {
        $.fn.dialog.open({
            title: title,
            element: $('<div id="cicero-live-error">' + message + '</div>'),
            width: 200,
            modal: false,
            height: 50
        });
    };

    dojo.require('esri.map');
    dojo.require('esri.tasks.locator');
    dojo.require('esri.virtualearth.VETiledLayer');
    dojo.require('esri.virtualearth.VEGeocoder');
    dojo.require('esri.layers.MapImageLayer');
    dojo.addOnLoad(init);
});


