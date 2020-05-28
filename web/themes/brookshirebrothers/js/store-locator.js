(function ($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.StoreLocator = {
    attach: function (context, settings) {
      let userZip = $.cookie('userZip');
      if (userZip) {
        $("#search").once("StoreLocator").val(userZip);
      }
      $("#moreOptions").once("StoreLocator").click(function() {
        $("#moreOptionsSection").toggle();
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
