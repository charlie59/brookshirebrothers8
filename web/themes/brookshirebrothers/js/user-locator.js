(function ($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.UserLocator = {
    attach: function (context, settings) {
      function stopLookup() {
        //
      }
      let cookie = $.cookie('userZip');
      if (cookie == null) {
        if ("geolocation" in navigator) {
          navigator.geolocation.getCurrentPosition(function (position) {
              let pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
              };
              let userZip;
              let latLng = pos.lat + "," + pos.lng;
              let key = "AIzaSyAQNRPaZd4ibswz8dB7gpOZyajfvtkRaAI";
              // https://developers.google.com/maps/documentation/geocoding/intro#reverse-restricted
              $.getJSON('https://maps.googleapis.com/maps/api/geocode/json?latlng=' + latLng + '&result_type=street_address&key=' + key, function (data) {
                let timer = setTimeout(stopLookup, 15000); // bail after 15 seconds
                // this check should take care of errors
                if (data.status === 'OK') {
                  if (data.results[0]) {
                    /**
                     * @typedef {Object} obj
                     * @property {string} address_components
                     */
                    let obj = data.results[0];
                    let j = 0;
                    for (j = 0; j < obj.address_components.length; j++) {
                      if (obj.address_components[j].types[0] === 'postal_code') {
                        userZip = obj.address_components[j].short_name;
                        break;
                      }
                    }
                    // set cookie
                    document.cookie = "userZip=" + userZip;
                  }
                }
              });
            }
          );
        }
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
