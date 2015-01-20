function locations_initialize() {

	for (var key in locations) {
		if (locations.hasOwnProperty(key)) {
			locations_initialize_map(key, locations[key]);
		}
	}

}

function locations_get_canvas_id(id) {
	return 'locations-map-canvas-' + id;
}

function locations_initialize_map(id, options) {

	var mapOptions = {
		center: { lat: -34.397, lng: 150.644},	
		zoom: parseInt(options.zoom)
	};

	var canvasId = locations_get_canvas_id(id);
        var map = new google.maps.Map(document.getElementById(canvasId), mapOptions);

	for (var i = 0; i < options.pins.length; i++) {

		var pin = options.pins[i];
		var latlng = new google.maps.LatLng(pin.lat, pin.lng);
		var marker = new google.maps.Marker({
			position: latlng,
			map: map,
			title: pin.name
		});

		map.setCenter(latlng);

	}

	locations_map_instances[id] = map;
}

var locations = [];
var locations_map_instances = {};

google.maps.event.addDomListener(window, 'load', locations_initialize);

function setLocation(id, index) {
	var map = locations_map_instances[id];
	var location = locations[id];
	var pin = location.pins[index];
	map.setCenter(new google.maps.LatLng(pin.lat, pin.lng));
}
