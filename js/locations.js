
function locations_initialize() {

	var mapOptions = {
          center: { lat: -34.397, lng: 150.644},
          zoom: 8
        };
        var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

}

google.maps.event.addDomListener(window, 'load', locations_initialize);

var markers = new Array();
var global_maps = new Array();
var global_locations = new Array();

var map_instances = new Array();

var activedetails = undefined;


function updateAddress(id, index, details)
	{
	setLocation(id, index);
	
	if (activedetails != undefined)
		{
		document.getElementById(activedetails).innerHTML = "";
		}
	
	if (activedetails != details)
		{
		for (var i=0; i<map_instances.length; i++)
			{
			if (map_instances[i].id == id)
				{
				activedetails = details;
				document.getElementById(details).innerHTML = locationText(global_maps[i], i, index);
				}
			}
		}
	else
		{
		activedetails = undefined;
		}
	}


function setLocation(id, index)
	{
	for (var i=0; i<map_instances.length; i++)
		{
		if (map_instances[i].id == id)
			{
			selectLocation(global_maps[i], i, index);
			}
		}
	}


function selectLocation(map, index, location)
	{
	if (markers[index][location] != undefined)
		{
		markers[index][location].openInfoWindowHtml(locationText(map, index, location));
		}
	else
		{
		// alert("Location Unknown");
		}
	}
	
		
function locationText(map, index, location)
	{
	if (map_instances[index].locations[location].image != "")
		{
		return "<p style='text-align: center; font-family: verdana; font-size: 11px; font-weight: bold; line-height: 2em;'>" + map_instances[index].locations[location].name + "<br/><img style='border: 1px solid #777;' src='" + map_instances[index].locations[location].image + "' height='100' width='100' /></p>";
		}
	else
		{
		return "<p style='text-align: center; font-family: verdana; font-size: 11px; font-weight: bold; line-height: 2em;'>" + map_instances[index].locations[location].name.replace(/\n/g, '<br />') + "<br/></p>";
		}
	}
	
function Location(lat, lng, name, image)
	{
	this.point = new GLatLng(lat, lng);
	this.name  = name;
	this.image = image;
	}

function createMarker(map, index, location)
	{
	var marker = new GMarker(map_instances[index].locations[location].point);
	GEvent.addListener(marker, "click", function() { marker.openInfoWindowHtml(locationText(map, index, location)); });
	return marker;
	}
	
	
var geocoder = null;
var lookupQueue = new Array();
var lookupActive = false;

geocoder = new GClientGeocoder();

function processQueue()
	{
	if (lookupActive == false && lookupQueue.length > 0)
		{
		lookupActive = true;
		if (geocoder)
			{
			geocoder.getLatLng
				(
				lookupQueue[0].geocode,
				function(point)
					{
					if (!point)
						{
						var map      = lookupQueue[0].map;
						var index    = lookupQueue[0].index;
						var location = lookupQueue[0].location;
						getLocation(index, location).point = undefined;
						addLocation(map, index, location);						
						}
					else
						{
						var map      = lookupQueue[0].map;
						var index    = lookupQueue[0].index;
						var location = lookupQueue[0].location;
						getLocation(index, location).point = point;
						addLocation(map, index, location);
						}
					lookupQueue.shift();
					lookupActive = false;
					processQueue();
	  				}
				);
			}
		}
	}

function geoLookup(geocode, map, index, location)
 	{
 	lookupQueue.push({"geocode": geocode, "map": map, "index": index, "location": location});
	processQueue();
  	}

function map(settings)
	{
	map_instances.push(settings);
	var index = map_instances.length-1;
	document.write("<div class='map' id='map" + index + "'></div>");
	setTimeout("loadMap('" + index + "');", 1);
	}

function loadMap(index)
	{
	var id = "map" + index;
	
	if (GBrowserIsCompatible())
		{
		var map = new GMap2(document.getElementById(id));
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl());

		setMap(map, index);
		
		var i;
		for (i=0; i<map_instances[index].locations.length; i++)
			{
			if (map_instances[index].locations[i].point == undefined)
				{
				geoLookup(map_instances[index].locations[i].geocode, map, index, i);
				}
			}
		
		markers[index][0].openInfoWindowHtml(locationText(map, index, 0));
		}
	}

function getLocation(index, location)
	{
	return map_instances[index].locations[location];
	}
	
function addLocation(map, index, location)
	{
	if (getLocation(index, location).point != undefined)
		{
		var marker = createMarker(map, index, location);
		map.addOverlay(marker);
		if (markers[index].length > location)
			{
			markers[index][location] = marker;
			}
		else
			{
			markers[index].push(marker);
			}
		}
	else // Allow adding a dummy point so that failed geocoded addresses do not shift the array index.
		{
		if (markers[index].length > location)
			{
			markers[index][location] = undefined;
			}
		else
			{
			markers[index].push(undefined);
			}
		}
	}
	
function setMap(map, index)
	{
	markers.push(new Array());
	map.setCenter(map_instances[index].locations[0].point, map_instances[index].zoom);

	for (var i=0; i<map_instances[index].locations.length; i++)
		{
		addLocation(map, index, i);
		}
		
	if (map_instances[index].route == 1)
		{
		addRoute(map, index);
		}
		
	}
	
function addRoute(map, index)
	{
	var points = [];
	for	(var i=0; i<map_instances[index].locations.length; i++)
		{
		points.push(map_instances[index].locations[i].point);
		}
	map.addOverlay(new GPolyline(points));
	markers[index][0].openInfoWindowHtml(locationText(map, index, 0));	
	}

