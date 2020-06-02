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
      // TODO add check for valid value, State, City , Zip - so maybe just not empty.
    }
  }
})(jQuery, Drupal, drupalSettings);
