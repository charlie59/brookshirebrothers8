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
    }
  }
})(jQuery, Drupal, drupalSettings);
