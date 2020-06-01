(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.StoreLocator = {
    attach: function (context, settings) {
      let userZip = $.cookie('userZip');
      let searchBox = $("#search");
      if (userZip) {
        searchBox.once("StoreLocator").val(userZip);
      }
      $("#moreOptions").once("StoreLocator").click(function () {
        $("#moreOptionsSection").toggle();
      });

      let storeLocations;
      let locationCoordinates = drupalSettings.brookshireBrothers.storeLocator.locationCoordinates;
      console.log(locationCoordinates);
      let distanceSelect = $('#filter-distance');
      let overClass = 'over';
      let html;

      // Simple JavaScript Templating
      // John Resig - http://ejohn.org/ - MIT Licensed
      let cache = {};
      let tmpl = function tmpl(str, data) {
        // Figure out if we're getting a template, or if we need to
        // load the template - and be sure to cache the result.
        let fn = !/\W/.test(str) ?
          cache[str] = cache[str] ||
            tmpl(document.getElementById(str).innerHTML) :
          // Generate a reusable function that will serve as a template
          // generator (and which will be cached).
          new Function("obj",
            "let p=[],print=function(){p.push.apply(p,arguments);};" +
            // Introduce the data as local variables using with(){}
            "with(obj){p.push('" +
            // Convert the template into pure JavaScript
            str
              .replace(/[\r\t\n]/g, " ")
              .split("<%").join("\t")
              .replace(/((^|%>)[^\t]*)'/g, "$1\r")
              .replace(/\t=(.*?)%>/g, "',$1,'")
              .split("\t").join("');")
              .split("%>").join("p.push('")
              .split("\r").join("\\'")
            + "');}return p.join('');");
        // Provide some basic currying to the user
        return data ? fn(data) : fn;
      };

      /**
       * Gets GeoCoder object from zip code.
       *
       * @param address
       * @returns {JQuery.Deferred<any, any, any>}
       */
      function getPosition(address) {
        let d = $.Deferred();
        let geoCoder = new google.maps.Geocoder();
        geoCoder.geocode({'address': address}, function (results, status) {
          if (status === google.maps.GeocoderStatus.OK) {
            d.resolve(results);
          }
        });
        return d;
      }

      /**
       * Get co-ordinates of all matching stores.
       *
       * @param obj
       * @param yourCoOrd
       * @param limit
       */
      function getCoordinates(obj, yourCoOrd, limit) {
        let semiResultsArray = [];
        let currIndex = 0;
      }

      /**
       * Function fired from Submit click.
       */
      function sendForm() {
        userZip = searchBox.val(); // user might have changed value from cookie
        if (userZip.match(/^[0-9]{5}$/) == null) {
          searchBox.addClass("is-danger").focus().next('p').removeClass('is-hidden');
        } else {
          let selectedOption = distanceSelect.children().eq(distanceSelect.get(0).selectedIndex);
          let selectedDistance;
          if (selectedOption.hasClass(overClass)) {
            selectedDistance = 99999;
          } else {
            selectedDistance = parseInt(selectedOption.text(), 10);
          }

          getPosition(userZip).done(function (results) {
            let currCoOrd = [results[0].geometry.location.lat(), results[0].geometry.location.lng()];
            let dataObject = getCoordinates(locationCoordinates, currCoOrd, selectedDistance);
            let html = '';
            let lat = results[0].geometry.location.lat();
            let lng = results[0].geometry.location.lng();

            for (let i in dataObject) {
              if (dataObject.hasOwnProperty(i)) {
                let num = parseInt(i) + 1;
                dataObject[i].features[0].properties.i = i;
                dataObject[i].features[0].properties.num = num;
                let locality = dataObject[i].features[0].properties.locality.replace(/<[^>]+>/g, '');
                let city = dataObject[i].features[0].properties.locality.replace(/,.*/g, '');
                let address = dataObject[i].features[0].properties.address;
                let address_loc = dataObject[i].features[0].properties.address + ',' + locality;
                let encoded = address_loc.replace(/[\s]+/g, '+');
                let info = '<div class="infoDiv">' +
                  '<a href="https://www.google.com/maps/place/'
                  + encoded + '" target="_blank" rel="noopener">' + address + ', ' + city + "</a></div>";
                let storeLocation = [info, dataObject[i].features[0].geometry.coordinates];
                storeLocations.push(storeLocation);
                html += tmpl("result_tmpl", dataObject[i].features[0].properties);
              }

            }
          });

          $('#storeResults').html(html);
        }
      }

      $("#searchSubmit").once("StoreLocator").on("click", function (e) {
        e.preventDefault();
        sendForm();
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
