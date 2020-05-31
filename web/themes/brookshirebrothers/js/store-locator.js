(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.StoreLocator = {
    attach: function (context, settings) {
      let userZip = $.cookie('userZip');
      let searchBox = $("#search");
      if (userZip) {
        searchBox.once("StoreLocator").val(userZip);
      }
      $("#moreOptions").once("StoreLocator").click(function() {
        $("#moreOptionsSection").toggle();
      });

      userZip = searchBox.val(); // user might have changed value
      let storeLocations;
      let distanceSelect = $('#filter-distance');
      let overClass = 'over';

      function sendForm() {
        $.ajax({
          type: 'get',
          cache: false,
          url: '/store-locator',
          data: 'ajax=1',
          dataType: 'text',
          success: function (data) {
            let selectedOption = distanceSelect.children().eq(distanceSelect.get(0).selectedIndex);
            let selectedDistance = parseInt(selectedOption.text(), 10);
            if (selectedOption.hasClass(overClass)) {
              selectedDistance = 99999;
            }

            $(data).appendTo($('#storeResults'));

          }
        });
      }
      $("#searchSubmit").click(function(e){
        e.preventDefault();
        sendForm();
      });
    }
  }
})(jQuery, Drupal);
